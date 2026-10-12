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

/** Newsletters des pôles : abonnés, modèles, campagnes, envoi par lots. Un éditeur ne gère que son pôle. */
final class NewsletterController
{
    public static function index(): void
    {
        $user = Auth::require();
        $poles = self::poles($user);
        $ids = array_column($poles, 'id');
        $in = implode(',', array_map('intval', $ids)) ?: '0';
        $stats = Db::run("SELECT pole_id, status, COUNT(*) AS n FROM newsletter_subscribers WHERE pole_id IN ($in) GROUP BY pole_id, status")->fetchAll();
        $counts = [];
        foreach ($stats as $s) {
            $counts[$s['pole_id']][$s['status']] = (int) $s['n'];
        }
        View::render('admin/newsletters/index', [
            'title' => 'Newsletters',
            'poles' => $poles,
            'counts' => $counts,
            'campaigns' => Db::run(
                "SELECT c.*, p.name AS pole,
                        (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id) AS total,
                        (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id AND d.status = 'sent') AS sent
                 FROM newsletter_campaigns c JOIN poles p ON p.id = c.pole_id WHERE c.pole_id IN ($in) ORDER BY c.created_at DESC"
            )->fetchAll(),
            'templates' => Db::run("SELECT t.*, p.name AS pole FROM newsletter_templates t JOIN poles p ON p.id = t.pole_id WHERE t.pole_id IN ($in) ORDER BY p.position, t.name")->fetchAll(),
            'pending' => (int) Db::run("SELECT COUNT(*) FROM newsletter_deliveries WHERE status = 'pending'")->fetchColumn(),
            'quota' => [Sender::sentLast24h(), Sender::dailyLimit(), Sender::batchSize()],
        ], 'admin/layout');
    }

    // ---- Abonnés

    public static function subscribers(): void
    {
        $user = Auth::require();
        $poles = self::poles($user);
        $pole = self::poleParam($user, $_GET['pole'] ?? null);
        $status = in_array($_GET['statut'] ?? '', ['active', 'pending', 'unsubscribed'], true) ? $_GET['statut'] : '';
        $q = trim((string) ($_GET['q'] ?? ''));
        $where = ['s.pole_id IN (' . (implode(',', array_map('intval', array_column($poles, 'id'))) ?: '0') . ')'];
        $params = [];
        if ($pole !== null) {
            $where[] = 's.pole_id = ?';
            $params[] = $pole;
        }
        if ($status !== '') {
            $where[] = 's.status = ?';
            $params[] = $status;
        }
        if ($q !== '') {
            $where[] = 's.email LIKE ?';
            $params[] = '%' . $q . '%';
        }
        $rows = Db::run(
            'SELECT s.*, p.name AS pole FROM newsletter_subscribers s JOIN poles p ON p.id = s.pole_id WHERE ' . implode(' AND ', $where) . ' ORDER BY s.consent_at DESC LIMIT 500',
            $params
        )->fetchAll();
        View::render('admin/newsletters/subscribers', [
            'title' => 'Abonnés',
            'rows' => $rows,
            'poles' => $poles,
            'pole' => $pole,
            'status' => $status,
            'q' => $q,
        ], 'admin/layout');
    }

    public static function addSubscriber(): void
    {
        $user = Auth::require();
        Csrf::check();
        $email = trim((string) ($_POST['email'] ?? ''));
        $pole = self::poleParam($user, $_POST['pole'] ?? null);
        $source = trim((string) ($_POST['source'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pole === null || $source === '') {
            Session::flash('Indiquez une adresse valide, le pôle et l\'origine du consentement.', 'error');
            redirect('/admin/newsletters/abonnes');
        }
        $exists = Db::run('SELECT status FROM newsletter_subscribers WHERE pole_id = ? AND email = ?', [$pole, $email])->fetchColumn();
        if ($exists === 'unsubscribed') {
            Session::flash('Cette adresse s\'est désinscrite : elle ne peut être réinscrite que par la personne elle-même, via le formulaire du site.', 'error');
        } elseif ($exists) {
            Session::flash('Cette adresse est déjà inscrite à cette lettre.', 'error');
        } else {
            Db::run(
                "INSERT INTO newsletter_subscribers (pole_id, email, status, token, source, confirmed_at) VALUES (?, ?, 'active', ?, ?, CURRENT_TIMESTAMP)",
                [$pole, $email, bin2hex(random_bytes(24)), 'ajout manuel : ' . $source]
            );
            Auth::log('abonne.ajout', $email);
            Session::flash('Abonné ajouté.');
        }
        redirect('/admin/newsletters/abonnes?pole=' . $pole);
    }

    public static function deleteSubscriber(string $id): void
    {
        $user = Auth::require();
        Csrf::check();
        $row = Db::run('SELECT * FROM newsletter_subscribers WHERE id = ?', [(int) $id])->fetch();
        if ($row && in_array((int) $row['pole_id'], array_map('intval', array_column(self::poles($user), 'id')), true)) {
            Db::run('DELETE FROM newsletter_subscribers WHERE id = ?', [$row['id']]);
            Auth::log('abonne.suppression', $row['email']);
            Session::flash('Abonné supprimé définitivement (droit à l\'effacement).');
        }
        redirect('/admin/newsletters/abonnes');
    }

    public static function export(): void
    {
        $user = Auth::require();
        $pole = self::poleParam($user, $_GET['pole'] ?? null);
        $in = implode(',', array_map('intval', $pole !== null ? [$pole] : array_column(self::poles($user), 'id'))) ?: '0';
        $rows = Db::run("SELECT s.email, p.slug AS pole, s.status, s.source, s.consent_at, s.confirmed_at, s.unsubscribed_at FROM newsletter_subscribers s JOIN poles p ON p.id = s.pole_id WHERE s.pole_id IN ($in) ORDER BY p.position, s.email")->fetchAll();
        Auth::log('abonnes.export', (string) count($rows));
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="abonnes-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['email', 'pole', 'statut', 'origine', 'consentement', 'confirmation', 'desinscription'], ';');
        foreach ($rows as $r) {
            fputcsv($out, array_values($r), ';');
        }
        fclose($out);
    }

    /**
     * Import CSV. Colonnes reconnues (en-tête) : email (obligatoire), pole, date, source.
     * Une adresse désinscrite ou déjà présente n'est jamais modifiée. Sans consentement déclaré, les contacts restent « en attente ».
     */
    public static function import(): void
    {
        $user = Auth::require();
        Csrf::check();
        $defaultPole = self::poleParam($user, $_POST['pole'] ?? null);
        $source = trim((string) ($_POST['source'] ?? ''));
        $consented = !empty($_POST['consent']);
        $file = $_FILES['csv']['tmp_name'] ?? '';
        if (!is_string($file) || !is_uploaded_file($file) || $source === '') {
            Session::flash('Choisissez un fichier CSV et décrivez l\'origine des contacts.', 'error');
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
        $iPole = $col(['pole', 'pôle', 'liste', 'list']);
        $iDate = $col(['date', 'inscri', 'consent', 'created']);
        $allowed = array_column(self::poles($user), null, 'id');
        $match = static function (string $v) use ($allowed): ?int {
            $v = strtolower($v);
            foreach ($allowed as $id => $p) {
                $city = strtolower(explode(' ', str_replace('Pôle ESS Pays de ', '', $p['name']))[0]);
                if ($v === $p['slug'] || $v === strtolower($p['name']) || str_contains($v, $city) || str_contains($v, strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $city)))) {
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
            $pole = $iPole !== null ? $match(trim((string) ($cells[$iPole] ?? ''))) : null;
            $pole ??= $defaultPole;
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pole === null || !isset($allowed[$pole])) {
                $stats['invalides']++;
                continue;
            }
            $existing = Db::run('SELECT status FROM newsletter_subscribers WHERE pole_id = ? AND email = ?', [$pole, $email])->fetchColumn();
            if ($existing !== false) {
                $stats[$existing === 'unsubscribed' ? 'desinscrits' : 'existants']++;
                continue;
            }
            $date = $iDate !== null ? strtotime((string) ($cells[$iDate] ?? '')) : false;
            Db::run(
                'INSERT INTO newsletter_subscribers (pole_id, email, status, token, source, consent_at, confirmed_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $pole,
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
            'Import terminé : %d ajoutés (%s), %d déjà inscrits, %d désinscrits ignorés, %d lignes invalides ou sans pôle.',
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
        $user = Auth::require();
        $tpl = $id !== null ? self::findTemplate($user, (int) $id) : ['name' => '', 'header' => '', 'footer' => '', 'pole_id' => $user['pole_id']];
        View::render('admin/newsletters/template', ['title' => $id ? 'Modifier le modèle' : 'Nouveau modèle', 'tpl' => $tpl, 'poles' => self::poles($user), 'id' => $id], 'admin/layout');
    }

    public static function templateSave(?string $id = null): void
    {
        $user = Auth::require();
        Csrf::check();
        $name = trim((string) ($_POST['name'] ?? ''));
        $pole = self::poleParam($user, $_POST['pole'] ?? null);
        if ($name === '' || $pole === null) {
            Session::flash('Le nom et le pôle du modèle sont obligatoires.', 'error');
            redirect($id ? "/admin/newsletters/modeles/$id" : '/admin/newsletters/modeles/nouveau');
        }
        $values = [$pole, $name, trim((string) ($_POST['header'] ?? '')), trim((string) ($_POST['footer'] ?? ''))];
        if ($id !== null) {
            self::findTemplate($user, (int) $id);
            Db::run('UPDATE newsletter_templates SET pole_id = ?, name = ?, header = ?, footer = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [...$values, (int) $id]);
        } else {
            Db::run('INSERT INTO newsletter_templates (pole_id, name, header, footer) VALUES (?, ?, ?, ?)', $values);
        }
        Auth::log('modele.enregistrement', $name);
        Session::flash('Modèle enregistré.');
        redirect('/admin/newsletters');
    }

    public static function templateDelete(string $id): void
    {
        $user = Auth::require();
        Csrf::check();
        $tpl = self::findTemplate($user, (int) $id);
        Db::run('DELETE FROM newsletter_templates WHERE id = ?', [$tpl['id']]);
        Session::flash('Modèle supprimé.');
        redirect('/admin/newsletters');
    }

    // ---- Campagnes

    public static function campaignForm(?string $id = null): void
    {
        $user = Auth::require();
        $c = $id !== null ? self::findCampaign($user, (int) $id) : ['subject' => '', 'body' => '', 'pole_id' => $user['pole_id'], 'template_id' => null, 'status' => 'draft'];
        $in = implode(',', array_map('intval', array_column(self::poles($user), 'id'))) ?: '0';
        View::render('admin/newsletters/campaign', [
            'title' => $id ? 'Lettre : ' . $c['subject'] : 'Nouvelle lettre',
            'c' => $c,
            'id' => $id,
            'poles' => self::poles($user),
            'templates' => Db::run("SELECT t.id, t.name, p.name AS pole FROM newsletter_templates t JOIN poles p ON p.id = t.pole_id WHERE t.pole_id IN ($in) ORDER BY p.position, t.name")->fetchAll(),
            'recipients' => $id ? (int) Db::run("SELECT COUNT(*) FROM newsletter_subscribers WHERE pole_id = ? AND status = 'active'", [$c['pole_id']])->fetchColumn() : null,
            'progress' => $id ? Db::run('SELECT status, COUNT(*) FROM newsletter_deliveries WHERE campaign_id = ? GROUP BY status', [(int) $id])->fetchAll(\PDO::FETCH_KEY_PAIR) : [],
        ], 'admin/layout');
    }

    public static function campaignSave(?string $id = null): void
    {
        $user = Auth::require();
        Csrf::check();
        if ($id !== null && self::findCampaign($user, (int) $id)['status'] !== 'draft') {
            Session::flash('Une lettre déjà envoyée ne peut plus être modifiée.', 'error');
            redirect("/admin/newsletters/campagnes/$id");
        }
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body = trim(str_replace("\r\n", "\n", (string) ($_POST['body'] ?? '')));
        $pole = self::poleParam($user, $_POST['pole'] ?? null);
        $template = (int) ($_POST['template_id'] ?? 0) ?: null;
        if ($template !== null && (int) Db::run('SELECT pole_id FROM newsletter_templates WHERE id = ?', [$template])->fetchColumn() !== $pole) {
            $template = null;
        }
        if ($subject === '' || $body === '' || $pole === null) {
            Session::flash('L\'objet, le texte et le pôle sont obligatoires.', 'error');
            redirect($id ? "/admin/newsletters/campagnes/$id" : '/admin/newsletters/campagnes/nouvelle');
        }
        if ($id !== null) {
            Db::run('UPDATE newsletter_campaigns SET pole_id = ?, template_id = ?, subject = ?, body = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [$pole, $template, $subject, $body, (int) $id]);
        } else {
            Db::run('INSERT INTO newsletter_campaigns (pole_id, template_id, subject, body, created_by) VALUES (?, ?, ?, ?, ?)', [$pole, $template, $subject, $body, $user['id']]);
            $id = (string) Db::pdo()->lastInsertId();
        }
        Auth::log('lettre.enregistrement', $subject);
        Session::flash('Brouillon enregistré. Vérifiez l\'aperçu et envoyez-vous un test avant l\'envoi.');
        redirect("/admin/newsletters/campagnes/$id");
    }

    public static function preview(string $id): void
    {
        $user = Auth::require();
        self::findCampaign($user, (int) $id);
        [$campaign, $template, $pole] = Sender::context((int) $id);
        header("Content-Security-Policy: default-src 'none'; img-src 'self' https: data:; style-src 'unsafe-inline'; frame-ancestors 'none'");
        echo Renderer::render($campaign, $template, $pole, null)['html'];
    }

    public static function test(string $id): void
    {
        $user = Auth::require();
        Csrf::check();
        self::findCampaign($user, (int) $id);
        $to = trim((string) ($_POST['to'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            Session::flash('Adresse de test invalide.', 'error');
        } else {
            $ok = Sender::sendTest((int) $id, $to);
            Session::flash($ok ? "Test envoyé à $to." : 'L\'envoi du test a échoué : vérifiez la configuration SMTP.', $ok ? 'success' : 'error');
        }
        redirect("/admin/newsletters/campagnes/$id");
    }

    public static function send(string $id): void
    {
        $user = Auth::require();
        Csrf::check();
        $c = self::findCampaign($user, (int) $id);
        if ($c['status'] !== 'draft' || empty($_POST['confirm'])) {
            Session::flash('Cochez la confirmation pour lancer l\'envoi.', 'error');
            redirect("/admin/newsletters/campagnes/$id");
        }
        $n = Sender::queue((int) $id);
        Auth::log('lettre.envoi', $c['subject'] . " ($n)");
        [$sent] = Sender::sendBatch();
        Session::flash("Envoi lancé vers $n abonnés : $sent envoyés tout de suite, la suite part par lots automatiques.");
        redirect("/admin/newsletters/campagnes/$id");
    }

    public static function batch(): void
    {
        Auth::require();
        Csrf::check();
        [$sent, $failed, $left] = Sender::sendBatch();
        Session::flash("Lot envoyé : $sent envois, $failed échecs, $left en attente.");
        redirect('/admin/newsletters');
    }

    public static function campaignDelete(string $id): void
    {
        $user = Auth::require();
        Csrf::check();
        $c = self::findCampaign($user, (int) $id);
        if ($c['status'] === 'draft') {
            Db::run('DELETE FROM newsletter_campaigns WHERE id = ?', [$c['id']]);
            Session::flash('Brouillon supprimé.');
        }
        redirect('/admin/newsletters');
    }

    // ---- Outils

    /**
     * @param array<string, mixed> $user
     * @return list<array<string, mixed>>
     */
    private static function poles(array $user): array
    {
        return $user['role'] === 'admin'
            ? Db::run('SELECT * FROM poles ORDER BY position')->fetchAll()
            : Db::run('SELECT * FROM poles WHERE id = ?', [(int) $user['pole_id']])->fetchAll();
    }

    /** @param array<string, mixed> $user */
    private static function poleParam(array $user, mixed $value): ?int
    {
        if ($user['role'] === 'editor') {
            return (int) $user['pole_id'];
        }
        $id = (int) $value;
        return $id > 0 && Db::run('SELECT 1 FROM poles WHERE id = ?', [$id])->fetchColumn() ? $id : null;
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private static function findTemplate(array $user, int $id): array
    {
        $t = Db::run('SELECT * FROM newsletter_templates WHERE id = ?', [$id])->fetch();
        if (!$t || ($user['role'] === 'editor' && (int) $t['pole_id'] !== (int) $user['pole_id'])) {
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
        if (!$c || ($user['role'] === 'editor' && (int) $c['pole_id'] !== (int) $user['pole_id'])) {
            http_response_code(404);
            exit('Lettre introuvable.');
        }
        return $c;
    }
}
