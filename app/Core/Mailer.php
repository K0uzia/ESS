<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Client SMTP minimal (SSL implicite sur 465, STARTTLS sur 587, AUTH LOGIN).
 * Réglages : ceux du tableau de bord (Comptes › Envoi des e-mails) s'ils existent, sinon le .env.
 * Sans serveur (poste local), le message est écrit dans data/mail.log au lieu d'être envoyé.
 */
final class Mailer
{
    public const DEFAULT_HOST = 'smtp.hostinger.com';
    public const DEFAULT_PORT = 465;

    /** Cause du dernier échec d'envoi (pour l'e-mail de test du tableau de bord). */
    public static ?string $lastError = null;

    /** @return array{host: string, port: int, user: string, password: string, from: string, source: string} */
    public static function config(): array
    {
        $user = setting('smtp_user');
        if ($user !== '') {
            return [
                'host' => setting('smtp_host') ?: self::DEFAULT_HOST,
                'port' => (int) (setting('smtp_port') ?: self::DEFAULT_PORT),
                'user' => $user,
                'password' => self::reveal(setting('smtp_password')),
                'from' => $user,
                'source' => 'admin',
            ];
        }
        return [
            'host' => $_ENV['SMTP_HOST'] ?? '',
            'port' => (int) (($_ENV['SMTP_PORT'] ?? '') ?: self::DEFAULT_PORT),
            'user' => $_ENV['SMTP_USER'] ?? '',
            'password' => $_ENV['SMTP_PASSWORD'] ?? '',
            'from' => $_ENV['SMTP_FROM'] ?? '',
            'source' => 'env',
        ];
    }

    /** Mot de passe de la boîte chiffré avec la clé de l'application : la base seule ne le révèle pas. */
    public static function seal(string $password): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce . sodium_crypto_secretbox($password, $nonce, self::secretKey()));
    }

    private static function reveal(string $sealed): string
    {
        $raw = base64_decode($sealed, true);
        if ($raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return '';
        }
        $plain = sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), self::secretKey());
        return $plain === false ? '' : $plain;
    }

    private static function secretKey(): string
    {
        return sodium_crypto_generichash('smtp|' . FormGuard::key(), '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    /** @param array<string, string> $headers */
    public static function send(string $to, string $subject, string $text, ?string $html = null, ?string $replyTo = null, ?string $from = null, array $headers = [], ?string $fromName = null): bool
    {
        $config = self::config();
        $from ??= $config['from'] ?: setting('email_contact');
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
        if ($config['host'] === '') {
            $dir = ROOT . '/data';
            is_dir($dir) || mkdir($dir, 0775, true);
            file_put_contents("$dir/mail.log", "=== " . date('c') . " → $to\r\n$message\r\n", FILE_APPEND);
            return true;
        }

        try {
            self::smtp($config, $from, $to, $message);
            return true;
        } catch (\RuntimeException $e) {
            self::$lastError = $e->getMessage();
            error_log('SMTP : ' . $e->getMessage());
            return false;
        }
    }

    /** @param array{host: string, port: int, user: string, password: string} $config */
    private static function smtp(array $config, string $from, string $to, string $message): void
    {
        $host = $config['host'];
        $port = $config['port'];
        $ssl = $port === 465;
        $socket = @stream_socket_client(($ssl ? 'ssl://' : 'tcp://') . "$host:$port", $errno, $errstr, 15);
        if ($socket === false) {
            throw new \RuntimeException("connexion impossible : $errstr");
        }
        stream_set_timeout($socket, 30);
        $expect = static function (string $code) use ($socket): void {
            $reply = '';
            while (($line = fgets($socket, 515)) !== false) {
                $reply .= $line;
                if (($line[3] ?? '') === ' ') {
                    break;
                }
            }
            if (!str_starts_with($reply, $code)) {
                throw new \RuntimeException('réponse inattendue : ' . trim($reply));
            }
        };
        $cmd = static function (string $line, string $code) use ($socket, $expect): void {
            fwrite($socket, $line . "\r\n");
            $expect($code);
        };

        $expect('220');
        $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
        $cmd($ehlo, '250');
        if (!$ssl) {
            $cmd('STARTTLS', '220');
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException('STARTTLS refusé');
            }
            $cmd($ehlo, '250');
        }
        $cmd('AUTH LOGIN', '334');
        $cmd(base64_encode($config['user']), '334');
        $cmd(base64_encode($config['password']), '235');
        $cmd("MAIL FROM:<$from>", '250');
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
