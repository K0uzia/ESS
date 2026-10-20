<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;

final class AccountController
{
    public static function index(): void
    {
        Auth::require('admin');
        $accounts = Db::run(
            'SELECT u.id, u.name, u.username, u.email, u.role, u.org, u.last_login_at, p.name AS pole
             FROM users u LEFT JOIN poles p ON p.id = u.pole_id ORDER BY u.name COLLATE NOCASE'
        )->fetchAll();
        View::render('admin/accounts/index', ['title' => 'Comptes', 'accounts' => $accounts], 'admin/layout');
    }

    public static function create(): void
    {
        Auth::require('admin');
        self::form(null, []);
    }

    /** Le compte est créé sans mot de passe : la personne le choisit elle-même par le lien d'invitation reçu par e-mail. */
    public static function store(): void
    {
        Auth::require('admin');
        Csrf::check();
        $input = self::input();
        $errors = self::validate($input, null);
        if ($errors !== []) {
            self::form(null, $errors, $input);
            return;
        }
        Db::run(
            'INSERT INTO users (username, name, email, role, pole_id, org, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$input['email'], $input['name'], $input['email'], $input['role'], $input['pole_id'], $input['org'], Auth::unusableHash()]
        );
        $account = self::find((string) Db::pdo()->lastInsertId());
        Auth::log('compte.creation', $input['email']);
        if (self::sendLink($account, 'invitation')) {
            Session::flash('Compte de ' . $input['name'] . ' créé. Une invitation a été envoyée à ' . $input['email'] . ' pour choisir le mot de passe.');
        } else {
            Session::flash('Compte de ' . $input['name'] . ' créé, mais l\'invitation n\'a pas pu partir : ' . Mailer::explain() . ' Renvoyez-la ensuite depuis la fiche du compte.', 'error');
        }
        redirect('/admin/comptes');
    }

    public static function edit(string $id): void
    {
        Auth::require('admin');
        self::form(self::find($id), []);
    }

    public static function update(string $id): void
    {
        Auth::require('admin');
        Csrf::check();
        $account = self::find($id);
        $input = self::input();
        $errors = self::validate($input, (int) $account['id']);
        if ($input['role'] !== 'admin' && $account['role'] === 'admin' && self::adminCount() <= 1) {
            $errors['role'] = 'Il doit rester au moins un administrateur.';
        }
        if ($errors !== []) {
            self::form($account, $errors, $input);
            return;
        }
        Db::run(
            // L'identifiant technique suit l'e-mail, sauf pour le compte provisoire « lacapsule ».
            'UPDATE users SET name = ?, email = ?, role = ?, pole_id = ?, org = ?, username = CASE WHEN username = email THEN ? ELSE username END, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$input['name'], $input['email'], $input['role'], $input['pole_id'], $input['org'], $input['email'], $account['id']]
        );
        Auth::log('compte.modification', $input['email']);
        Session::flash('Compte de ' . $input['name'] . ' mis à jour.');
        redirect('/admin/comptes');
    }

    public static function destroy(string $id): void
    {
        $me = Auth::require('admin');
        Csrf::check();
        $account = self::find($id);
        if ((int) $account['id'] === (int) $me['id']) {
            Session::flash('Vous ne pouvez pas supprimer votre propre compte.', 'error');
        } elseif ($account['role'] === 'admin' && self::adminCount() <= 1) {
            Session::flash('Il doit rester au moins un administrateur.', 'error');
        } else {
            Db::run('DELETE FROM users WHERE id = ?', [$account['id']]);
            Auth::log('compte.suppression', $account['username']);
            Session::flash('Compte de ' . $account['name'] . ' supprimé.');
        }
        redirect('/admin/comptes');
    }

    /**
     * Changement de mot de passe : seul un administrateur l'envoie, par un lien à l'adresse du compte
     * (décision du 14 octobre 2026 : les comptes de pôle et TAg29 passent par l'administrateur).
     */
    public static function passwordForm(string $id): void
    {
        Auth::require('admin');
        redirect('/admin/comptes/' . (int) self::find($id)['id'] . '#mot-de-passe');
    }

    public static function passwordUpdate(string $id): void
    {
        Auth::require('admin');
        Csrf::check();
        $account = self::find($id);
        if (empty($account['email'])) {
            Session::flash('Ce compte n\'a pas d\'adresse e-mail : ajoutez-en une, puis envoyez le lien.', 'error');
        } elseif (self::sendLink($account, $account['last_login_at'] === null ? 'invitation' : 'reinitialisation')) {
            Auth::log('compte.lien_mot_de_passe', $account['username']);
            Session::flash('Lien envoyé à ' . $account['email'] . '. Le mot de passe actuel reste valable tant que le nouveau n\'est pas choisi.');
        } else {
            Session::flash(Mailer::explain(), 'error');
        }
        redirect('/admin/comptes');
    }

    /**
     * Envoie un lien pour choisir le mot de passe (invitation d'un nouveau compte ou changement).
     * @param array<string, mixed> $account
     */
    public static function sendLink(array $account, string $purpose): bool
    {
        $token = Auth::issueLink((int) $account['id'], $purpose);
        $base = ($_ENV['APP_ENV'] ?? '') === 'local' ? ($_ENV['APP_URL'] ?? 'http://localhost:8080') : setting('site_url');
        $link = rtrim($base, '/') . '/admin/mot-de-passe?jeton=' . $token;
        $hours = Auth::LINK_HOURS[$purpose];
        $validity = $hours >= 48 ? intdiv($hours, 24) . ' jours' : $hours . ' heures';
        if ($purpose === 'invitation') {
            $subject = 'Votre accès au tableau de bord ess29.org';
            $text = "Bonjour,\n\nUn compte a été créé pour vous sur le tableau de bord du site ess29.org.\n"
                . "Adresse de connexion : {$account['email']}\n\n"
                . "Pour choisir votre mot de passe et activer le compte, ouvrez ce lien (valable $validity) :\n$link\n\n"
                . "Si vous n'attendiez pas ce message, ignorez-le.\n\n";
        } else {
            $subject = 'Changement de votre mot de passe ess29.org';
            $text = "Bonjour,\n\nUn changement de mot de passe a été demandé pour votre compte du tableau de bord ess29.org.\n\n"
                . "Pour choisir un nouveau mot de passe, ouvrez ce lien (valable $validity) :\n$link\n\n"
                . "Si vous n'êtes pas à l'origine de cette demande, ignorez ce message : votre mot de passe actuel reste valable.\n\n";
        }
        return Mailer::send((string) $account['email'], $subject, $text . setting('raison_sociale') . "\n", from: setting('email_comptes') ?: null);
    }

    /** @param array<string, mixed> $account */
    private static function isOwn(array $account): bool
    {
        return (int) $account['id'] === (int) Auth::user()['id'];
    }

    /** @return array<string, mixed> */
    private static function find(string $id): array
    {
        $account = Db::run('SELECT id, name, username, email, role, pole_id, org, last_login_at FROM users WHERE id = ?', [(int) $id])->fetch();
        if (!$account) {
            http_response_code(404);
            exit('Compte introuvable.');
        }
        return $account;
    }

    private static function adminCount(): int
    {
        return (int) Db::run("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    }

    /**
     * Rôle « admin » (tout, sans organisme) ou « editor » ; un éditeur est rattaché à un organisme (champ « organisme » :
     * « ess29 », « tag29 » ou « pole:<id> »), ignoré pour un administrateur.
     * @return array{name: string, email: ?string, role: string, pole_id: ?int, org: ?string, organisme: string}
     */
    private static function input(): array
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = ($_POST['role'] ?? '') === 'editor' ? 'editor' : 'admin';
        $organisme = $role === 'editor' ? (string) ($_POST['organisme'] ?? '') : '';
        return [
            'name' => trim((string) preg_replace('/\s+/u', ' ', (string) ($_POST['name'] ?? ''))),
            'email' => $email === '' ? null : $email,
            'role' => $role,
            'pole_id' => str_starts_with($organisme, 'pole:') ? (int) substr($organisme, 5) : null,
            'org' => in_array($organisme, ['ess29', 'tag29'], true) ? $organisme : null,
            'organisme' => $organisme,
        ];
    }

    /**
     * @param array{name: string, email: ?string, role: string, pole_id: ?int} $input
     * @return array<string, string>
     */
    private static function validate(array $input, ?int $ignoreId): array
    {
        $errors = [];
        if ($input['name'] === '' || text_length($input['name']) > 80) {
            $errors['name'] = 'Indiquez le prénom et le nom.';
        }
        if ($input['email'] === null) {
            $errors['email'] = 'Indiquez l\'adresse e-mail : elle sert à se connecter et à recevoir les liens de mot de passe.';
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse e-mail invalide.';
        } elseif (Db::run('SELECT 1 FROM users WHERE (email = ? OR username = ?) AND id IS NOT ?', [$input['email'], $input['email'], $ignoreId])->fetchColumn()) {
            $errors['email'] = 'Cette adresse est déjà utilisée par un autre compte.';
        }
        if ($input['role'] === 'editor' && $input['org'] === null && ($input['pole_id'] === null || !Db::run('SELECT 1 FROM poles WHERE id = ?', [$input['pole_id']])->fetchColumn())) {
            $errors['organisme'] = 'Choisissez l\'organisme de cet éditeur : ESS29, TAg29 ou un pôle.';
        }
        return $errors;
    }

    /**
     * @param array<string, mixed>|null $account
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $input
     */
    private static function form(?array $account, array $errors, ?array $input = null): void
    {
        if ($errors !== []) {
            http_response_code(422);
        }
        View::render('admin/accounts/form', [
            'title' => $account === null ? 'Nouveau compte' : 'Modifier le compte',
            'account' => $account,
            'own' => $account !== null && self::isOwn($account),
            'values' => $input ?? $account ?? ['name' => '', 'email' => '', 'role' => 'admin', 'pole_id' => null, 'org' => null],
            'errors' => $errors,
            'poles' => Db::run('SELECT id, name FROM poles ORDER BY position')->fetchAll(),
        ], 'admin/layout');
    }
}
