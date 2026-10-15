<?php
declare(strict_types=1);

namespace App\Site;

use App\Content\Page;
use App\Core\View;

final class SiteController
{
    /** Chemin canonique → clé de page. Source unique de l'arborescence publique (voir [projet].md : pages uniques, sans sous-page). */
    public const PAGES = [
        '/' => 'accueil',
        '/les-poles/' => 'les-poles',
        '/equipe/' => 'equipe',
        '/decouvrir-l-ess/' => 'decouvrir-l-ess',
        '/agir/' => 'agir',
        '/se-former/' => 'se-former',
        '/tag29/' => 'tag29',
        '/mentions-legales/' => 'mentions-legales',
        '/confidentialite/' => 'confidentialite',
        '/accessibilite/' => 'accessibilite',
        '/plan-du-site/' => 'plan-du-site',
    ];

    /** Photo d'en-tête par page (Section::PHOTOS, un seul emploi par photo) ; absente = en-tête en dégradé seul. */
    public const HEAD_PHOTO = [
        'les-poles' => 'reseau',
        'equipe' => 'equipe',
        'decouvrir-l-ess' => 'decouvrir',
        'agir' => 'agir',
        'se-former' => 'former',
        'tag29' => 'tag29',
    ];

    public const NAV = [
        '/les-poles/' => 'Les pôles',
        '/equipe/' => 'L\'équipe',
        '/decouvrir-l-ess/' => 'Découvrir l\'ESS',
        '/agir/' => 'Agir',
        '/se-former/' => 'Se former',
        '/tag29/' => 'TAg29',
        '/#contact' => 'Contact',
    ];

    public static function show(string $path): bool
    {
        $key = self::PAGES[$path] ?? null;
        $page = $key === null ? null : Page::load($key);
        if ($page === null) {
            return false;
        }
        header('Cache-Control: public, max-age=300');
        View::render('site/page', [
            'path' => $path,
            'pageKey' => $key,
            'page' => $page,
            'breadcrumb' => self::breadcrumb($path),
        ], 'site/layout');
        return true;
    }

    public static function notFound(): void
    {
        http_response_code(404);
        View::render('site/404', ['path' => '/404', 'pageKey' => '404', 'page' => [
            'meta' => ['title' => 'Page introuvable', 'description' => '', 'h1' => 'Page introuvable'],
            'sections' => [],
        ], 'breadcrumb' => []], 'site/layout');
    }

    /** @return list<array{0: string, 1: string}> */
    private static function breadcrumb(string $path): array
    {
        return $path === '/' ? [] : [['/', 'Accueil']];
    }
}
