<?php
declare(strict_types=1);

namespace App\Site;

use App\Core\Markdown;

/** Rendu du corps d'une section : composant, grille de cartes ou texte. */
final class Section
{
    private const CHARTE = 'Charte/CHARTE GRAPHIQUE ESS BRETAGNE/5-Photos/';
    private const LIBRES = 'content/photos-libres/';

    /**
     * Photos du site, source unique pour bin/build-images.php et le rendu. Chaque photo n'a qu'un emploi sur le site.
     * nom => [fichier source depuis la racine, ratio largeur/hauteur, recadrage vertical (0 haut, 1 bas), largeurs générées]
     */
    public const PHOTOS = [
        'accueil' => [self::CHARTE . 'AdobeStock_1879193392.jpeg', 16 / 9, 0.5, [960, 1920]],
        'reseau' => [self::CHARTE . 'AdobeStock_1342779117.jpeg', 16 / 9, 0.35, [800, 1600]],
        'equipe' => [self::CHARTE . 'AdobeStock_519172833.jpeg', 16 / 9, 0.3, [800, 1600]],
        'decouvrir' => [self::CHARTE . 'AdobeStock_659243949.jpeg', 16 / 9, 0.3, [800, 1600]],
        'agir' => [self::CHARTE . 'AdobeStock_600680452.jpeg', 16 / 9, 0.3, [800, 1600]],
        'former' => [self::CHARTE . 'AdobeStock_2063079841.jpeg', 16 / 9, 0.4, [800, 1600]],
        'contact' => [self::CHARTE . 'AdobeStock_339694831.jpeg', 16 / 9, 0.35, [800, 1600]],
        'adherer' => [self::CHARTE . 'AdobeStock_1367700974.jpeg', 4 / 5, 0.55, [600, 900]],
        'atelier' => [self::CHARTE . 'AdobeStock_209847380.jpeg', 4 / 5, 0.4, [600, 900]],
        'collectif' => [self::CHARTE . 'AdobeStock_1708024907.jpeg', 4 / 5, 0.6, [600, 900]],
        // Photos libres de droits (CC0), sources dans content/photos-libres/SOURCES.tsv.
        'lettre' => [self::LIBRES . 'lettre.jpg', 3 / 2, 0.5, [600, 960]],
        'circuits-courts' => [self::LIBRES . 'circuits-courts.jpg', 3 / 2, 0.5, [600, 960]],
        'sensibilisation' => [self::LIBRES . 'sensibilisation.jpg', 3 / 2, 0.5, [600, 954]],
        'emploi' => [self::LIBRES . 'emploi.jpg', 3 / 2, 0.5, [600, 810]],
        'formation' => [self::LIBRES . 'formation.jpg', 3 / 2, 0.5, [600, 960]],
        'tag29' => [self::LIBRES . 'tag29.jpg', 3 / 2, 0.5, [600, 960]],
        'tag29-presentation' => [self::LIBRES . 'tag29-presentation.jpg', 3 / 2, 0.5, [600, 960]],
    ];

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

    /**
     * Section texte + photo : le texte avant la première sous-partie « ### » reste à côté de la photo,
     * les sous-parties (deux ou plus) passent dessous en cartes, pour que la photo n'ait jamais une colonne de texte plus haute qu'elle.
     * @param array{key: string, title: string, variant: string, image: string, body: string} $s
     * @return array{0: string, 1: string} [à côté de la photo, sous la ligne]
     */
    public static function splitParts(array $s, int $h = 3): array
    {
        $parts = preg_split('/^(?=### )/m', trim($s['body']), 2) ?: [];
        // Une seule sous-partie reste à côté de la photo : une carte seule sous la ligne serait isolée.
        if (count($parts) < 2 || trim($parts[0]) === '' || preg_match_all('/^### /m', $parts[1]) < 2) {
            return [self::body($s, $h), ''];
        }
        return [self::body(['body' => $parts[0], 'variant' => ''] + $s, $h), self::subsections($parts[1], $h, 'blocs')];
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
        $img = self::photoAttributes($name, $sizes);
        return $img === '' ? '' : '<img class="photo" ' . $img . ' alt="" loading="lazy" decoding="async">';
    }

    /** Attributs src, srcset, sizes, width et height d'une photo de self::PHOTOS, ou chaîne vide. */
    public static function photoAttributes(string $name, string $sizes): string
    {
        if (!isset(self::PHOTOS[$name])) {
            return '';
        }
        [, $ratio, , $widths] = self::PHOTOS[$name];
        $srcset = implode(', ', array_map(static fn (int $w): string => "/assets/img/photos/$name-$w.webp {$w}w", $widths));
        $large = max($widths);
        return 'src="/assets/img/photos/' . $name . '-' . min($widths) . '.webp" srcset="' . $srcset . '" sizes="' . $sizes . '" width="' . $large . '" height="' . (int) round($large / $ratio) . '"';
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
            // Cartes de hauteur comparable : le premier bloc reste visible, coupé à hauteur fixe en CSS,
            // la suite se lit dans « Lire la suite ». Une carte avec un tableau (tarifs) reste entière.
            if ($kind === 'blocs' && text_length($content) > 320 && !str_contains($content, '|---')) {
                $blocks = preg_split('/\n\s*\n/', trim($content)) ?: [];
                $teaser = (string) array_shift($blocks);
                $rest = implode("\n\n", $blocks);
                $inner = ($teaser !== '' ? '<div class="bloc__teaser">' . Markdown::toHtml($teaser, $h - 2) . '</div>' : '')
                    . '<details class="more"><summary>Lire la suite<span class="visually-hidden"> : ' . e(trim($title)) . '</span></summary>'
                    . ($rest !== '' ? Markdown::toHtml($rest, $h - 2) : '') . '</details>';
            }
            $items .= $kind === 'frise'
                ? '<li class="frise__item"><span class="frise__dot" aria-hidden="true"></span><h' . $h . ' class="frise__title">' . Markdown::inline(trim($title)) . '</h' . $h . '><div class="prose">' . $inner . '</div></li>'
                : '<li class="bloc">' . $icon . '<h' . $h . ' class="bloc__title">' . Markdown::inline(trim($title)) . '</h' . $h . '><div class="prose">' . $inner . '</div></li>';
        }
        // L'introduction peut contenir un bloc dynamique (« @composant » seul sur sa ligne).
        $introHtml = $intro === '' ? '' : '<div class="section__intro">' . self::body(['key' => '', 'title' => '', 'variant' => '', 'image' => '', 'body' => $intro], $h) . '</div>';
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
