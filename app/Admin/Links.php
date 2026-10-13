<?php
declare(strict_types=1);

namespace App\Admin;

use App\Content\Page;
use App\Core\Db;
use App\Site\SiteController;

/**
 * Destinations proposées pour un lien ou un bouton : pages du site et leurs sections, onglets des pôles,
 * ou une adresse extérieure / un e-mail. Évite de saisir une adresse à la main.
 */
final class Links
{
    public const EXTERNAL = '__web';
    public const MAIL = '__mail';

    /** @return array<string, array<string, string>> groupe => [adresse => libellé] */
    public static function targets(): array
    {
        static $groups = null;
        if ($groups !== null) {
            return $groups;
        }
        $groups = [];
        $labels = ContentController::pages();
        foreach (SiteController::PAGES as $path => $key) {
            $page = $labels[$key] ?? $key;
            $options = [(string) $path => $page . ' (haut de la page)'];
            foreach ((Page::load($key) ?? ['sections' => []])['sections'] as $s) {
                if ($s['title'] !== '') {
                    $options[$path . '#' . $s['key']] = $page . ' › ' . $s['title'];
                }
            }
            foreach ($key === 'les-poles' || $key === 'equipe' ? Db::run('SELECT slug, name FROM poles ORDER BY position')->fetchAll() : [] as $p) {
                if ($key === 'les-poles') {
                    $options['/les-poles/#' . $p['slug']] = $page . ' › ' . $p['name'];
                } else {
                    $options['/equipe/#equipe-' . $p['slug']] = $page . ' › équipe du ' . $p['name'];
                }
            }
            $groups[$labels[$key] ?? $key] = $options;
        }
        return $groups;
    }

    /** Libellé lisible d'une adresse (« Agir › Entreprendre autrement »), ou l'adresse elle-même. */
    public static function label(string $url): string
    {
        foreach (self::targets() as $group => $options) {
            if (isset($options[$url])) {
                return str_replace(' (haut de la page)', '', $options[$url]);
            }
        }
        return preg_replace('#^mailto:#', '', $url) ?? $url;
    }

    /** Adresse choisie dans le formulaire (liste ou saisie libre), ou null si elle n'est pas valable. */
    public static function resolve(string $target, string $free): ?string
    {
        $free = trim($free);
        if ($target === self::MAIL) {
            $free = (string) preg_replace('#^mailto:#i', '', $free);
            return filter_var($free, FILTER_VALIDATE_EMAIL) ? 'mailto:' . $free : null;
        }
        if ($target === self::EXTERNAL) {
            if (preg_match('/^www\./i', $free)) {
                $free = 'https://' . $free;
            }
            // Une adresse interne déjà enregistrée hors de la liste (« /page/#ancre ») reste acceptée telle quelle.
            return preg_match('#^(https?://[^\s()]+\.[^\s()]+|/[^\s()]*)$#i', $free) ? $free : null;
        }
        foreach (self::targets() as $options) {
            if (isset($options[$target])) {
                return $target;
            }
        }
        return null;
    }

    /** Valeur de la liste et saisie libre correspondant à une adresse enregistrée. @return array{0: string, 1: string} */
    public static function split(string $url, string $base = ''): array
    {
        // « #ancre » d'un bouton d'en-tête : section de la même page.
        if ($base !== '' && str_starts_with($url, '#')) {
            $url = $base . $url;
        }
        foreach (self::targets() as $options) {
            if (isset($options[$url])) {
                return [$url, ''];
            }
        }
        if (str_starts_with($url, 'mailto:')) {
            return [self::MAIL, substr($url, 7)];
        }
        return [self::EXTERNAL, $url];
    }
}
