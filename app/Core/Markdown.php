<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sous-ensemble Markdown de content/seed/FORMAT.md. Tout le texte est échappé avant transformation :
 * aucun HTML saisi dans l'admin n'atteint la page.
 */
final class Markdown
{
    /** $caption : titre donné aux tableaux sans intertitre dans le texte (titre de la carte qui les contient). */
    public static function toHtml(string $source, int $headingShift = 0, string $caption = ''): string
    {
        $lines = preg_split('/\R/', trim($source)) ?: [];
        $html = [];
        $paragraph = [];
        $list = null;
        $table = [];

        $flushParagraph = static function () use (&$paragraph, &$html): void {
            if ($paragraph !== []) {
                $html[] = '<p>' . implode('<br>', array_map([self::class, 'inline'], $paragraph)) . '</p>';
                $paragraph = [];
            }
        };
        $flushList = static function () use (&$list, &$html): void {
            if ($list !== null) {
                $html[] = self::isStats($list['items'])
                    ? '<ul class="stats" role="list">' . implode('', array_map([self::class, 'stat'], $list['items'])) . '</ul>'
                    : "<{$list['tag']}>" . implode('', array_map(fn ($i) => '<li>' . self::inline($i) . '</li>', $list['items'])) . "</{$list['tag']}>";
                $list = null;
            }
        };
        $flushTable = static function () use (&$table, &$html, &$caption): void {
            if ($table !== []) {
                $html[] = self::table($table, $caption);
                $table = [];
            }
        };
        $flushAll = static function () use ($flushParagraph, $flushList, $flushTable): void {
            $flushParagraph();
            $flushList();
            $flushTable();
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                $flushAll();
                continue;
            }
            if (preg_match('/^(#{3,4})\s+(.+)$/', $trimmed, $m)) {
                $flushAll();
                $level = min(6, strlen($m[1]) + $headingShift);
                $html[] = "<h$level>" . self::inline($m[2]) . "</h$level>";
                $caption = trim(strip_tags(self::inline($m[2])));
                continue;
            }
            if (preg_match('/^→\s+(\[[^\]]+\]\([^)\s]+\))$/u', $trimmed, $m)) {
                $flushAll();
                $html[] = '<p class="cta">' . str_replace('<a ', '<a class="button" ', self::inline($m[1])) . '</p>';
                continue;
            }
            if (str_starts_with($trimmed, '> ')) {
                $flushAll();
                // « … » (Prénom, âge) : l'auteur final passe en légende de la citation.
                $quote = substr($trimmed, 2);
                $author = preg_match('/^(.*»)\s*\(([^()]+)\)$/u', $quote, $q) ? $q[2] : '';
                $html[] = '<figure class="quote"><blockquote><p>' . self::inline($author !== '' ? $q[1] : $quote) . '</p></blockquote>'
                    . ($author !== '' ? '<figcaption class="quote__author">' . e($author) . '</figcaption>' : '') . '</figure>';
                continue;
            }
            if (str_starts_with($trimmed, '|')) {
                $flushParagraph();
                $flushList();
                $table[] = $trimmed;
                continue;
            }
            if (preg_match('/^(?:([-*])|(\d+)[.)])\s+(.+)$/', $trimmed, $m)) {
                $flushParagraph();
                $flushTable();
                $tag = $m[1] !== '' ? 'ul' : 'ol';
                if ($list !== null && $list['tag'] !== $tag) {
                    $flushList();
                }
                $list ??= ['tag' => $tag, 'items' => []];
                $list['items'][] = $m[3];
                continue;
            }
            if ($list !== null && preg_match('/^\s{2,}\S/', $line)) {
                $list['items'][array_key_last($list['items'])] .= ' ' . $trimmed;
                continue;
            }
            $flushList();
            $flushTable();
            $paragraph[] = $trimmed;
        }
        $flushAll();
        return implode("\n", $html);
    }

    public static function inline(string $text): string
    {
        $links = [];
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', static function (array $m) use (&$links): string {
            $links[] = [$m[1], $m[2]];
            return "\x00" . (count($links) - 1) . "\x00";
        }, $text) ?? $text;

        $html = self::emphasis(e($text));

        return preg_replace_callback('/\x00(\d+)\x00/', static function (array $m) use ($links): string {
            [$label, $url] = $links[(int) $m[1]];
            if (!preg_match('#^(https?://|mailto:|tel:|/|\#)#i', $url)) {
                return self::emphasis(e($label));
            }
            $external = (bool) preg_match('#^https?://#i', $url) && !preg_match('#^https?://(www\.)?ess29\.org#i', $url);
            return '<a href="' . e($url) . '"' . ($external ? ' rel="noopener"' : '') . '>' . self::emphasis(e($label)) . '</a>';
        }, $html) ?? $html;
    }

    private static function emphasis(string $html): string
    {
        $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html) ?? $html;
        return preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/s', '<em>$1</em>', $html) ?? $html;
    }

    private const STAT = '/^(\d[\d\x{202F} ,.]*(?:\s?%|\s?millions? d.euros)?)\s+(.+)$/u';

    /** @param list<string> $items */
    private static function isStats(array $items): bool
    {
        return count($items) >= 2 && count(array_filter($items, fn ($i) => preg_match(self::STAT, $i))) === count($items);
    }

    private static function stat(string $item): string
    {
        preg_match(self::STAT, $item, $m);
        return '<li class="stat"><strong class="stat__value">' . e(trim($m[1])) . '</strong> <span class="stat__label">' . self::inline(rtrim($m[2], ' ;.')) . '</span></li>';
    }

    /** @param list<string> $rows */
    private static function table(array $rows, string $caption = ''): string
    {
        $cells = static fn (string $row): array => array_map('trim', explode('|', trim($row, " |")));
        $head = $cells(array_shift($rows));
        if ($rows !== [] && preg_match('/^\|?\s*:?-{3,}/', $rows[0])) {
            array_shift($rows);
        }
        static $count = 0;
        $count++;
        // Titre du tableau (RGAA 5.4) : intertitre qui le précède, sinon titre de la carte, sinon numéro.
        $title = $caption !== '' ? $caption : 'Tableau ' . $count;
        // Trois colonnes ou plus : lignes empilées en fiches sur mobile, chaque cellule précédée de son en-tête (data-label).
        $stack = count($head) > 2;
        $labels = array_map(static fn (string $h): string => trim(strip_tags(self::inline($h))), $head);
        $out = '<div class="table-wrap" tabindex="0" role="region" aria-label="' . $title . '"><table' . ($stack ? ' class="table--stack"' : '') . '>'
            . '<caption class="visually-hidden">' . $title . '</caption><thead><tr>';
        foreach ($head as $cell) {
            $out .= '<th scope="col">' . self::inline($cell) . '</th>';
        }
        $out .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $out .= '<tr>';
            foreach ($cells($row) as $i => $cell) {
                $out .= '<td' . ($stack && isset($labels[$i]) ? ' data-label="' . $labels[$i] . '"' : '') . '>' . self::inline($cell) . '</td>';
            }
            $out .= '</tr>';
        }
        return $out . '</tbody></table></div>';
    }
}
