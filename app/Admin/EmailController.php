<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;
use App\Site\Orgs;

/**
 * Toutes les adresses e-mail du site, réservé aux administrateurs (décisions du 15 et du 20 octobre 2026) :
 * boîtes et alias qui envoient, expéditeurs des e-mails du site, et pour chaque organisme l'adresse qui reçoit
 * le formulaire de contact, l'adresse de réponse et l'expéditeur de ses deux newsletters (adhérents, inscrits).
 * Serveur et port restent ceux par défaut (Hostinger, 465).
 */
final class EmailController
{
    public static function index(): void
    {
        Auth::require('admin');
        $senders = self::senders();
        $uses = self::uses();
        $known = array_map('strtolower', array_column($senders, 'email'));
        // Adresses choisies pour envoyer mais reliées à aucune boîte : rien ne partira tant qu'elles ne le sont pas.
        $missing = array_diff_key($uses, array_flip($known));
        // Chaque boîte suivie de ses alias, rangés dans l'ordre des organismes ; puis les alias d'une boîte absente.
        $firstRank = static fn (array $s): int => min(array_column($uses[strtolower($s['email'])] ?? [['rank' => 99]], 'rank'));
        $ordered = [];
        foreach ($senders as $box) {
            if ($box['box'] !== '') {
                continue;
            }
            $ordered[] = $box;
            $aliases = array_values(array_filter($senders, static fn (array $a): bool => strcasecmp($a['box'], $box['email']) === 0));
            usort($aliases, static fn (array $a, array $b): int => [$firstRank($a), $a['email']] <=> [$firstRank($b), $b['email']]);
            array_push($ordered, ...$aliases);
        }
        foreach ($senders as $s) {
            if (!in_array($s, $ordered, true)) {
                $ordered[] = $s;
            }
        }
        $senders = $ordered;
        $orgs = [];
        foreach (Orgs::all() as $key => $org) {
            $orgs[$key] = $org + [
                'contact' => Orgs::address($key, 'contact'),
                'reponse' => Orgs::address($key, 'reponse'),
                'public' => $key === 'tag29' ? null : self::publicEmail($org),
                'lists' => Orgs::lists([$key], false),
            ];
        }
        View::render('admin/emails/index', [
            'title' => 'E-mails',
            'senders' => $senders,
            'uses' => $uses,
            'missing' => $missing,
            'orgs' => $orgs,
        ], 'admin/layout');
    }

    // ---- Boîtes et alias d'envoi

    public static function senderForm(?string $id = null): void
    {
        Auth::require('admin');
        $row = $id !== null ? self::findSender($id) : null;
        $values = $row ? ['email' => $row['email'], 'kind' => $row['box'] === '' ? 'boite' : 'alias', 'box' => $row['box']]
            : ['email' => trim((string) ($_GET['email'] ?? '')), 'kind' => 'boite', 'box' => ''];
        self::form('sender', $values, [], ['row' => $row]);
    }

    public static function senderSave(?string $id = null): void
    {
        Auth::require('admin');
        Csrf::check();
        $row = $id !== null ? self::findSender($id) : null;
        $values = [
            'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'kind' => ($_POST['kind'] ?? '') === 'alias' ? 'alias' : 'boite',
            'box' => strtolower(trim((string) ($_POST['box'] ?? ''))),
        ];
        $password = (string) ($_POST['password'] ?? '');
        $boxes = array_column(array_filter(self::senders(), static fn (array $s): bool => $s['box'] === ''), 'email');
        $errors = [];
        if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse e-mail invalide.';
        } elseif (Db::run('SELECT 1 FROM mail_senders WHERE email = ? AND rowid <> ?', [$values['email'], (int) ($row['id'] ?? 0)])->fetchColumn()) {
            $errors['email'] = 'Cette adresse est déjà dans la liste.';
        }
        if ($values['kind'] === 'boite' && $password !== '' && !function_exists('openssl_encrypt')) {
            $errors['password'] = 'Le serveur ne peut pas chiffrer le mot de passe : l\'extension PHP « openssl » n\'est pas installée. Prévenez La Capsule.';
        }
        if ($values['kind'] === 'boite' && $password === '' && ($row === null || $row['password'] === '')) {
            $errors['password'] = 'Indiquez le mot de passe de cette boîte mail (celui de Hostinger).';
        }
        if ($values['kind'] === 'alias' && (!in_array($values['box'], $boxes, true) || $values['box'] === $values['email'])) {
            $errors['box'] = 'Choisissez la boîte dont cette adresse est un alias.';
        }
        if ($values['kind'] === 'alias' && $row !== null && $row['box'] === '' && Db::run('SELECT 1 FROM mail_senders WHERE box = ?', [$row['email']])->fetchColumn()) {
            $errors['kind'] = 'D\'autres adresses sont des alias de cette boîte : elle doit rester une boîte.';
        }
        // Boîte au mot de passe ou à l'adresse changés : une connexion à Hostinger avant d'enregistrer.
        $verify = $errors === [] && $values['kind'] === 'boite'
            && ($password !== '' || $row === null || $row['box'] !== '' || $row['email'] !== $values['email']);
        $check = $verify ? Mailer::check($values['email'], $password !== '' ? $password : Mailer::storedPassword((string) $row['password'])) : null;
        $unreachable = $check !== null && str_starts_with($check, 'connexion impossible');
        if ($check !== null && !$unreachable) {
            $errors['password'] = Mailer::explainCheck($check) . ' : rien n\'a été enregistré. Vérifiez le mot de passe en vous connectant à webmail.hostinger.com avec cette adresse, puis saisissez-le ici.';
        }
        if ($errors !== []) {
            self::form('sender', $values, $errors, ['row' => $row]);
            return;
        }
        $box = $values['kind'] === 'alias' ? $values['box'] : '';
        $sealed = $values['kind'] === 'alias' ? '' : ($password !== '' ? Mailer::seal($password) : (string) $row['password']);
        if ($row !== null) {
            Db::run('UPDATE mail_senders SET email = ?, box = ?, password = ?, updated_at = CURRENT_TIMESTAMP WHERE rowid = ?', [$values['email'], $box, $sealed, $row['id']]);
            if ($row['email'] !== $values['email']) {
                Db::run('UPDATE mail_senders SET box = ? WHERE box = ?', [$values['email'], $row['email']]);
            }
        } else {
            Db::run('INSERT INTO mail_senders (email, box, password) VALUES (?, ?, ?)', [$values['email'], $box, $sealed]);
        }
        if ($verify) {
            Db::run('UPDATE mail_senders SET checked_at = CURRENT_TIMESTAMP, check_error = ? WHERE email = ?', [(string) $check, $values['email']]);
        }
        Auth::log('emails.expediteur', $values['email']);
        match (true) {
            $unreachable => Session::flash($values['email'] . ' enregistrée, mais le serveur d\'envoi de Hostinger ne répond pas : la connexion n\'a pas pu être vérifiée. Réessayez avec « Tester » plus tard.', 'error'),
            $verify => Session::flash($values['email'] . ' enregistrée : Hostinger accepte l\'adresse et le mot de passe, la boîte est connectée.'),
            default => Session::flash($values['email'] . ' enregistrée.'),
        };
        redirect('/admin/emails#boites');
    }

    public static function senderDelete(string $id): void
    {
        Auth::require('admin');
        Csrf::check();
        $row = self::findSender($id);
        $uses = self::uses()[strtolower($row['email'])] ?? [];
        if ($uses !== []) {
            Session::flash($row['email'] . ' sert encore à : ' . self::usesText($uses) . '. Choisissez d\'abord une autre adresse pour ces envois.', 'error');
        } elseif (Db::run('SELECT 1 FROM mail_senders WHERE box = ?', [$row['email']])->fetchColumn()) {
            Session::flash('D\'autres adresses sont des alias de ' . $row['email'] . ' : supprimez-les ou rattachez-les à une autre boîte d\'abord.', 'error');
        } else {
            Db::run('DELETE FROM mail_senders WHERE rowid = ?', [$row['id']]);
            Auth::log('emails.expediteur.suppression', $row['email']);
            Session::flash($row['email'] . ' retirée de la liste.');
        }
        redirect('/admin/emails#boites');
    }

    /** E-mail de test envoyé à l'administrateur connecté, depuis l'adresse choisie. */
    public static function senderTest(string $id): void
    {
        $me = Auth::require('admin');
        Csrf::check();
        $row = self::findSender($id);
        if (empty($me['email'])) {
            Session::flash('Votre compte n\'a pas d\'adresse e-mail : ajoutez-en une dans Comptes pour recevoir le test.', 'error');
        } elseif (Mailer::send((string) $me['email'], 'Test d\'envoi depuis ' . $row['email'], "Bonjour,\n\nCet e-mail confirme que le site ess29.org peut envoyer des messages depuis {$row['email']}.\n\n" . setting('raison_sociale') . "\n", from: $row['email'])) {
            Session::flash('E-mail de test envoyé depuis ' . $row['email'] . ' à ' . $me['email'] . '. Vérifiez votre boîte de réception (et les indésirables).');
        } else {
            Session::flash(Mailer::explain(), 'error');
        }
        redirect('/admin/emails#boites');
    }

    // ---- Envois du site

    public static function siteForm(): void
    {
        Auth::require('admin');
        self::form('site', ['email_envoi' => setting('email_envoi'), 'email_comptes' => setting('email_comptes')], []);
    }

    public static function siteSave(): void
    {
        Auth::require('admin');
        Csrf::check();
        $values = ['email_envoi' => trim((string) ($_POST['email_envoi'] ?? '')), 'email_comptes' => trim((string) ($_POST['email_comptes'] ?? ''))];
        $errors = [];
        foreach ($values as $key => $email) {
            if (!self::isSender($email) && ($email === '' || $email !== setting($key))) {
                $errors[$key] = 'Choisissez une des adresses de la liste « Boîtes et alias d\'envoi ».';
            }
        }
        if ($errors !== []) {
            self::form('site', $values, $errors);
            return;
        }
        foreach ($values as $key => $value) {
            Db::run('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP', [$key, $value]);
        }
        Auth::log('emails.site', implode(', ', $values));
        Session::flash('Envois du site enregistrés.');
        redirect('/admin/emails#site');
    }

    // ---- Organismes

    public static function orgForm(string $id): void
    {
        $key = $id;
        Auth::require('admin');
        $org = self::findOrg($key);
        $values = ['contact' => Orgs::address($key, 'contact'), 'reponse' => Orgs::address($key, 'reponse'), 'public' => self::publicEmail($org)];
        foreach (Orgs::lists([$key], false) as $l) {
            $values['open_' . $l['audience']] = (int) $l['is_open'];
            $values['sender_' . $l['audience']] = $l['sender_email'];
            $values['mode_' . $l['audience']] = $l['sympa_address'] !== '' ? 'sympa' : 'site';
            $values['sympa_' . $l['audience']] = $l['sympa_address'];
        }
        self::form('org', $values, [], ['org' => $org]);
    }

    public static function orgSave(string $id): void
    {
        $key = $id;
        Auth::require('admin');
        Csrf::check();
        $org = self::findOrg($key);
        $values = [
            'contact' => trim((string) ($_POST['contact'] ?? '')),
            'reponse' => trim((string) ($_POST['reponse'] ?? '')),
            'public' => trim((string) ($_POST['public'] ?? '')),
        ];
        foreach (array_keys(Orgs::AUDIENCES) as $a) {
            $values['open_' . $a] = !empty($_POST['open_' . $a]) ? 1 : 0;
            $values['sender_' . $a] = trim((string) ($_POST['sender_' . $a] ?? ''));
            $values['mode_' . $a] = ($_POST['mode_' . $a] ?? '') === 'sympa' ? 'sympa' : 'site';
            $values['sympa_' . $a] = $values['mode_' . $a] === 'sympa' ? strtolower(trim((string) ($_POST['sympa_' . $a] ?? ''))) : '';
        }
        $errors = [];
        foreach (['contact', 'reponse'] as $field) {
            if ($values[$field] !== '' && !filter_var($values[$field], FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = 'Adresse e-mail invalide.';
            }
        }
        if ($key !== 'tag29' && !filter_var($values['public'], FILTER_VALIDATE_EMAIL)) {
            $errors['public'] = 'Adresse e-mail invalide : elle est affichée sur le site.';
        }
        foreach (array_keys(Orgs::AUDIENCES) as $a) {
            if ($values['mode_' . $a] === 'sympa' && !filter_var($values['sympa_' . $a], FILTER_VALIDATE_EMAIL)) {
                $errors['sympa_' . $a] = 'Indiquez l\'adresse de la liste Sympa, par exemple adherents-paysdebrest@ess29.com.';
            }
            $saved = (string) (Db::run('SELECT sender_email FROM newsletter_lists WHERE org = ? AND audience = ?', [$key, $a])->fetchColumn() ?: '');
            if ($values['sender_' . $a] !== '' && !self::isSender($values['sender_' . $a]) && $values['sender_' . $a] !== $saved) {
                $errors['sender_' . $a] = 'Choisissez une des adresses de la liste « Boîtes et alias d\'envoi ».';
            }
        }
        if ($errors !== []) {
            self::form('org', $values, $errors, ['org' => $org]);
            return;
        }
        foreach (['contact', 'reponse'] as $purpose) {
            Db::run('INSERT INTO mail_addresses (org, purpose, email) VALUES (?, ?, ?) ON CONFLICT(org, purpose) DO UPDATE SET email = excluded.email', [$key, $purpose, $values[$purpose]]);
        }
        foreach (array_keys(Orgs::AUDIENCES) as $a) {
            Db::run('UPDATE newsletter_lists SET sender_email = ?, sympa_address = ?, is_open = ? WHERE org = ? AND audience = ?', [$values['sender_' . $a], $values['sympa_' . $a], $values['open_' . $a], $key, $a]);
        }
        if ($org['pole_id'] !== null) {
            Db::run('UPDATE poles SET email = ? WHERE id = ?', [$values['public'], $org['pole_id']]);
        } elseif ($key === 'ess29') {
            Db::run("UPDATE settings SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE key = 'email_contact'", [$values['public']]);
        }
        Auth::log('emails.organisme', $key);
        Session::flash('Adresses de ' . $org['name'] . ' enregistrées.');
        redirect('/admin/emails#org-' . $key);
    }

    // ---- Outils

    /**
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     * @param array<string, mixed> $extra
     */
    private static function form(string $kind, array $values, array $errors, array $extra = []): void
    {
        if ($errors !== []) {
            http_response_code(422);
        }
        $title = match ($kind) {
            'sender' => isset($extra['row']) ? 'Modifier : ' . $extra['row']['email'] : 'Ajouter une boîte ou un alias',
            'site' => 'Envois du site',
            default => (string) $extra['org']['name'],
        };
        View::render('admin/emails/form', [
            'title' => $title,
            'kind' => $kind,
            'values' => $values,
            'errors' => $errors,
            'senders' => self::senders(),
        ] + $extra, 'admin/layout');
    }

    /** @return list<array<string, mixed>> boîtes puis alias, par adresse */
    private static function senders(): array
    {
        return Db::run("SELECT rowid AS id, email, box, password, checked_at, check_error FROM mail_senders ORDER BY box <> '', COALESCE(NULLIF(box, ''), email), email")->fetchAll();
    }

    /** @return array<string, mixed> */
    private static function findSender(string $id): array
    {
        $row = Db::run('SELECT rowid AS id, email, box, password, checked_at, check_error FROM mail_senders WHERE rowid = ?', [(int) $id])->fetch();
        if (!$row) {
            http_response_code(404);
            exit('Adresse introuvable.');
        }
        return $row;
    }

    private static function isSender(string $email): bool
    {
        return $email !== '' && (bool) Db::run('SELECT 1 FROM mail_senders WHERE email = ?', [$email])->fetchColumn();
    }

    /**
     * Ce que chaque adresse d'expédition envoie, et pour qui : affiché sur la page et vérifié avant une suppression.
     * Clés en minuscules. « org » : nom de l'organisme concerné, null pour un envoi de tout le site.
     * @return array<string, list<array{what: string, org: ?string, rank: int}>>
     */
    private static function uses(): array
    {
        $uses = [];
        if (setting('email_envoi') !== '') {
            $uses[strtolower(setting('email_envoi'))][] = ['what' => 'E-mails automatiques', 'org' => null, 'rank' => 0];
        }
        if (setting('email_comptes') !== '') {
            $uses[strtolower(setting('email_comptes'))][] = ['what' => 'Comptes du tableau de bord', 'org' => null, 'rank' => 0];
        }
        $rank = array_flip(array_keys(Orgs::all()));
        foreach (Orgs::lists(null, false) as $l) {
            if ($l['sender_email'] !== '' && $l['is_open']) {
                $uses[strtolower($l['sender_email'])][] = ['what' => $l['audience'] === 'adherents' ? 'Newsletter adhérents' : 'Newsletter inscrits via le site', 'org' => $l['org_name'], 'rank' => 1 + $rank[$l['org']]];
            }
        }
        return $uses;
    }

    /** @param list<array{what: string, org: ?string, rank: int}> $uses */
    public static function usesText(array $uses): string
    {
        return implode(', ', array_map(static fn (array $u): string => $u['what'] . ($u['org'] !== null ? ' (' . $u['org'] . ')' : ''), $uses));
    }

    /** @return array<string, mixed> */
    private static function findOrg(string $key): array
    {
        $org = Orgs::get($key);
        if ($org === null) {
            http_response_code(404);
            exit('Organisme introuvable.');
        }
        return $org;
    }

    /** Adresse affichée sur le site : celle du pôle, l'adresse générique pour ESS29 ; TAg29 n'en affiche pas. @param array<string, mixed> $org */
    private static function publicEmail(array $org): string
    {
        return $org['key'] === 'tag29' ? '' : (string) $org['email'];
    }
}
