<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Markdown;
use App\Site\Components;

/**
 * Corps d'une section découpé en champs de texte pour l'éditeur : la structure (pictogrammes, liens des cartes et
 * des boutons, mise en page, blocs dynamiques) n'est jamais exposée ni renvoyée par le formulaire. À l'enregistrement,
 * le corps actuel est redécoupé et seuls les textes sont remplacés, unité par unité. L'éditeur enrichi (admin.js)
 * convertit lui-même son contenu en Markdown : le serveur ne reçoit que du texte, échappé au rendu.
 *
 * Unités : text (paragraphes et listes), heading (intertitre), card (carte), button, quote (témoignage),
 * table (tableau), component (bloc automatique, non modifiable ici).
 */
final class TextFields
{
    private const CARD = '/^-\s+(?:!([a-z0-9]+)\s+)?(?:\[([^\]]+)\]\(([^)\s]+)\)|\*\*(.+?)\*\*)\s*:?\s*(.*)$/u';

    /** @return list<array<string, mixed>> */
    public static function parse(string $body, string $variant): array
    {
        $isCards = in_array('cartes', explode(' ', $variant), true);
        $lines = preg_split('/\R/', $body) ?: [];
        $units = [];
        $open = null;
        $close = static function () use (&$open, &$units): void {
            if ($open !== null) {
                $units[] = $open;
                $open = null;
            }
        };
        foreach ($lines as $i => $line) {
            $t = trim($line);
            if ($t === '') {
                continue;
            }
            if (Components::isDirective($t)) {
                $close();
                $units[] = ['type' => 'component', 'from' => $i, 'to' => $i, 'raw' => $t];
            } elseif (preg_match('/^(#{3,4})\s+(?:!([a-z0-9]+)\s+)?(.+)$/u', $t, $m)) {
                $close();
                $units[] = ['type' => 'heading', 'from' => $i, 'to' => $i, 'hashes' => $m[1], 'picto' => $m[2], 'text' => $m[3]];
            } elseif (preg_match('/^→\s+\[([^\]]+)\]\(([^)\s]+)\)$/u', $t, $m)) {
                $close();
                $units[] = ['type' => 'button', 'from' => $i, 'to' => $i, 'text' => $m[1], 'url' => $m[2]];
            } elseif (str_starts_with($t, '> ')) {
                $close();
                $quote = substr($t, 2);
                $author = '';
                if (preg_match('/^(.*»)\s*\(([^()]+)\)$/u', $quote, $q)) {
                    [$quote, $author] = [$q[1], $q[2]];
                }
                $marks = (bool) preg_match('/^«\s*(.*?)\s*»$/us', $quote, $g);
                $units[] = ['type' => 'quote', 'from' => $i, 'to' => $i, 'text' => $marks ? $g[1] : $quote, 'author' => $author, 'marks' => $marks];
            } elseif (str_starts_with($t, '|')) {
                if ($open === null || $open['type'] !== 'table') {
                    $close();
                    $open = ['type' => 'table', 'from' => $i, 'to' => $i, 'rows' => []];
                }
                $open['to'] = $i;
                $open['rows'][] = preg_match('/^\|?\s*:?-{3,}/', $t) ? null : array_map('trim', explode('|', trim($t, " |")));
            } elseif ($isCards && preg_match(self::CARD, $t, $m)) {
                $close();
                $units[] = ['type' => 'card', 'from' => $i, 'to' => $i, 'picto' => $m[1], 'url' => $m[3], 'title' => $m[2] !== '' ? $m[2] : $m[4], 'text' => $m[5]];
            } else {
                if ($open === null || $open['type'] !== 'text') {
                    $close();
                    $open = ['type' => 'text', 'from' => $i, 'to' => $i];
                }
                $open['to'] = $i;
            }
        }
        $close();
        foreach ($units as &$u) {
            if ($u['type'] === 'text') {
                $u['md'] = implode("\n", array_slice($lines, $u['from'], $u['to'] - $u['from'] + 1));
            }
        }
        unset($u);
        // Section encore vide : un champ de texte pour l'écrire.
        if ($units === []) {
            $units[] = ['type' => 'text', 'from' => count($lines), 'to' => count($lines) - 1, 'md' => ''];
        }
        return $units;
    }

    /** Empreinte de la structure, pour refuser un enregistrement fait sur une version périmée de la section. */
    public static function signature(array $units): string
    {
        return substr(md5(implode(',', array_map(
            static fn (array $u): string => $u['type'] . ($u['type'] === 'table' ? count($u['rows']) . 'x' . count((array) ($u['rows'][0] ?? [])) : ''),
            $units
        ))), 0, 12);
    }

    /**
     * Applique les textes envoyés au corps actuel. null si la structure a changé depuis l'ouverture du formulaire.
     * @param array<int|string, mixed> $posted units[i][…]
     */
    public static function apply(string $body, string $variant, array $posted, string $signature): ?string
    {
        $units = self::parse($body, $variant);
        if (!hash_equals(self::signature($units), $signature)) {
            return null;
        }
        $lines = preg_split('/\R/', $body) ?: [];
        // Remplacement de la fin vers le début : les numéros de ligne des unités précédentes restent valables.
        foreach (array_reverse($units, true) as $i => $u) {
            $p = is_array($posted[$i] ?? null) ? $posted[$i] : [];
            $new = self::serialize($u, $p);
            if ($new === null) {
                continue;
            }
            array_splice($lines, $u['from'], $u['to'] - $u['from'] + 1, $new === '' ? [] : explode("\n", $new));
        }
        return trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }

    /** Lignes Markdown d'une unité avec les textes envoyés ; null : unité laissée telle quelle. */
    private static function serialize(array $u, array $p): ?string
    {
        $line = static fn (string $key): string => trim((string) preg_replace('/\s+/u', ' ', (string) ($p[$key] ?? '')));
        switch ($u['type']) {
            case 'text':
                return isset($p['md']) ? str_replace("\r\n", "\n", trim((string) $p['md'])) : null;
            case 'heading':
                $text = $line('text');
                return $text === '' ? null : $u['hashes'] . ' ' . ($u['picto'] !== '' ? '!' . $u['picto'] . ' ' : '') . $text;
            case 'button':
                $text = $line('text');
                return $text === '' ? null : '→ [' . str_replace(']', '', $text) . '](' . self::url($u, $p) . ')';
            case 'card':
                $title = $line('title');
                if ($title === '') {
                    return null;
                }
                $text = self::inlineText($p);
                $head = $u['url'] !== '' ? '[' . str_replace(']', '', $title) . '](' . self::url($u, $p) . ')' : '**' . $title . '**';
                return '- ' . ($u['picto'] !== '' ? '!' . $u['picto'] . ' ' : '') . $head . ($text !== '' ? ' : ' . $text : '');
            case 'quote':
                $text = self::inlineText($p);
                if ($text === '') {
                    return null;
                }
                $text = $u['marks'] ? '« ' . trim($text, ' «»') . ' »' : $text;
                $author = str_replace(['(', ')'], '', $line('author'));
                return '> ' . $text . ($author !== '' ? ' (' . $author . ')' : '');
            case 'table':
                $cells = is_array($p['cells'] ?? null) ? $p['cells'] : [];
                $out = [];
                foreach ($u['rows'] as $r => $row) {
                    if ($row === null) {
                        $out[] = '|' . str_repeat('---|', count((array) $u['rows'][0]));
                        continue;
                    }
                    $values = [];
                    foreach ($row as $c => $old) {
                        $v = isset($cells[$r][$c]) ? trim((string) preg_replace('/\s+/u', ' ', str_replace('|', '/', (string) $cells[$r][$c]))) : $old;
                        $values[] = $v;
                    }
                    $out[] = '| ' . implode(' | ', $values) . ' |';
                }
                return implode("\n", $out);
            default:
                return null;
        }
    }

    /** @var list<string> liens refusés au dernier apply() (adresse non valable : l'ancienne est gardée) */
    public static array $rejected = [];

    /** Destination choisie pour un bouton ou une carte ; l'ancienne si la nouvelle n'est pas valable. */
    private static function url(array $u, array $p): string
    {
        if (!is_array($p['link'] ?? null)) {
            return $u['url'];
        }
        $url = Links::resolve((string) ($p['link']['target'] ?? ''), (string) ($p['link']['free'] ?? ''));
        if ($url === null) {
            self::$rejected[] = (string) ($p['title'] ?? $p['text'] ?? '');
            return $u['url'];
        }
        return $url;
    }

    /** Texte d'une ligne (carte, citation), une seule ligne de Markdown. */
    private static function inlineText(array $p): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) ($p['text'] ?? '')));
    }

    /** HTML affiché dans l'éditeur enrichi : paragraphes, listes, gras, italique, liens. */
    public static function editorHtml(string $md): string
    {
        $html = [];
        $para = [];
        $list = null;
        $flush = static function () use (&$para, &$list, &$html): void {
            if ($para !== []) {
                $html[] = '<p>' . implode('<br>', array_map([Markdown::class, 'inline'], $para)) . '</p>';
                $para = [];
            }
            if ($list !== null) {
                $html[] = "<{$list[0]}>" . implode('', array_map(static fn (string $i): string => '<li>' . Markdown::inline($i) . '</li>', $list[1])) . "</{$list[0]}>";
                $list = null;
            }
        };
        foreach (preg_split('/\R/', $md) ?: [] as $line) {
            $t = trim($line);
            if ($t === '') {
                $flush();
            } elseif (preg_match('/^→\s+\[([^\]]+)\]\(([^)\s]+)\)$/u', $t, $m)) {
                $flush();
                $html[] = '<p class="cta">' . Markdown::inline('[' . $m[1] . '](' . $m[2] . ')') . '</p>';
            } elseif (preg_match('/^#{3,4}\s+(.+)$/', $t, $m)) {
                $flush();
                $html[] = '<h3>' . Markdown::inline($m[1]) . '</h3>';
            } elseif (preg_match('/^(?:([-*])|\d+[.)])\s+(.+)$/', $t, $m)) {
                $tag = $m[1] !== '' ? 'ul' : 'ol';
                if ($para !== [] || ($list !== null && $list[0] !== $tag)) {
                    $flush();
                }
                $list ??= [$tag, []];
                $list[1][] = $m[2];
            } elseif ($list !== null && preg_match('/^\s{2,}\S/', $line)) {
                $list[1][array_key_last($list[1])] .= ' ' . $t;
            } else {
                if ($list !== null) {
                    $flush();
                }
                $para[] = $t;
            }
        }
        $flush();
        return implode('', $html);
    }

    /** Texte brut d'un corps de section, pour l'aperçu de la liste. */
    public static function excerpt(string $body, int $length = 160): string
    {
        $text = (string) preg_replace(
            ['/^@\S+$/m', '/\[([^\]]+)\]\([^)]*\)/', '/!([a-z0-9]+)\s+/', '/^[#>\-→|]+\s*/m', '/[*|]+/', '/\s+/u'],
            ['', '$1', '', '', '', ' '],
            $body
        );
        $text = trim($text);
        return text_length($text) > $length ? rtrim(mb_substr_safe($text, $length - 1)) . '…' : $text;
    }
}
