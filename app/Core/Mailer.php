<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Client SMTP minimal (SSL implicite sur 465, STARTTLS sur 587, AUTH LOGIN).
 * Chaque adresse qui envoie est une boîte Hostinger ou un alias d'une boîte (table mail_senders, page E-mails) ;
 * on se connecte avec la boîte, l'e-mail part au nom de l'adresse. À défaut, la boîte du .env.
 * En local sans boîte, le message est écrit dans data/mail.log au lieu d'être envoyé.
 */
final class Mailer
{
    public const DEFAULT_HOST = 'smtp.hostinger.com';
    public const DEFAULT_PORT = 465;

    /** Cause du dernier échec d'envoi (pour le tableau de bord). */
    public static ?string $lastError = null;

    /**
     * Boîte qui envoie au nom de $from : $from elle-même si c'est une boîte, sinon la boîte dont $from est l'alias.
     * @return array{host: string, port: int, user: string, password: string}|null
     */
    public static function config(string $from): ?array
    {
        $row = Db::run('SELECT email, box, password FROM mail_senders WHERE email = ?', [$from])->fetch();
        if ($row && $row['box'] !== '') {
            $row = Db::run("SELECT email, box, password FROM mail_senders WHERE email = ? AND box = ''", [$row['box']])->fetch();
        }
        // Mot de passe illisible (clé ou extension différente sur ce serveur) : traité comme absent, à saisir à nouveau.
        $password = $row && $row['password'] !== '' ? self::reveal((string) $row['password']) : '';
        if ($password !== '') {
            return ['host' => self::DEFAULT_HOST, 'port' => self::DEFAULT_PORT, 'user' => (string) $row['email'], 'password' => $password];
        }
        if (($_ENV['SMTP_USER'] ?? '') !== '' && ($_ENV['SMTP_HOST'] ?? '') !== '') {
            return [
                'host' => (string) $_ENV['SMTP_HOST'],
                'port' => (int) (($_ENV['SMTP_PORT'] ?? '') ?: self::DEFAULT_PORT),
                'user' => (string) $_ENV['SMTP_USER'],
                'password' => (string) ($_ENV['SMTP_PASSWORD'] ?? ''),
            ];
        }
        return null;
    }

    /**
     * Mot de passe de la boîte chiffré avec la clé de l'application : la base seule ne le révèle pas.
     * AES-256-GCM par OpenSSL (présent partout où l'envoi SMTP chiffré fonctionne), préfixe « o1: ».
     * Les valeurs sans préfixe, chiffrées avec sodium, restent lisibles là où l'extension sodium existe.
     */
    public static function seal(string $password): string
    {
        $iv = random_bytes(12);
        $cipher = openssl_encrypt($password, 'aes-256-gcm', self::secretKey(), OPENSSL_RAW_DATA, $iv, $tag);
        return 'o1:' . base64_encode($iv . $tag . $cipher);
    }

    /** Mot de passe enregistré mais illisible sur ce serveur (autre clé, ou chiffré avec sodium absent ici). */
    public static function unreadable(string $sealed): bool
    {
        return $sealed !== '' && self::reveal($sealed) === '';
    }

    private static function reveal(string $sealed): string
    {
        if (str_starts_with($sealed, 'o1:')) {
            $raw = base64_decode(substr($sealed, 3), true);
            if ($raw === false || strlen($raw) <= 28) {
                return '';
            }
            $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', self::secretKey(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
            return $plain === false ? '' : $plain;
        }
        if (!function_exists('sodium_crypto_secretbox_open')) {
            return '';
        }
        $raw = base64_decode($sealed, true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return '';
        }
        $key = sodium_crypto_generichash('smtp|' . FormGuard::key(), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $key);
        return $plain === false ? '' : $plain;
    }

    private static function secretKey(): string
    {
        return hash('sha256', 'smtp|' . FormGuard::key(), true);
    }

    /** @param array<string, string> $headers */
    public static function send(string $to, string $subject, string $text, ?string $html = null, ?string $replyTo = null, ?string $from = null, array $headers = [], ?string $fromName = null): bool
    {
        $from = (string) ($from ?? setting('email_envoi'));
        $fromName ??= $_ENV['SMTP_FROM_NAME'] ?? 'ESS Finistère';
        $boundary = 'b' . bin2hex(random_bytes(12));
        $domain = substr((string) strrchr($from ?: 'x@ess29.org', '@'), 1);

        $head = [
            'Date' => date(DATE_RFC2822),
            'From' => self::encode($fromName) . " <$from>",
            'To' => "<$to>",
            'Subject' => self::encode($subject),
            'Message-ID' => '<' . bin2hex(random_bytes(16)) . "@$domain>",
            'MIME-Version' => '1.0',
        ] + ($replyTo ? ['Reply-To' => "<$replyTo>"] : []) + $headers;

        if ($html === null) {
            $head['Content-Type'] = 'text/plain; charset=UTF-8';
            $head['Content-Transfer-Encoding'] = 'base64';
            $body = chunk_split(base64_encode($text));
        } else {
            $head['Content-Type'] = "multipart/alternative; boundary=\"$boundary\"";
            $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
                . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
                . "--$boundary--\r\n";
        }
        $message = '';
        foreach ($head as $k => $v) {
            $message .= "$k: $v\r\n";
        }
        $message .= "\r\n" . $body;

        self::$lastError = null;
        $config = $from !== '' ? self::config($from) : null;
        if ($config === null && ($_ENV['APP_ENV'] ?? '') !== 'local') {
            $box = $from !== '' ? Db::run("SELECT COALESCE(NULLIF(box, ''), email) FROM mail_senders WHERE email = ?", [$from])->fetchColumn() : false;
            self::$lastError = $from === '' ? 'aucune adresse d\'expédition' : ($box ? "boîte $box sans mot de passe" : "adresse $from sans boîte");
            error_log('SMTP : ' . self::$lastError);
            return false;
        }
        if ($config === null) {
            $dir = ROOT . '/data';
            is_dir($dir) || mkdir($dir, 0775, true);
            file_put_contents("$dir/mail.log", "=== " . date('c') . " → $to\r\n$message\r\n", FILE_APPEND);
            return true;
        }

        try {
            self::smtp($config, $from, $to, $message);
            self::remember($config['user'], '');
            return true;
        } catch (\RuntimeException $e) {
            self::$lastError = $e->getMessage();
            error_log('SMTP : ' . $e->getMessage());
            // Code 1 : refus avant ou pendant la connexion à la boîte ; au-delà, la boîte est bien connectée.
            self::remember($config['user'], $e->getCode() === 1 ? $e->getMessage() : '');
            return false;
        }
    }

    /**
     * Connexion à la boîte sans rien envoyer (une seule tentative, pour ne pas faire bloquer la boîte).
     * @return string|null cause du refus, null si Hostinger accepte l'adresse et le mot de passe
     */
    public static function check(string $user, string $password): ?string
    {
        try {
            self::smtp(['host' => self::DEFAULT_HOST, 'port' => self::DEFAULT_PORT, 'user' => $user, 'password' => $password]);
            return null;
        } catch (\RuntimeException $e) {
            error_log('SMTP (vérification de ' . $user . ') : ' . $e->getMessage());
            return $e->getMessage();
        }
    }

    /** Mot de passe enregistré d'une boîte, pour la revérifier quand seule son adresse change. */
    public static function storedPassword(string $sealed): string
    {
        return self::reveal($sealed);
    }

    /** Cause d'un refus de connexion, en clair, pour l'indicateur de la page E-mails. */
    public static function explainCheck(string $error): string
    {
        return match (true) {
            str_contains($error, '535') => 'Hostinger refuse cette adresse et ce mot de passe',
            str_starts_with($error, 'connexion impossible') => 'Le serveur d\'envoi de Hostinger ne répond pas',
            default => 'Connexion refusée (' . $error . ')',
        };
    }

    private static function remember(string $box, string $error): void
    {
        Db::run("UPDATE mail_senders SET checked_at = CURRENT_TIMESTAMP, check_error = ? WHERE email = ? AND box = ''", [$error, $box]);
    }

    /** Dernier échec passager (réponse 4xx du serveur, serveur injoignable) : l'e-mail pourra partir plus tard. */
    public static function temporary(): bool
    {
        $error = (string) self::$lastError;
        return str_starts_with($error, 'connexion impossible') || str_starts_with($error, 'délai dépassé') || (bool) preg_match('/: 4\d\d /', $error);
    }

    /** Cause du dernier échec, en clair pour le tableau de bord ; le détail technique reste dans le journal PHP. */
    public static function explain(): string
    {
        $error = (string) self::$lastError;
        return match (true) {
            $error === 'aucune adresse d\'expédition' => 'Aucune adresse d\'expédition n\'est réglée pour cet envoi. Réglez-la dans E-mails.',
            str_starts_with($error, 'boîte ') => 'Le mot de passe de la boîte ' . explode(' ', $error)[1] . ' n\'est pas enregistré. Ajoutez-le dans E-mails, partie « Boîtes et alias d\'envoi ».',
            str_starts_with($error, 'adresse ') => 'L\'adresse ' . explode(' ', $error)[1] . ' n\'est reliée à aucune boîte d\'envoi. Ajoutez-la dans E-mails, partie « Boîtes et alias d\'envoi ».',
            str_starts_with($error, 'expéditeur') => 'La boîte d\'envoi refuse d\'envoyer au nom de ' . explode(' ', $error)[1] . '. Vérifiez dans E-mails qu\'elle est bien une boîte ou un alias de la boîte choisie, et que cet alias existe chez Hostinger.',
            stripos($error, 'ratelimit') !== false => 'Hostinger limite pour l\'instant le nombre d\'e-mails envoyés depuis ce domaine : rien n\'est parti. Réessayez dans quelques heures ; si cela dure, prévenez La Capsule.',
            self::temporary() => 'Hostinger refuse l\'envoi pour l\'instant (' . $error . '). Réessayez plus tard.',
            str_contains($error, '535') => 'L\'adresse ou le mot de passe de la boîte d\'envoi a été refusé. Vérifiez-les dans E-mails puis réessayez.',
            str_starts_with($error, 'délai dépassé') => 'Le service d\'envoi de Hostinger n\'a pas répondu à temps : l\'e-mail n\'est pas parti. Réessayez dans quelques minutes ; si cela dure, prévenez La Capsule.',
            str_starts_with($error, 'connexion impossible') => 'Le service d\'envoi ne répond pas. Réessayez plus tard ; si cela dure, prévenez La Capsule.',
            default => 'L\'e-mail n\'a pas pu partir (' . ($error !== '' ? $error : 'cause inconnue') . '). Réessayez ; si cela échoue encore, prévenez La Capsule.',
        };
    }

    /**
     * Sans $from : connexion à la boîte seulement, puis QUIT. Les refus jusqu'à la connexion ont le code 1.
     * @param array{host: string, port: int, user: string, password: string} $config
     */
    private static function smtp(array $config, ?string $from = null, string $to = '', string $message = ''): void
    {
        $host = $config['host'];
        $port = $config['port'];
        $ssl = $port === 465;
        $socket = @stream_socket_client(($ssl ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $errstr, 15);
        if ($socket === false) {
            throw new \RuntimeException("connexion impossible : $errstr", 1);
        }
        // Délai court par réponse : un serveur muet ne doit pas faire couper la page par l'hébergeur avant notre message.
        stream_set_timeout($socket, 10);
        $stage = 1;
        $step = 'accueil';
        $expect = static function (string $code) use ($socket, &$stage, &$step): void {
            $reply = '';
            while (($line = fgets($socket, 515)) !== false) {
                $reply .= $line;
                if (($line[3] ?? '') === ' ') {
                    break;
                }
            }
            if ($reply === '' && stream_get_meta_data($socket)['timed_out']) {
                throw new \RuntimeException("délai dépassé : pas de réponse du serveur d'envoi à l'étape $step", $stage);
            }
            if (!str_starts_with($reply, $code)) {
                throw new \RuntimeException('réponse inattendue : ' . trim($reply), $stage);
            }
        };
        $cmd = static function (string $line, string $code) use ($socket, $expect, &$step): void {
            // Nom de commande seulement : jamais l'identifiant, le mot de passe ni le message dans le journal.
            $step = preg_match('/^(EHLO|STARTTLS|AUTH LOGIN|MAIL FROM|RCPT TO|DATA)\b/', $line, $m) ? $m[1] : ($step === 'DATA' ? 'envoi du message' : 'identification');
            fwrite($socket, $line . "\r\n");
            $expect($code);
        };

        $expect('220');
        $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
        $cmd($ehlo, '250');
        if (!$ssl) {
            $cmd('STARTTLS', '220');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('STARTTLS refusé', 1);
            }
            $cmd($ehlo, '250');
        }
        $cmd('AUTH LOGIN', '334');
        $cmd(base64_encode($config['user']), '334');
        $cmd(base64_encode($config['password']), '235');
        $stage = 2;
        if ($from === null) {
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            return;
        }
        try {
            $cmd("MAIL FROM:<$from>", '250');
        } catch (\RuntimeException $e) {
            throw new \RuntimeException("expéditeur $from refusé : " . $e->getMessage(), 2, $e);
        }
        $cmd("RCPT TO:<$to>", '250');
        $cmd('DATA', '354');
        // Point en début de ligne doublé (RFC 5321, 4.5.2).
        $cmd(preg_replace('/^\./m', '..', $message) . "\r\n.", '250');
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
    }

    private static function encode(string $value): string
    {
        return preg_match('/[^\x20-\x7e]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
    }
}
