<?php
declare(strict_types=1);

namespace App\Core;

final class Auth
{
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;
    public const MIN_PASSWORD_LENGTH = 12;
    /** Durée de validité des liens envoyés par e-mail. */
    public const LINK_HOURS = ['invitation' => 168, 'reinitialisation' => 2];

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

        // Connexion par e-mail ; l'identifiant reste accepté pour le compte provisoire « lacapsule », qui n'a pas d'e-mail.
        $user = Db::run('SELECT id, password_hash FROM users WHERE email = ? OR username = ?', [$username, $username])->fetch();
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
                : (Db::run('SELECT id, name, username, email, role, pole_id FROM users WHERE id = ?', [$id])->fetch() ?: null);
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

    /**
     * Périmètre d'un compte : « all » (administrateur), « pole » (éditeur d'un pôle) ou « tag29 »
     * (éditeur sans pôle : page Tag29, équipe TAg29 et médiathèque seulement).
     * @param array<string, mixed> $user
     */
    public static function scope(array $user): string
    {
        return $user['role'] === 'admin' ? 'all' : ($user['pole_id'] === null ? 'tag29' : 'pole');
    }

    /** Refuse l'accès d'un éditeur TAg29 à un module qui ne le concerne pas. */
    public static function forbidTag29(array $user): void
    {
        if (self::scope($user) === 'tag29') {
            http_response_code(403);
            exit('Accès réservé aux pôles et aux administrateurs.');
        }
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /** Mot de passe impossible à deviner, en attendant que la personne choisisse le sien par le lien reçu. */
    public static function unusableHash(): string
    {
        return self::hash(bin2hex(random_bytes(32)));
    }

    /** Nouveau lien de mot de passe ; les liens précédents de ce compte cessent de fonctionner. Renvoie le jeton en clair. */
    public static function issueLink(int $userId, string $purpose): string
    {
        $token = bin2hex(random_bytes(32));
        Db::run('DELETE FROM password_tokens WHERE user_id = ?', [$userId]);
        Db::run(
            "INSERT INTO password_tokens (user_id, token_hash, purpose, expires_at) VALUES (?, ?, ?, datetime('now', ?))",
            [$userId, hash('sha256', $token), $purpose, '+' . self::LINK_HOURS[$purpose] . ' hours']
        );
        return $token;
    }

    /** @return array<string, mixed>|null compte et motif du lien, s'il est valide et non utilisé */
    public static function findLink(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $row = Db::run(
            "SELECT t.id AS token_id, t.purpose, u.id, u.name, u.username, u.email FROM password_tokens t JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > datetime('now')",
            [hash('sha256', $token)]
        )->fetch();
        return $row ?: null;
    }

    /** @param array<string, mixed> $link résultat de findLink() */
    public static function completeLink(array $link, string $password): void
    {
        Db::run('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?', [self::hash($password), $link['id']]);
        Db::run('DELETE FROM password_tokens WHERE user_id = ?', [$link['id']]);
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
