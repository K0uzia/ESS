<?php
declare(strict_types=1);

namespace App\Core;

/** Anti-robots des formulaires publics sans session : pot de miel, délai minimal signé, limite de débit. */
final class FormGuard
{
    private const MIN_SECONDS = 3;
    private const MAX_AGE = 7200;

    /** $form : identifiant du formulaire, pour un id unique quand plusieurs formulaires sont sur la même page. */
    public static function fields(string $form): string
    {
        $ts = (string) time();
        $id = 'site_web-' . $form;
        return '<input type="hidden" name="_ts" value="' . $ts . '.' . self::sign($ts) . '">'
            . '<div class="hp" aria-hidden="true"><label for="' . $id . '">Ne pas remplir</label>'
            . '<input id="' . $id . '" name="site_web" type="text" tabindex="-1" autocomplete="off"></div>';
    }

    /** Vrai si la soumission semble humaine. Un robot reçoit la même réponse qu'un humain, sans effet. */
    public static function passes(): bool
    {
        if (($_POST['site_web'] ?? '') !== '') {
            return false;
        }
        [$ts, $sig] = array_pad(explode('.', (string) ($_POST['_ts'] ?? ''), 2), 2, '');
        if (!ctype_digit($ts) || !hash_equals(self::sign($ts), $sig)) {
            return false;
        }
        $age = time() - (int) $ts;
        return $age >= self::MIN_SECONDS && $age <= self::MAX_AGE;
    }

    /**
     * Vrai si la page a été ouverte trop longtemps avant l'envoi (signature valide, pot de miel vide) :
     * la personne doit pouvoir renvoyer sa saisie au lieu de la perdre sans le savoir (RGAA 13.1).
     */
    public static function expired(): bool
    {
        [$ts, $sig] = array_pad(explode('.', (string) ($_POST['_ts'] ?? ''), 2), 2, '');
        return ($_POST['site_web'] ?? '') === '' && ctype_digit($ts) && hash_equals(self::sign($ts), $sig) && time() - (int) $ts > self::MAX_AGE;
    }

    public static function rateLimited(string $bucket, int $max, int $minutes): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        Db::run("DELETE FROM rate_limits WHERE created_at < datetime('now', '-1 day')");
        $count = (int) Db::run(
            "SELECT COUNT(*) FROM rate_limits WHERE bucket = ? AND ip = ? AND created_at >= datetime('now', ?)",
            [$bucket, $ip, "-$minutes minutes"]
        )->fetchColumn();
        if ($count >= $max) {
            return true;
        }
        Db::run('INSERT INTO rate_limits (bucket, ip) VALUES (?, ?)', [$bucket, $ip]);
        return false;
    }

    private static function sign(string $value): string
    {
        return hash_hmac('sha256', $value, self::key());
    }

    /** APP_KEY du .env, sinon clé locale générée une fois dans data/ (jamais versionnée). */
    public static function key(): string
    {
        $key = $_ENV['APP_KEY'] ?? '';
        if ($key !== '') {
            return $key;
        }
        $file = ROOT . '/data/app.key';
        if (!is_file($file)) {
            is_dir(dirname($file)) || mkdir(dirname($file), 0775, true);
            file_put_contents($file, bin2hex(random_bytes(32)));
            chmod($file, 0600);
        }
        return trim((string) file_get_contents($file));
    }
}
