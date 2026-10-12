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

/** Contenus des pages : métadonnées et sections (titre, mise en page, photo, texte). */
final class ContentController
{
    public const LAYOUTS = [
        '' => 'Texte simple',
        'split' => 'Texte et photo',
        'cartes' => 'Cartes (liste « - !picto [Titre](lien) : texte »)',
        'blocs' => 'Blocs (sous-parties « ### » en cartes)',
        'frise' => 'Frise chronologique (sous-parties « ### »)',
        'etapes' => 'Étapes numérotées (liste « 1. »)',
        'temoignages' => 'Témoignages (citations « > »)',
        'encart' => 'Bandeau d\'appel (fond sombre, centré)',
    ];
    public const TONES = ['' => 'Automatique', 'fond' => 'Teinté', 'sombre' => 'Sombre (dégradé Glaz)'];

    /** @return array<string, string> clé de page → libellé */
    public static function pages(): array
    {
        $pages = [];
        foreach (SiteController::PAGES as $path => $key) {
            $pages[$key] = (Page::load($key)['meta']['h1'] ?? $key) . ' (' . $path . ')';
        }
        foreach (Db::run('SELECT slug, name FROM poles ORDER BY position')->fetchAll() as $p) {
            $pages[$p['slug']] = $p['name'] . ' (onglet de /les-poles/)';
        }
        return $pages;
    }

    public static function index(): void
    {
        $user = Auth::require();
        $updated = Db::run('SELECT page_key, MAX(updated_at) AS u, COUNT(DISTINCT section_key) - 1 AS n FROM page_contents GROUP BY page_key')->fetchAll(\PDO::FETCH_UNIQUE);
        View::render('admin/content/index', [
            'title' => 'Pages',
            'pages' => self::allowed($user),
            'updated' => $updated,
        ], 'admin/layout');
    }

    public static function edit(string $key): void
    {
        $user = Auth::require();
        self::guard($user, $key);
        View::render('admin/content/edit', [
            'title' => 'Modifier la page',
            'key' => $key,
            'label' => self::pages()[$key],
            'page' => Page::load($key) ?? ['meta' => [], 'sections' => []],
            'isFragment' => !in_array($key, SiteController::PAGES, true),
            'photos' => self::photos(),
            'errors' => [],
        ], 'admin/layout');
    }

    public static function update(string $key): void
    {
        $user = Auth::require();
        self::guard($user, $key);
        Csrf::check();
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        $upsert = $pdo->prepare(
            'INSERT INTO page_contents (page_key, section_key, field_key, content_type, value, position) VALUES (?, ?, ?, ?, ?, ?)
             ON CONFLICT(page_key, section_key, field_key) DO UPDATE SET value = excluded.value, position = excluded.position, updated_at = CURRENT_TIMESTAMP'
        );
        foreach (['title', 'description', 'h1', 'intro'] as $field) {
            $upsert->execute([$key, '_meta', $field, 'text', trim((string) ($_POST['meta'][$field] ?? '')), 0]);
        }
        foreach ((array) ($_POST['sections'] ?? []) as $sectionKey => $s) {
            if (!preg_match('/^[a-z0-9-]{1,60}$/', (string) $sectionKey) || !is_array($s)) {
                continue;
            }
            if (!empty($s['delete'])) {
                Db::run('DELETE FROM page_contents WHERE page_key = ? AND section_key = ?', [$key, $sectionKey]);
                continue;
            }
            $layout = array_key_exists($s['layout'] ?? '', self::LAYOUTS) ? (string) $s['layout'] : '';
            $tone = array_key_exists($s['tone'] ?? '', self::TONES) ? (string) $s['tone'] : '';
            $variant = trim(implode(' ', array_filter([$layout, !empty($s['inverse']) && $layout === 'split' ? 'inverse' : '', $tone])));
            $image = array_key_exists($s['image'] ?? '', self::photos()) ? (string) $s['image'] : '';
            $position = (int) ($s['position'] ?? 0);
            $upsert->execute([$key, $sectionKey, 'title', 'text', trim((string) ($s['title'] ?? '')), $position]);
            $upsert->execute([$key, $sectionKey, 'variant', 'text', $variant, $position]);
            $upsert->execute([$key, $sectionKey, 'image', 'text', $image, $position]);
            $upsert->execute([$key, $sectionKey, 'body', 'markdown', str_replace("\r\n", "\n", trim((string) ($s['body'] ?? ''))), $position]);
        }
        $pdo->commit();
        Auth::log('page.modification', $key);
        Session::flash('Page enregistrée.');
        redirect("/admin/pages/$key");
    }

    public static function addSection(string $key): void
    {
        $user = Auth::require();
        self::guard($user, $key);
        Csrf::check();
        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            Session::flash('Donnez un titre à la nouvelle section.', 'error');
            redirect("/admin/pages/$key");
        }
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title))), '-') ?: 'section';
        $sectionKey = substr($base, 0, 50);
        $n = 2;
        while (Db::run('SELECT 1 FROM page_contents WHERE page_key = ? AND section_key = ?', [$key, $sectionKey])->fetchColumn()) {
            $sectionKey = substr($base, 0, 46) . '-' . $n++;
        }
        $position = (int) Db::run('SELECT COALESCE(MAX(position), 0) + 1 FROM page_contents WHERE page_key = ?', [$key])->fetchColumn();
        foreach (['title' => $title, 'variant' => '', 'image' => '', 'body' => ''] as $field => $value) {
            Db::run(
                'INSERT INTO page_contents (page_key, section_key, field_key, content_type, value, position) VALUES (?, ?, ?, ?, ?, ?)',
                [$key, $sectionKey, $field, $field === 'body' ? 'markdown' : 'text', $value, $position]
            );
        }
        Auth::log('page.section_ajout', "$key#$sectionKey");
        Session::flash('Section ajoutée en fin de page : complétez son texte puis enregistrez.');
        redirect("/admin/pages/$key#section-$sectionKey");
    }

    /** @return array<string, string> valeur → libellé */
    public static function photos(): array
    {
        $photos = ['' => 'Aucune'];
        foreach (['accueil', 'reseau', 'equipe', 'decouvrir', 'agir', 'former', 'contact', 'adherer', 'atelier', 'collectif'] as $name) {
            $photos[$name] = 'Charte : ' . $name;
        }
        foreach (Db::run("SELECT id, alt, path FROM media WHERE path LIKE '/media/photos/%' ORDER BY id DESC")->fetchAll() as $m) {
            $photos['media-' . $m['id']] = 'Médiathèque : ' . ($m['alt'] !== '' ? $m['alt'] : basename($m['path']));
        }
        return $photos;
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
        $slug = (string) Db::run('SELECT slug FROM poles WHERE id = ?', [$user['pole_id']])->fetchColumn();
        return array_intersect_key($pages, [$slug => true]);
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
