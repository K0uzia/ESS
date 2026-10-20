<?php
declare(strict_types=1);

namespace App\Admin;

use App\Content\Page;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Session;
use App\Core\View;
use App\Site\SiteController;

/**
 * Contenus des pages : seulement les textes et la photo des sections existantes. Les sections, leur ordre, la mise en
 * page, les pictogrammes, les liens des cartes et les textes pour Google sont fixés par content/seed/ (La Capsule).
 */
final class ContentController
{
    /** Nom court de chaque page, tel qu'il apparaît dans la navigation du site. */
    public const LABELS = [
        'accueil' => 'Accueil',
        'les-poles' => 'Les pôles',
        'equipe' => 'L\'équipe',
        'decouvrir-l-ess' => 'Découvrir l\'ESS',
        'agir' => 'Agir',
        'se-former' => 'Se former',
        'tag29' => 'Tag29',
        'mentions-legales' => 'Mentions légales',
        'confidentialite' => 'Confidentialité',
        'accessibilite' => 'Accessibilité',
        'plan-du-site' => 'Plan du site',
    ];

    /** Pages du groupement, modifiables par un éditeur ESS29 (pas les pôles, ni TAg29, ni les pages légales). */
    public const ESS29_PAGES = ['accueil', 'les-poles', 'equipe', 'decouvrir-l-ess', 'agir', 'se-former'];

    /** @return array<string, string> clé de page → libellé */
    public static function pages(): array
    {
        $pages = [];
        foreach (SiteController::PAGES as $path => $key) {
            $pages[$key] = self::LABELS[$key] ?? (Page::load($key)['meta']['h1'] ?? $key);
        }
        foreach (Db::run('SELECT slug, name FROM poles ORDER BY position')->fetchAll() as $p) {
            $pages[$p['slug']] = $p['name'];
        }
        return $pages;
    }

    /** Adresse publique d'une page ou d'un onglet de pôle. */
    public static function publicUrl(string $key): string
    {
        $path = array_search($key, SiteController::PAGES, true);
        return $path !== false ? (string) $path : '/les-poles/#' . $key;
    }

    /** @return array<string, string> pages modifiables par l'utilisateur connecté */
    public static function allowedPages(): array
    {
        $user = Auth::user();
        return $user === null ? [] : self::allowed($user);
    }

    public static function index(): void
    {
        $user = Auth::require();
        $updated = Db::run('SELECT page_key, MAX(updated_at) AS u, COUNT(DISTINCT section_key) - 1 AS n FROM page_contents GROUP BY page_key')->fetchAll(\PDO::FETCH_UNIQUE);
        View::render('admin/content/index', [
            'title' => 'Pages du site',
            'pages' => self::allowed($user),
            'updated' => $updated,
        ], 'admin/layout');
    }

    /** Vue d'ensemble d'une page : une carte par section, chacune modifiable dans une fenêtre. */
    public static function edit(string $key): void
    {
        $user = Auth::require();
        self::guard($user, $key);
        View::render('admin/content/edit', [
            'title' => 'Page « ' . self::pages()[$key] . ' »',
            'viewUrl' => self::publicUrl($key),
            'key' => $key,
            'label' => self::pages()[$key],
            'page' => Page::load($key) ?? ['meta' => [], 'sections' => []],
            'isFragment' => !in_array($key, SiteController::PAGES, true),
            'library' => MediaController::library(),
        ], 'admin/layout');
    }

    public static function head(string $key): void
    {
        $user = Auth::require();
        self::guard($user, $key);
        if (!in_array($key, SiteController::PAGES, true)) {
            redirect("/admin/pages/$key");
        }
        View::render('admin/content/head', [
            'title' => 'En-tête de la page',
            'key' => $key,
            'label' => self::pages()[$key],
            'meta' => (Page::load($key) ?? ['meta' => []])['meta'],
        ], 'admin/layout');
    }

    public static function headUpdate(string $key): void
    {
        $user = Auth::require();
        self::guard($user, $key);
        Csrf::check();
        $meta = (Page::load($key) ?? ['meta' => []])['meta'];
        $values = [];
        foreach (['h1', 'intro'] as $field) {
            if (isset($_POST['meta'][$field])) {
                $values[$field] = trim((string) preg_replace('/\s+/u', ' ', (string) $_POST['meta'][$field]));
            }
        }
        // Boutons de l'en-tête : seuls les libellés sont modifiables, les liens restent ceux du fichier de contenu.
        // Boutons de l'en-tête : texte et destination, deux au plus ; un bouton sans texte est retiré.
        $actions = self::actions($meta['actions'] ?? '');
        $rejected = [];
        if (isset($_POST['actions']) && is_array($_POST['actions'])) {
            $actions = [];
            foreach (array_slice($_POST['actions'], 0, 2) as $a) {
                $label = trim((string) preg_replace('/\s+/u', ' ', str_replace(['|', ';'], ' ', (string) ($a['text'] ?? ''))));
                if ($label === '') {
                    continue;
                }
                $url = Links::resolve((string) ($a['link']['target'] ?? ''), (string) ($a['link']['free'] ?? ''));
                if ($url === null) {
                    $rejected[] = $label;
                    continue;
                }
                $actions[] = [$label, $url];
            }
            $values['actions'] = implode(' ; ', array_map(static fn (array $a): string => $a[0] . ' | ' . $a[1], $actions));
        }
        if ($rejected !== []) {
            Session::flash('Bouton « ' . implode(' », « ', $rejected) . ' » non enregistré : choisissez la page vers laquelle il mène.', 'error');
            redirect("/admin/pages/$key/en-tete");
        }
        if (($values['h1'] ?? 'x') === '') {
            Session::flash('Le grand titre de la page ne peut pas être vide.', 'error');
            redirect("/admin/pages/$key/en-tete");
        }
        foreach ($values as $field => $value) {
            self::upsert($key, '_meta', $field, $value, 0);
        }
        Auth::log('page.modification', "$key#en-tete");
        Session::flash('En-tête de la page enregistré.');
        redirect("/admin/pages/$key");
    }

    public static function section(string $key, string $section): void
    {
        $user = Auth::require();
        self::guard($user, $key);
        $s = self::findSection($key, $section);
        self::renderSection($key, $s, TextFields::parse($s['body'], $s['variant']), []);
    }

    public static function sectionUpdate(string $key, string $section): void
    {
        $user = Auth::require();
        self::guard($user, $key);
        Csrf::check();
        $s = self::findSection($key, $section);
        $units = TextFields::parse($s['body'], $s['variant']);
        $body = TextFields::apply($s['body'], $s['variant'], (array) ($_POST['units'] ?? []), (string) ($_POST['sig'] ?? ''));
        if ($body === null) {
            Session::flash('Cette section a été modifiée entre-temps : vos changements n\'ont pas été enregistrés. Rouvrez-la pour repartir de la dernière version.', 'error');
            redirect("/admin/pages/$key");
        }
        $title = trim((string) preg_replace('/\s+/u', ' ', (string) ($_POST['title'] ?? '')));
        if ($title === '') {
            Session::flash('Donnez un titre à la section.', 'error');
            self::renderSection($key, ['title' => '', 'body' => $body] + $s, TextFields::parse($body, $s['variant']), ['title' => 'Le titre est obligatoire.']);
            return;
        }
        $position = (int) Db::run("SELECT position FROM page_contents WHERE page_key = ? AND section_key = ? AND field_key = 'body'", [$key, $section])->fetchColumn();
        self::upsert($key, $section, 'title', $title, $position);
        self::upsert($key, $section, 'body', $body, $position, 'markdown');
        if (\App\Core\Upload::hasFile('photo_upload')) {
            $photo = MediaController::upload('photo_upload', $title);
            if (is_string($photo) && !str_starts_with($photo, 'media-')) {
                Session::flash($photo, 'error');
            } else {
                $_POST['image'] = $photo;
            }
        }
        if (isset($_POST['image']) && self::photoAllowed((string) $_POST['image'], $key, $section)) {
            self::upsert($key, $section, 'image', (string) $_POST['image'], $position);
        }
        Auth::log('page.modification', "$key#$section");
        Session::flash('Section « ' . $title . ' » enregistrée.');
        foreach (TextFields::$rejected as $name) {
            Session::flash('Le lien de « ' . $name . ' » n\'a pas été changé : l\'adresse saisie n\'est pas valable.', 'error');
        }
        redirect("/admin/pages/$key");
    }

    /**
     * Boutons de l'en-tête « Texte | lien ; Texte | lien ».
     * @return list<array{0: string, 1: string}>
     */
    public static function actions(string $value): array
    {
        $out = [];
        foreach (array_filter(array_map('trim', explode(';', $value))) as $part) {
            [$label, $href] = array_map('trim', explode('|', $part, 2)) + [1 => ''];
            if ($label !== '' && $href !== '') {
                $out[] = [$label, $href];
            }
        }
        return $out;
    }

    /**
     * @param array<string, string> $s
     * @param list<array<string, mixed>> $units
     * @param array<string, string> $errors
     */
    private static function renderSection(string $key, array $s, array $units, array $errors): void
    {
        $variants = explode(' ', $s['variant']);
        View::render('admin/content/section', [
            'title' => $s['title'] !== '' ? $s['title'] : 'Section sans titre',
            'key' => $key,
            'label' => self::pages()[$key],
            's' => $s,
            'units' => $units,
            'sig' => TextFields::signature($units),
            'kind' => in_array('frise', $variants, true) ? 'frise' : (in_array('blocs', $variants, true) || in_array('split', $variants, true) ? 'blocs' : ''),
            'hasPhoto' => in_array('split', $variants, true) || $s['image'] !== '',
            'library' => MediaController::library(),
            'viewUrl' => self::publicUrl($key) . (in_array($key, SiteController::PAGES, true) ? '#' . $s['key'] : ''),
            'errors' => $errors,
        ], 'admin/layout');
    }

    /** @return array{key: string, title: string, variant: string, image: string, body: string} */
    private static function findSection(string $key, string $section): array
    {
        foreach ((Page::load($key) ?? ['sections' => []])['sections'] as $s) {
            if ($s['key'] === $section) {
                return $s;
            }
        }
        http_response_code(404);
        exit('Section introuvable.');
    }

    /** Une photo n'a qu'un emploi sur le site : celles déjà utilisées ailleurs sont refusées. */
    private static function photoAllowed(string $photo, string $key, string $section): bool
    {
        if ($photo === '') {
            return true;
        }
        $item = MediaController::library()[$photo] ?? null;
        if ($item === null || $item['kind'] !== 'photo') {
            return false;
        }
        foreach ($item['uses'] as $use) {
            if (($use['page'] ?? null) !== $key || ($use['section'] ?? null) !== $section) {
                return false;
            }
        }
        return true;
    }

    private static function upsert(string $key, string $section, string $field, string $value, int $position, string $type = 'text'): void
    {
        Db::run(
            'INSERT INTO page_contents (page_key, section_key, field_key, content_type, value, position) VALUES (?, ?, ?, ?, ?, ?)
             ON CONFLICT(page_key, section_key, field_key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP',
            [$key, $section, $field, $type, $value, $position]
        );
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, string>
     */
    private static function allowed(array $user): array
    {
        $pages = self::pages();
        if ($user['role'] === 'admin') {
            return $pages;
        }
        $keys = match (Auth::scope($user)) {
            'ess29' => self::ESS29_PAGES,
            'tag29' => ['tag29'],
            default => [(string) Db::run('SELECT slug FROM poles WHERE id = ?', [$user['pole_id']])->fetchColumn()],
        };
        return array_intersect_key($pages, array_flip($keys));
    }

    /** @param array<string, mixed> $user */
    private static function guard(array $user, string $key): void
    {
        if (!array_key_exists($key, self::allowed($user))) {
            http_response_code(404);
            exit('Page introuvable.');
        }
    }
}
