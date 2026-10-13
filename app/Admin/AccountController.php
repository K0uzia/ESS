<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Session;
use App\Core\View;

final class AccountController
{
    public static function index(): void
    {
        Auth::require('admin');
        $accounts = Db::run(
            'SELECT u.id, u.username, u.email, u.role, u.last_login_at, p.name AS pole
             FROM users u LEFT JOIN poles p ON p.id = u.pole_id ORDER BY u.username'
        )->fetchAll();
        View::render('admin/accounts/index', ['title' => 'Comptes', 'accounts' => $accounts], 'admin/layout');
    }

    public static function create(): void
    {
        Auth::require('admin');
        self::form(null, []);
    }

    public static function store(): void
    {
        Auth::require('admin');
        Csrf::check();
        $input = self::input();
        $errors = self::validate($input, null);
        $password = (string) ($_POST['password'] ?? '');
        if (text_length($password) < Auth::MIN_PASSWORD_LENGTH) {
            $errors['password'] = 'Au moins ' . Auth::MIN_PASSWORD_LENGTH . ' caractères.';
        }
        if ($errors !== []) {
            self::form(null, $errors, $input);
            return;
        }
        Db::run(
            'INSERT INTO users (username, email, role, pole_id, password_hash) VALUES (?, ?, ?, ?, ?)',
            [$input['username'], $input['email'], $input['role'], $input['pole_id'], Auth::hash($password)]
        );
        Auth::log('compte.creation', $input['username']);
        Session::flash('Compte « ' . $input['username'] . ' » créé.');
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
        // Mot de passe : facultatif depuis la fiche ; l'ancien est demandé pour son propre compte.
        $password = (string) ($_POST['password'] ?? '');
        if ($password !== '') {
            if (self::isOwn($account)) {
                $hash = (string) Db::run('SELECT password_hash FROM users WHERE id = ?', [$account['id']])->fetchColumn();
                if (!password_verify((string) ($_POST['current_password'] ?? ''), $hash)) {
                    $errors['current_password'] = 'Mot de passe actuel incorrect.';
                }
            }
            if (text_length($password) < Auth::MIN_PASSWORD_LENGTH) {
                $errors['password'] = 'Au moins ' . Auth::MIN_PASSWORD_LENGTH . ' caractères.';
            } elseif ($password !== ($_POST['password_confirm'] ?? '')) {
                $errors['password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
            }
        }
        if ($errors !== []) {
            self::form($account, $errors, $input);
            return;
        }
        Db::run(
            'UPDATE users SET username = ?, email = ?, role = ?, pole_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?',
            [$input['username'], $input['email'], $input['role'], $input['pole_id'], $account['id']]
        );
        if ($password !== '') {
            Db::run('UPDATE users SET password_hash = ? WHERE id = ?', [Auth::hash($password), $account['id']]);
            Auth::log('compte.mot_de_passe', $input['username']);
        }
        Auth::log('compte.modification', $input['username']);
        Session::flash('Compte « ' . $input['username'] . ' » mis à jour' . ($password !== '' ? ', mot de passe changé.' : '.'));
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
            Session::flash('Compte « ' . $account['username'] . ' » supprimé.');
        }
        redirect('/admin/comptes');
    }

    /** Un administrateur réinitialise le mot de passe d'un autre compte ; chacun change le sien avec l'ancien. */
    public static function passwordForm(string $id): void
    {
        $account = self::authorizePassword($id);
        if (Auth::user()['role'] === 'admin') {
            redirect('/admin/comptes/' . (int) $account['id'] . '#mot-de-passe');
        }
        View::render('admin/accounts/password', [
            'title' => 'Mot de passe',
            'account' => $account,
            'own' => self::isOwn($account),
            'errors' => [],
        ], 'admin/layout');
    }

    public static function passwordUpdate(string $id): void
    {
        $account = self::authorizePassword($id);
        Csrf::check();
        $own = self::isOwn($account);
        $errors = [];
        $password = (string) ($_POST['password'] ?? '');
        if ($own) {
            $hash = (string) Db::run('SELECT password_hash FROM users WHERE id = ?', [$account['id']])->fetchColumn();
            if (!password_verify((string) ($_POST['current_password'] ?? ''), $hash)) {
                $errors['current_password'] = 'Mot de passe actuel incorrect.';
            }
        }
        if (text_length($password) < Auth::MIN_PASSWORD_LENGTH) {
            $errors['password'] = 'Au moins ' . Auth::MIN_PASSWORD_LENGTH . ' caractères.';
        } elseif ($password !== ($_POST['password_confirm'] ?? '')) {
            $errors['password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
        }
        if ($errors !== []) {
            http_response_code(422);
            View::render('admin/accounts/password', [
                'title' => 'Mot de passe', 'account' => $account, 'own' => $own, 'errors' => $errors,
            ], 'admin/layout');
            return;
        }
        Db::run('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [Auth::hash($password), $account['id']]);
        Auth::log('compte.mot_de_passe', $account['username']);
        Session::flash('Mot de passe modifié.');
        redirect(Auth::user()['role'] === 'admin' ? '/admin/comptes' : '/admin/');
    }

    /** @return array<string, mixed> */
    private static function authorizePassword(string $id): array
    {
        $me = Auth::require();
        $account = self::find($id);
        if ($me['role'] !== 'admin' && (int) $account['id'] !== (int) $me['id']) {
            http_response_code(403);
            exit('Accès refusé.');
        }
        return $account;
    }

    /** @param array<string, mixed> $account */
    private static function isOwn(array $account): bool
    {
        return (int) $account['id'] === (int) Auth::user()['id'];
    }

    /** @return array<string, mixed> */
    private static function find(string $id): array
    {
        $account = Db::run('SELECT id, username, email, role, pole_id FROM users WHERE id = ?', [(int) $id])->fetch();
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

    /** @return array{username: string, email: ?string, role: string, pole_id: ?int} */
    private static function input(): array
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $pole = (int) ($_POST['pole_id'] ?? 0);
        return [
            'username' => trim((string) ($_POST['username'] ?? '')),
            'email' => $email === '' ? null : $email,
            'role' => ($_POST['role'] ?? '') === 'editor' ? 'editor' : 'admin',
            'pole_id' => $pole > 0 ? $pole : null,
        ];
    }

    /**
     * @param array{username: string, email: ?string, role: string, pole_id: ?int} $input
     * @return array<string, string>
     */
    private static function validate(array $input, ?int $ignoreId): array
    {
        $errors = [];
        if (!preg_match('/^[a-z0-9._-]{3,40}$/i', $input['username'])) {
            $errors['username'] = 'De 3 à 40 caractères : lettres, chiffres, point, tiret ou tiret bas.';
        } elseif (Db::run('SELECT 1 FROM users WHERE username = ? AND id IS NOT ?', [$input['username'], $ignoreId])->fetchColumn()) {
            $errors['username'] = 'Cet identifiant est déjà utilisé.';
        }
        if ($input['email'] !== null) {
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Adresse e-mail invalide.';
            } elseif (Db::run('SELECT 1 FROM users WHERE email = ? AND id IS NOT ?', [$input['email'], $ignoreId])->fetchColumn()) {
                $errors['email'] = 'Cette adresse est déjà utilisée par un autre compte.';
            }
        }
        if ($input['role'] === 'editor' && $input['pole_id'] === null) {
            $errors['pole_id'] = 'Un éditeur est rattaché à un pôle.';
        }
        if ($input['pole_id'] !== null && !Db::run('SELECT 1 FROM poles WHERE id = ?', [$input['pole_id']])->fetchColumn()) {
            $errors['pole_id'] = 'Pôle inconnu.';
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
            'values' => $input ?? $account ?? ['username' => '', 'email' => '', 'role' => 'admin', 'pole_id' => null],
            'errors' => $errors,
            'poles' => Db::run('SELECT id, name FROM poles ORDER BY position')->fetchAll(),
        ], 'admin/layout');
    }
}
