<?php
declare(strict_types=1);

namespace App\Site;

use App\Core\Markdown;

/** Rendu du corps d'une section : composant, grille de cartes ou texte. */
final class Section
{
    private const PHOTOS = ['accueil', 'reseau', 'equipe', 'decouvrir', 'agir', 'former', 'contact', 'adherer', 'atelier', 'collectif'];

    /** @param array{key: string, title: string, variant: string, image: string, body: string} $s */
    public static function body(array $s, int $h = 3): string
    {
        $body = trim($s['body']);
        $variants = explode(' ', $s['variant']);
        if (in_array('cartes', $variants, true)) {
            return self::cards($body, $h);
        }
        if (in_array('blocs', $variants, true) || in_array('frise', $variants, true)) {
            return self::subsections($body, $h, in_array('frise', $variants, true) ? 'frise' : 'blocs');
        }
        // Texte et blocs dynamiques mêlés : une ligne « @composant » seule insère le bloc à cet endroit.
        $html = '';
        $text = [];
        $hasText = false;
        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            if (Components::isDirective($line)) {
                $html .= Markdown::toHtml(implode("\n", $text), $h - 3);
                $text = [];
                $html .= Components::render(trim($line), $h);
                continue;
            }
            $text[] = $line;
            $hasText = $hasText || trim($line) !== '';
        }
        $html .= Markdown::toHtml(implode("\n", $text), $h - 3);
        // Un bloc vide (donnée absente) masque la section s'il en était le seul contenu.
        return $hasText || trim($html) !== '' ? $html : '';
    }

    /** Photo de section (fichiers générés par bin/build-images.php), ou chaîne vide. */
    public static function photo(string $name, string $sizes = '(min-width: 60rem) 40vw, 100vw'): string
    {
        if (preg_match('/^media-(\d+)$/', $name, $m)) {
            $media = \App\Core\Db::run('SELECT * FROM media WHERE id = ?', [(int) $m[1]])->fetch();
            if (!$media) {
                return '';
            }
            $variants = json_decode((string) $media['variants'], true) ?: [(int) $media['width'] => $media['path']];
            ksort($variants);
            $srcset = implode(', ', array_map(fn ($w, $p) => e($p) . " {$w}w", array_keys($variants), $variants));
            return '<img class="photo" src="' . e((string) reset($variants)) . '" srcset="' . $srcset . '" sizes="' . $sizes . '" width="' . (int) $media['width'] . '" height="' . (int) $media['height'] . '" alt="" loading="lazy" decoding="async">';
        }
        if (!in_array($name, self::PHOTOS, true)) {
            return '';
        }
        $portrait = in_array($name, ['adherer', 'atelier', 'collectif'], true);
        [$small, $large, $w, $h] = $portrait ? [600, 900, 900, 1125] : [800, 1600, 1600, 900];
        return '<img class="photo" src="/assets/img/photos/' . $name . '-' . $small . '.webp" srcset="/assets/img/photos/' . $name . '-' . $small . '.webp ' . $small . 'w, /assets/img/photos/' . $name . '-' . $large . '.webp ' . $large . 'w" sizes="' . $sizes . '" width="' . $w . '" height="' . $h . '" alt="" loading="lazy" decoding="async">';
    }

    /**
     * Sous-parties « ### [!picto] Titre » rendues en cartes (blocs) ou en étapes d'une frise.
     * Le texte avant la première sous-partie sert d'introduction.
     */
    private static function subsections(string $body, int $h, string $kind): string
    {
        $parts = preg_split('/^### /m', $body) ?: [];
        $intro = trim((string) array_shift($parts));
        if ($parts === []) {
            return Markdown::toHtml($body, $h - 3);
        }
        $items = '';
        foreach ($parts as $part) {
            [$title, $content] = array_pad(explode("\n", $part, 2), 2, '');
            $picto = '';
            if (preg_match('/^!([a-z0-9]+)\s+(.*)$/', trim($title), $m)) {
                [$picto, $title] = [$m[1], $m[2]];
            }
            $icon = $picto !== '' && is_file(ROOT . "/public/assets/img/pictos/$picto.webp")
                ? '<img class="bloc__picto" src="/assets/img/pictos/' . $picto . '.webp" alt="" width="96" height="96" loading="lazy" decoding="async">'
                : '';
            $content = str_replace("\n#### ", "\n### ", "\n" . trim($content));
            $inner = Markdown::toHtml($content, $h - 2);
            // Contenu long : le premier paragraphe reste visible, la suite se déplie (details natif, sans JavaScript).
            if ($kind === 'blocs' && text_length($content) > 650) {
                $chunks = preg_split('/\n\s*\n/', trim($content), 2) ?: [];
                if (count($chunks) === 2) {
                    $inner = Markdown::toHtml($chunks[0], $h - 2)
                        . '<details class="more"><summary>Lire la suite<span class="visually-hidden"> : ' . e(trim($title)) . '</span></summary>'
                        . Markdown::toHtml($chunks[1], $h - 2) . '</details>';
                }
            }
            $items .= $kind === 'frise'
                ? '<li class="frise__item"><span class="frise__dot" aria-hidden="true"></span><h' . $h . ' class="frise__title">' . Markdown::inline(trim($title)) . '</h' . $h . '><div class="prose">' . $inner . '</div></li>'
                : '<li class="bloc">' . $icon . '<h' . $h . ' class="bloc__title">' . Markdown::inline(trim($title)) . '</h' . $h . '><div class="prose">' . $inner . '</div></li>';
        }
        $introHtml = $intro === '' ? '' : '<div class="section__intro">' . Markdown::toHtml($intro, $h - 3) . '</div>';
        return $introHtml . ($kind === 'frise' ? '<ol class="frise" role="list">' . $items . '</ol>' : '<ul class="blocs" role="list">' . $items . '</ul>');
    }

    /** Lignes « - !picto [Titre](url) : texte » ou « - !picto **Titre** : texte » ; le texte avant la liste sert d'introduction. */
    private static function cards(string $body, int $h): string
    {
        $intro = [];
        $cards = '';
        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            $line = trim($line);
            if (preg_match('/^-\s+(?:!([a-z0-9]+)\s+)?(?:\[([^\]]+)\]\(([^)\s]+)\)|\*\*(.+?)\*\*)\s*:?\s*(.*)$/u', $line, $m)) {
                [$picto, $title, $url, $plain, $text] = [$m[1], $m[2], $m[3], $m[4], $m[5]];
                $icon = $picto !== '' && is_file(ROOT . "/public/assets/img/pictos/$picto.webp")
                    ? '<img class="card__picto" src="/assets/img/pictos/' . $picto . '.webp" alt="" width="96" height="96" loading="lazy" decoding="async">'
                    : '';
                $heading = $url !== '' ? '<a class="card__link" href="' . e($url) . '">' . e($title) . '</a>' : e($plain);
                $cards .= '<li class="card' . ($url !== '' ? ' card--link' : '') . '">' . $icon . "<h$h class=\"card__title\">" . $heading . "</h$h>"
                    . ($text !== '' ? '<p>' . Markdown::inline($text) . '</p>' : '') . '</li>';
            } elseif ($cards === '') {
                $intro[] = $line;
            }
        }
        if ($cards === '') {
            return Markdown::toHtml($body, $h - 3);
        }
        $introHtml = trim(implode("\n", $intro)) === '' ? '' : '<div class="section__intro">' . Markdown::toHtml(implode("\n", $intro)) . '</div>';
        return $introHtml . '<ul class="cards" role="list">' . $cards . '</ul>';
    }
}
