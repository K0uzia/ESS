<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Client SMTP minimal (SSL implicite sur 465, STARTTLS sur 587, AUTH LOGIN).
 * Sans SMTP_HOST (poste local), le message est écrit dans data/mail.log au lieu d'être envoyé.
 */
final class Mailer
{
    /** @param array<string, string> $headers */
    public static function send(string $to, string $subject, string $text, ?string $html = null, ?string $replyTo = null, ?string $from = null, array $headers = [], ?string $fromName = null): bool
    {
        $from ??= ($_ENV['SMTP_FROM'] ?? '') ?: setting('email_contact');
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

        if (($_ENV['SMTP_HOST'] ?? '') === '') {
            $dir = ROOT . '/data';
            is_dir($dir) || mkdir($dir, 0775, true);
            file_put_contents("$dir/mail.log", "=== " . date('c') . " → $to\r\n$message\r\n", FILE_APPEND);
            return true;
        }

        try {
            self::smtp($from, $to, $message);
            return true;
        } catch (\RuntimeException $e) {
            error_log('SMTP : ' . $e->getMessage());
            return false;
        }
    }

    private static function smtp(string $from, string $to, string $message): void
    {
        $host = $_ENV['SMTP_HOST'];
        $port = (int) ($_ENV['SMTP_PORT'] ?? 465);
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
        $cmd(base64_encode($_ENV['SMTP_USER'] ?? ''), '334');
        $cmd(base64_encode($_ENV['SMTP_PASSWORD'] ?? ''), '235');
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
