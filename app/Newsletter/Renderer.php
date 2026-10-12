<?php
declare(strict_types=1);

namespace App\Newsletter;

use App\Core\Markdown;

/** Rendu e-mail d'une lettre : tableau centré de 600 px, styles en ligne, version texte. */
final class Renderer
{
    /**
     * @param array<string, mixed> $campaign subject, body
     * @param array<string, mixed>|null $template header, footer
     * @param array<string, mixed> $pole
     * @return array{html: string, text: string}
     */
    public static function render(array $campaign, ?array $template, array $pole, ?string $unsubscribeUrl): array
    {
        $c = require ROOT . '/config/email.php';
        $site = rtrim(setting('site_url'), '/');
        $logoFile = "/assets/img/email/logo-pole-{$pole['slug']}.png";
        $logo = is_file(ROOT . '/public' . $logoFile) ? $site . $logoFile : $site . '/assets/img/email/logo-ess-finistere.png';
        $unsubscribeUrl ??= $site . '/newsletter/';

        $blocks = array_filter([
            $template['header'] ?? '',
            (string) $campaign['body'],
            $template['footer'] ?? '',
        ], fn ($b) => trim((string) $b) !== '');
        $body = implode('<hr style="margin:28px 0;border:0;border-top:1px solid ' . $c['glaz_1'] . ';">', array_map(
            fn ($md) => self::inlineStyles(Markdown::toHtml((string) $md), $c),
            $blocks
        ));

        $address = e(str_replace("\n", ', ', (string) $pole['address_lines']) . ', ' . $pole['postal_code'] . ' ' . $pole['city']);
        $html = '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . e((string) $campaign['subject']) . '</title></head>'
            . '<body style="margin:0;padding:0;background:' . $c['surface'] . ';">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . $c['surface'] . ';"><tr><td align="center" style="padding:24px 12px;">'
            . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:' . $c['white'] . ';border-radius:16px;overflow:hidden;">'
            . '<tr><td style="padding:28px 32px;background:' . $c['white'] . ';"><img src="' . e($logo) . '" alt="' . e((string) $pole['name']) . '" height="64" style="display:block;height:64px;width:auto;border:0;"></td></tr>'
            . '<tr><td style="padding:28px 32px;background:' . $c['glaz_4'] . ';color:' . $c['white'] . ';font-family:' . $c['font'] . ';">'
            . '<p style="margin:0 0 6px;font-size:12px;letter-spacing:1px;text-transform:uppercase;color:' . $c['glaz_1'] . ';">Lettre d\'information</p>'
            . '<h1 style="margin:0;font-size:24px;line-height:1.25;color:' . $c['white'] . ';">' . e((string) $campaign['subject']) . '</h1></td></tr>'
            . '<tr><td style="padding:32px;font-family:' . $c['font'] . ';font-size:16px;line-height:1.6;color:' . $c['glaz_4'] . ';">' . $body . '</td></tr>'
            . '<tr><td style="padding:24px 32px;background:' . $c['surface'] . ';font-family:' . $c['font'] . ';font-size:13px;line-height:1.5;color:' . $c['glaz_3'] . ';">'
            . '<strong>' . e((string) $pole['name']) . '</strong><br>' . $address . '<br>'
            . '<a href="mailto:' . e((string) $pole['email']) . '" style="color:' . $c['glaz_3'] . ';">' . e((string) $pole['email']) . '</a> · <a href="' . e($site) . '/" style="color:' . $c['glaz_3'] . ';">ess29.org</a>'
            . '<p style="margin:16px 0 0;">Vous recevez cette lettre car vous y êtes inscrit. <a href="' . e($unsubscribeUrl) . '" style="color:' . $c['glaz_3'] . ';">Se désinscrire</a></p>'
            . '</td></tr></table></td></tr></table></body></html>';

        $text = $campaign['subject'] . "\n\n" . implode("\n\n---\n\n", array_map(fn ($b) => self::toText((string) $b), $blocks))
            . "\n\n--\n" . $pole['name'] . "\n" . str_replace("\n", ', ', (string) $pole['address_lines']) . ', ' . $pole['postal_code'] . ' ' . $pole['city']
            . "\n" . $pole['email'] . "\n\nSe désinscrire : $unsubscribeUrl\n";

        return ['html' => $html, 'text' => $text];
    }

    /** @param array<string, string> $c */
    private static function inlineStyles(string $html, array $c): string
    {
        return strtr($html, [
            '<p>' => '<p style="margin:0 0 16px;">',
            '<h3>' => '<h2 style="margin:24px 0 12px;font-size:19px;line-height:1.3;color:' . $c['glaz_3'] . ';">',
            '</h3>' => '</h2>',
            '<h4>' => '<h3 style="margin:20px 0 8px;font-size:16px;color:' . $c['glaz_3'] . ';">',
            '</h4>' => '</h3>',
            '<ul>' => '<ul style="margin:0 0 16px;padding-left:20px;">',
            '<ol>' => '<ol style="margin:0 0 16px;padding-left:20px;">',
            '<li>' => '<li style="margin:0 0 6px;">',
            '<a href' => '<a style="color:' . $c['glaz_3'] . ';font-weight:600;" href',
            '<a class="button" ' => '<a style="display:inline-block;padding:12px 22px;border-radius:999px;background:' . $c['or'] . ';color:' . $c['glaz_4'] . ';font-weight:600;text-decoration:none;" ',
            '<blockquote class="quote">' => '<blockquote style="margin:0 0 16px;padding:16px 20px;border-radius:12px;background:' . $c['surface'] . ';color:' . $c['glaz_3'] . ';font-style:italic;">',
        ]);
    }

    private static function toText(string $markdown): string
    {
        $text = preg_replace('/\[([^\]]+)\]\(([^)\s]+)\)/', '$1 ($2)', $markdown) ?? $markdown;
        $text = preg_replace('/^(#{3,4}|→|>)\s*/m', '', $text) ?? $text;
        return trim(str_replace(['**', '*'], '', $text));
    }
}
