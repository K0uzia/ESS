<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;
    public const MIN_PASSWORD_LENGTH = 12;

    /** @var array<string, mixed>|null|false */
    private static array|null|false $user = false;

    public static function attempt(string $username, string $password): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        Db::run("DELETE FROM login_attempts WHERE attempted_at < datetime('now', ?)", ['-' . self::WINDOW_MINUTES . ' minutes']);
        $count = (int) Db::run('SELECT COUNT(*) FROM login_attempts WHERE ip = ?', [$ip])->fetchColumn();
        if ($count >= self::MAX_ATTEMPTS) {
            return false;
        }

        $user = Db::run('SELECT id, password_hash FROM users WHERE username = ?', [$username])->fetch();
        // Hash factice si l'utilisateur n'existe pas : même durée de réponse dans les deux cas.
        $hash = $user['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$dzAvWElxVEhiQU5LNzNmYw$ghniolJ7ojvXLWFMNgI1OhfDivYnbsyOuOceBVHt498';
        if (!$user || !password_verify($password, $hash)) {
            Db::run('INSERT INTO login_attempts (ip) VALUES (?)', [$ip]);
            return false;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
            Db::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_ARGON2ID), $user['id']]);
        }
        Db::run('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
        Db::run('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?', [$user['id']]);
        Session::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        self::$user = false;
        self::log('connexion');
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$user === false) {
            $id = $_SESSION['user_id'] ?? null;
            self::$user = $id === null ? null
                : (Db::run('SELECT id, username, email, role, pole_id FROM users WHERE id = ?', [$id])->fetch() ?: null);
        }
        return self::$user;
    }

    /** @return array<string, mixed> */
    public static function require(?string $role = null): array
    {
        $user = self::user();
        if ($user === null) {
            redirect('/admin/connexion');
        }
        if ($role !== null && $user['role'] !== $role) {
            http_response_code(403);
            exit('Accès réservé aux administrateurs.');
        }
        return $user;
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public static function log(string $action, ?string $target = null): void
    {
        Db::run('INSERT INTO audit_log (user_id, action, target) VALUES (?, ?, ?)', [$_SESSION['user_id'] ?? null, $action, $target]);
    }

    /** Le compte initial temporaire a-t-il encore son mot de passe d'origine ? */
    public static function defaultAccountActive(): bool
    {
        $hash = Db::run("SELECT password_hash FROM users WHERE username = 'lacapsule'")->fetchColumn();
        return is_string($hash) && password_verify('lacapsule', $hash);
    }
}
