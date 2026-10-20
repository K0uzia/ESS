<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Session;
use App\Core\View;
use App\Newsletter\Renderer;
use App\Newsletter\Sender;
use App\Site\Orgs;

/**
 * Newsletters : deux listes par organisme (adhérents, inscrits via le site), chacune avec ses abonnés et ses lettres ;
 * modèles communs, envoi par lots. Un éditeur ne gère que les listes de son organisme.
 */
final class NewsletterController
{
    public static function index(): void
    {
        $user = self::user();
        $lists = self::lists($user);
        $in = self::inLists($lists);
        $active = Db::run("SELECT list_id, COUNT(*) FROM newsletter_subscribers WHERE list_id IN ($in) AND status = 'active' GROUP BY list_id")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $campaigns = Db::run(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id) AS total,
                    (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id AND d.status = 'sent') AS sent
             FROM newsletter_campaigns c WHERE c.list_id IN ($in) ORDER BY c.created_at DESC"
        )->fetchAll();
        // Un onglet par organisme, puis ses deux listes avec leurs lettres.
        $orgs = [];
        foreach ($lists as $l) {
            $orgs[$l['org']] ??= ['name' => $l['org_name'], 'lists' => []];
            $orgs[$l['org']]['lists'][] = $l + [
                'active' => (int) ($active[$l['id']] ?? 0),
                'campaigns' => array_values(array_filter($campaigns, static fn (array $c): bool => (int) $c['list_id'] === (int) $l['id'])),
            ];
        }
        View::render('admin/newsletters/index', [
            'title' => 'Newsletters',
            'orgs' => $orgs,
            'templates' => Db::run('SELECT * FROM newsletter_templates ORDER BY name')->fetchAll(),
            'pending' => (int) Db::run("SELECT COUNT(*) FROM newsletter_deliveries WHERE status = 'pending'")->fetchColumn(),
        ], 'admin/layout');
    }

    // ---- Abonnés

    public static function subscribers(): void
    {
        $user = self::user();
        $lists = self::subscriberLists($user);
        $list = self::listParam($user, $_GET['liste'] ?? null, true);
        $status = in_array($_GET['statut'] ?? '', ['active', 'pending', 'unsubscribed'], true) ? $_GET['statut'] : '';
        $q = trim((string) ($_GET['q'] ?? ''));
        $where = ['s.list_id IN (' . self::inLists($lists) . ')'];
        $params = [];
        if ($list !== null) {
            $where[] = 's.list_id = ?';
            $params[] = $list;
        }
        if ($status !== '') {
            $where[] = 's.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = 's.email LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $rows = Db::run('SELECT s.* FROM newsletter_subscribers s WHERE ' . implode(' AND ', $where) . ' ORDER BY s.consent_at DESC LIMIT 5000', $params)->fetchAll();
        $labels = array_column($lists, 'label', 'id');
        foreach ($rows as &$r) {
            $r['list'] = $labels[$r['list_id']] ?? '';
        }
        unset($r);
        View::render('admin/newsletters/subscribers', [
            'title' => 'Abonnés',
            'rows' => $rows,
            'lists' => $lists,
            'list' => $list,
            'status' => $status,
            'q' => $q,
        ], 'admin/layout');
    }

    public static function addSubscriber(): void
    {
        $user = self::user();
        Csrf::check();
        $email = trim((string) ($_POST['email'] ?? ''));
        $list = self::listParam($user, $_POST['liste'] ?? null, true);
        $source = trim((string) ($_POST['source'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $list === null || $source === '') {
            Session::flash('Indiquez une adresse valide, la liste et l\'origine du consentement.', 'error');
            redirect('/admin/newsletters/abonnes');
        }
        $exists = Db::run('SELECT status FROM newsletter_subscribers WHERE list_id = ? AND email = ?', [$list, $email])->fetchColumn();
        if ($exists === 'unsubscribed') {
            Session::flash('Cette adresse s\'est désinscrite : elle ne peut être réinscrite que par la personne elle-même, via le formulaire du site.', 'error');
        } elseif ($exists) {
            Session::flash('Cette adresse est déjà inscrite à cette liste.', 'error');
        } else {
            Db::run(
                "INSERT INTO newsletter_subscribers (list_id, email, status, token, source, confirmed_at) VALUES (?, ?, 'active', ?, ?, CURRENT_TIMESTAMP)",
                [$list, $email, bin2hex(random_bytes(24)), 'ajout manuel : ' . $source]
            );
            Auth::log('abonne.ajout', $email);
            Session::flash('Abonné ajouté.');
        }
        redirect('/admin/newsletters/abonnes');
    }

    public static function deleteSubscriber(string $id): void
    {
        $user = self::user();
        Csrf::check();
        $row = Db::run('SELECT * FROM newsletter_subscribers WHERE id = ?', [(int) $id])->fetch();
        if ($row && in_array((int) $row['list_id'], array_map('intval', array_column(self::lists($user), 'id')), true)) {
            Db::run('DELETE FROM newsletter_subscribers WHERE id = ?', [$row['id']]);
            Auth::log('abonne.suppression', $row['email']);
            Session::flash('Abonné supprimé définitivement (droit à l\'effacement).');
        }
        redirect('/admin/newsletters/abonnes');
    }

    public static function export(): void
    {
        $user = self::user();
        $lists = self::subscriberLists($user);
        $list = self::listParam($user, $_GET['liste'] ?? null, true);
        $in = $list !== null ? (string) $list : self::inLists($lists);
        $rows = Db::run("SELECT s.email, l.org, l.audience, s.status, s.source, s.consent_at, s.confirmed_at, s.unsubscribed_at FROM newsletter_subscribers s JOIN newsletter_lists l ON l.id = s.list_id WHERE s.list_id IN ($in) ORDER BY l.id, s.email")->fetchAll();
        Auth::log('abonnes.export', (string) count($rows));
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="abonnes-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['email', 'organisme', 'liste', 'statut', 'origine', 'consentement', 'confirmation', 'desinscription'], ';');
        foreach ($rows as $r) {
            fputcsv($out, array_values($r), ';');
        }
        fclose($out);
    }

    /**
     * Import CSV dans une liste. Colonnes reconnues (en-tête) : email (obligatoire), date. Une colonne pole/organisme,
     * si elle existe, range chaque ligne dans la liste du même public (adhérents ou inscrits) de cet organisme.
     * Une adresse désinscrite ou déjà présente n'est jamais modifiée. Sans consentement déclaré, les contacts restent « en attente ».
     */
    public static function import(): void
    {
        $user = self::user();
        Csrf::check();
        $defaultList = self::listParam($user, $_POST['liste'] ?? null, true);
        $source = trim((string) ($_POST['source'] ?? ''));
        $consented = !empty($_POST['consent']);
        $file = $_FILES['csv']['tmp_name'] ?? '';
        if (!is_string($file) || !is_uploaded_file($file) || $source === '' || $defaultList === null) {
            Session::flash('Choisissez la liste, un fichier CSV et décrivez l\'origine des contacts.', 'error');
            redirect('/admin/newsletters/abonnes');
        }
        $content = (string) file_get_contents($file);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        if (!mb_check_encoding_safe($content)) {
            $content = (string) iconv('Windows-1252', 'UTF-8//TRANSLIT', $content);
        }
        $lines = preg_split('/\R/', trim($content)) ?: [];
        $sep = substr_count($lines[0] ?? '', ';') >= substr_count($lines[0] ?? '', ',') ? ';' : ',';
        $head = array_map(fn ($h) => strtolower(trim((string) $h, " \"'")), str_getcsv((string) array_shift($lines), $sep));
        $col = static function (array $names) use ($head): ?int {
            foreach ($head as $i => $h) {
                foreach ($names as $n) {
                    if (str_contains($h, $n)) {
                        return $i;
                    }
                }
            }
            return null;
        };
        $iEmail = $col(['mail']);
        if ($iEmail === null) {
            Session::flash('Colonne « email » introuvable dans la première ligne du fichier.', 'error');
            redirect('/admin/newsletters/abonnes');
        }
        $iPole = $col(['pole', 'pôle', 'organisme']);
        $iDate = $col(['date', 'inscri', 'consent', 'created']);
        $audience = (string) Orgs::findList($defaultList)['audience'];
        // Listes du même public que la liste choisie, une par organisme accessible.
        $allowed = array_column(array_filter(self::subscriberLists($user), static fn (array $l): bool => $l['audience'] === $audience), null, 'id');
        $match = static function (string $v) use ($allowed): ?int {
            $v = strtolower($v);
            foreach ($allowed as $id => $l) {
                $short = strtolower((string) Orgs::get($l['org'])['short']);
                if ($v === $l['org'] || $v === strtolower($l['org_name']) || str_contains($v, $short) || str_contains($v, strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT', $short)))) {
                    return (int) $id;
                }
            }
            return null;
        };
        $stats = ['ajoutes' => 0, 'existants' => 0, 'desinscrits' => 0, 'invalides' => 0];
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $cells = str_getcsv($line, $sep);
            $email = strtolower(trim((string) ($cells[$iEmail] ?? ''), " \"'"));
            $list = $iPole !== null ? $match(trim((string) ($cells[$iPole] ?? ''))) : null;
            $list ??= $defaultList;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !isset($allowed[$list])) {
                $stats['invalides']++;
                continue;
            }
            $existing = Db::run('SELECT status FROM newsletter_subscribers WHERE list_id = ? AND email = ?', [$list, $email])->fetchColumn();
            if ($existing !== false) {
                $stats[$existing === 'unsubscribed' ? 'desinscrits' : 'existants']++;
                continue;
            }
            $date = $iDate !== null ? strtotime((string) ($cells[$iDate] ?? '')) : false;
            Db::run(
                'INSERT INTO newsletter_subscribers (list_id, email, status, token, source, consent_at, confirmed_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $list,
                    $email,
                    $consented ? 'active' : 'pending',
                    bin2hex(random_bytes(24)),
                    'import CSV : ' . $source,
                    $date ? gmdate('Y-m-d H:i:s', $date) : gmdate('Y-m-d H:i:s'),
                    $consented ? gmdate('Y-m-d H:i:s') : null,
                ]
            );
            $stats['ajoutes']++;
        }
        $pdo->commit();
        Auth::log('abonnes.import', json_encode($stats) ?: '');
        Session::flash(sprintf(
            'Import terminé : %d ajoutés (%s), %d déjà inscrits, %d désinscrits ignorés, %d lignes invalides.',
            $stats['ajoutes'],
            $consented ? 'actifs' : 'en attente de consentement',
            $stats['existants'],
            $stats['desinscrits'],
            $stats['invalides']
        ));
        redirect('/admin/newsletters/abonnes');
    }

    // ---- Modèles

    public static function templateForm(?string $id = null): void
    {
        self::user();
        $tpl = $id !== null ? self::findTemplate((int) $id) : ['name' => '', 'header' => '', 'footer' => ''];
        View::render('admin/newsletters/template', ['title' => $id ? 'Modifier le modèle' : 'Nouveau modèle', 'tpl' => $tpl, 'id' => $id], 'admin/layout');
    }

    public static function templateSave(?string $id = null): void
    {
        self::user();
        Csrf::check();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Session::flash('Le nom du modèle est obligatoire.', 'error');
            redirect($id ? "/admin/newsletters/modeles/$id" : '/admin/newsletters/modeles/nouveau');
        }
        $values = [$name, trim((string) ($_POST['header'] ?? '')), trim((string) ($_POST['footer'] ?? ''))];
        if ($id !== null) {
            self::findTemplate((int) $id);
            Db::run('UPDATE newsletter_templates SET name = ?, header = ?, footer = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [...$values, (int) $id]);
        } else {
            Db::run('INSERT INTO newsletter_templates (name, header, footer) VALUES (?, ?, ?)', $values);
        }
        Auth::log('modele.enregistrement', $name);
        Session::flash('Modèle enregistré.');
        redirect('/admin/newsletters?vue=modeles');
    }

    public static function templateDelete(string $id): void
    {
        self::user();
        Csrf::check();
        $tpl = self::findTemplate((int) $id);
        Db::run('DELETE FROM newsletter_templates WHERE id = ?', [$tpl['id']]);
        Session::flash('Modèle supprimé.');
        redirect('/admin/newsletters?vue=modeles');
    }

    // ---- Campagnes

    public static function campaignForm(?string $id = null): void
    {
        $user = self::user();
        $c = $id !== null ? self::findCampaign($user, (int) $id)
            : ['subject' => '', 'body' => '', 'list_id' => self::listParam($user, $_GET['liste'] ?? null), 'template_id' => null, 'status' => 'draft'];
        View::render('admin/newsletters/campaign', [
            'title' => $id ? 'Lettre : ' . $c['subject'] : 'Nouvelle lettre',
            'c' => $c,
            'id' => $id,
            'lists' => self::lists($user),
            'templates' => Db::run('SELECT id, name FROM newsletter_templates ORDER BY name')->fetchAll(),
            'recipients' => $id ? (int) Db::run("SELECT COUNT(*) FROM newsletter_subscribers WHERE list_id = ? AND status = 'active'", [$c['list_id']])->fetchColumn() : null,
            'progress' => $id ? Db::run('SELECT status, COUNT(*) FROM newsletter_deliveries WHERE campaign_id = ? GROUP BY status', [(int) $id])->fetchAll(\PDO::FETCH_KEY_PAIR) : [],
        ], 'admin/layout');
    }

    public static function campaignSave(?string $id = null): void
    {
        $user = self::user();
        Csrf::check();
        if ($id !== null && self::findCampaign($user, (int) $id)['status'] !== 'draft') {
            Session::flash('Une lettre déjà envoyée ne peut plus être modifiée.', 'error');
            redirect("/admin/newsletters/campagnes/$id");
        }
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? '')));
        $list = self::listParam($user, $_POST['liste'] ?? null);
        $template = (int) ($_POST['template_id'] ?? 0) ?: null;
        if ($template !== null && !Db::run('SELECT 1 FROM newsletter_templates WHERE id = ?', [$template])->fetchColumn()) {
            $template = null;
        }
        if ($subject === '' || $body === '' || $list === null) {
            Session::flash('L\'objet, le texte et la liste sont obligatoires.', 'error');
            redirect($id ? "/admin/newsletters/campagnes/$id" : '/admin/newsletters/campagnes/nouvelle');
        }
        if ($id !== null) {
            Db::run('UPDATE newsletter_campaigns SET list_id = ?, template_id = ?, subject = ?, body = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$list, $template, $subject, $body, (int) $id]);
        } else {
            Db::run('INSERT INTO newsletter_campaigns (list_id, template_id, subject, body, created_by) VALUES (?, ?, ?, ?, ?)', [$list, $template, $subject, $body, $user['id']]);
            $id = (string) Db::pdo()->lastInsertId();
        }
        Auth::log('lettre.enregistrement', $subject);
        Session::flash('Brouillon enregistré. Étape suivante : vérifier la lettre.');
        redirect("/admin/newsletters/campagnes/$id?etape=verifier");
    }

    public static function preview(string $id): void
    {
        $user = self::user();
        self::findCampaign($user, (int) $id);
        [$campaign, $template, $list] = Sender::context((int) $id);
        // Affiché dans la fenêtre d'aperçu du tableau de bord (iframe isolée, même origine seulement).
        header("Content-Security-Policy: default-src 'none'; img-src 'self' https: data:; style-src 'unsafe-inline'; frame-ancestors 'self'");
        header('X-Frame-Options: SAMEORIGIN');
        // Les e-mails pointent vers le site en ligne ; l'aperçu charge les images depuis ce serveur-ci.
        echo str_replace(setting('site_url') . '/assets/', '/assets/', Renderer::render($campaign, $template, $list, null)['html']);
    }

    public static function test(string $id): void
    {
        $user = self::user();
        Csrf::check();
        self::findCampaign($user, (int) $id);
        $to = trim((string) ($_POST['to'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Session::flash('Adresse de test invalide.', 'error');
        } else {
            $ok = Sender::sendTest((int) $id, $to);
            Session::flash($ok ? "Test envoyé à $to." : \App\Core\Mailer::explain(), $ok ? 'success' : 'error');
        }
        redirect("/admin/newsletters/campagnes/$id?etape=verifier");
    }

    public static function send(string $id): void
    {
        $user = self::user();
        Csrf::check();
        $c = self::findCampaign($user, (int) $id);
        if ((string) (Orgs::findList((int) $c['list_id'])['sender_email'] ?? '') === '') {
            Session::flash('Cette liste n\'a pas encore d\'adresse d\'expédition : un administrateur doit la régler dans E-mails.', 'error');
            redirect("/admin/newsletters/campagnes/$id?etape=envoyer");
        }
        if ($c['status'] !== 'draft' || empty($_POST['confirm'])) {
            Session::flash('Cochez la confirmation pour lancer l\'envoi.', 'error');
            redirect("/admin/newsletters/campagnes/$id?etape=envoyer");
        }
        $list = Orgs::findList((int) $c['list_id']);
        if ($list['sympa_address'] !== '') {
            $ok = Sender::sendToSympa((int) $id);
            Auth::log('lettre.envoi', $c['subject'] . ' (liste ' . $list['sympa_address'] . ')');
            Session::flash($ok ? 'Lettre envoyée à la liste ' . $list['sympa_address'] . ' : Sympa la distribue à ses abonnés.' : \App\Core\Mailer::explain(), $ok ? 'success' : 'error');
            redirect("/admin/newsletters/campagnes/$id");
        }
        $n = Sender::queue((int) $id);
        Auth::log('lettre.envoi', $c['subject'] . " ($n)");
        [$sent] = Sender::sendBatch();
        Session::flash("Envoi lancé vers $n abonnés : $sent sont déjà partis, les autres suivent petit à petit.");
        redirect("/admin/newsletters/campagnes/$id");
    }

    public static function campaignDelete(string $id): void
    {
        $user = self::user();
        Csrf::check();
        $c = self::findCampaign($user, (int) $id);
        if ($c['status'] === 'draft') {
            Db::run('DELETE FROM newsletter_campaigns WHERE id = ?', [$c['id']]);
            Session::flash('Brouillon supprimé.');
        }
        redirect('/admin/newsletters?onglet=' . urlencode((string) (Orgs::findList((int) $c['list_id'])['org'] ?? '')));
    }

    // ---- Outils

    /** @return array<string, mixed> utilisateur connecté qui a au moins une liste ouverte (tout administrateur) */
    private static function user(): array
    {
        $user = Auth::require();
        if ($user['role'] !== 'admin' && self::lists($user) === []) {
            http_response_code(403);
            exit('Aucune newsletter ouverte pour votre organisme.');
        }
        return $user;
    }

    /**
     * Listes ouvertes accessibles : toutes pour un administrateur, celles de son organisme pour un éditeur.
     * @param array<string, mixed> $user
     * @return list<array<string, mixed>>
     */
    private static function lists(array $user): array
    {
        $org = Auth::org($user);
        return Orgs::lists($org === null ? null : [$org]);
    }

    /** @param list<array<string, mixed>> $lists */
    private static function inLists(array $lists): string
    {
        return implode(',', array_map(static fn (array $l): int => (int) $l['id'], $lists)) ?: '0';
    }

    /**
     * Listes dont le site gère les abonnés : pas les listes Sympa, dont les abonnés sont gérés dans Sympa.
     * @param array<string, mixed> $user
     * @return list<array<string, mixed>>
     */
    private static function subscriberLists(array $user): array
    {
        return array_values(array_filter(self::lists($user), static fn (array $l): bool => $l['sympa_address'] === ''));
    }

    /** Identifiant de liste accessible à l'utilisateur (parmi celles à abonnés si $subscribers), ou null. @param array<string, mixed> $user */
    private static function listParam(array $user, mixed $value, bool $subscribers = false): ?int
    {
        $id = (int) $value;
        $lists = $subscribers ? self::subscriberLists($user) : self::lists($user);
        return in_array($id, array_map('intval', array_column($lists, 'id')), true) ? $id : null;
    }

    /** @return array<string, mixed> */
    private static function findTemplate(int $id): array
    {
        $t = Db::run('SELECT * FROM newsletter_templates WHERE id = ?', [$id])->fetch();
        if (!$t) {
            http_response_code(404);
            exit('Modèle introuvable.');
        }
        return $t;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private static function findCampaign(array $user, int $id): array
    {
        $c = Db::run('SELECT * FROM newsletter_campaigns WHERE id = ?', [$id])->fetch();
        if (!$c || self::listParam($user, $c['list_id']) === null) {
            http_response_code(404);
            exit('Lettre introuvable.');
        }
        return $c;
    }
}
