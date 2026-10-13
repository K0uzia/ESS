<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Site\Components;
use App\Site\Section;
use App\Site\SiteController;

/**
 * Médiathèque : toutes les images du site (photos de la charte, photos libres, logos) et les photos ajoutées.
 * Les images fournies avec le site sont générées au build (bin/build-images.php) : elles se choisissent ici
 * mais ne se suppriment pas.
 */
final class MediaController
{
    /** Description courte de chaque photo fournie avec le site (App\Site\Section::PHOTOS), pour la retrouver. */
    private const PHOTO_LABELS = [
        'accueil' => 'Groupe en réunion, rires',
        'reseau' => 'Rencontre debout entre acteurs',
        'equipe' => 'Deux collègues souriantes au bureau',
        'decouvrir' => 'Atelier post-it',
        'agir' => 'Personne écrivant au tableau',
        'former' => 'Groupe assis en cercle, formation',
        'contact' => 'Discussion autour d\'une table',
        'adherer' => 'Groupe assis dans un escalier (portrait)',
        'atelier' => 'Travail en binôme sur un document (portrait)',
        'collectif' => 'Collectif réuni dans un escalier (portrait)',
        'lettre' => 'Trois personnes autour d\'un café',
        'circuits-courts' => 'Légumes sur un marché',
        'sensibilisation' => 'Mains sur des documents, atelier',
        'emploi' => 'Mains au clavier, poste de travail',
        'formation' => 'Réunion de travail au bureau',
        'tag29' => 'Équipe au travail sur ordinateurs',
        'tag29-presentation' => 'Deux personnes sur ordinateurs, vue de dessus',
    ];

    /**
     * Images du site, clé = valeur enregistrée dans la section (nom de photo ou « media-<id> »).
     * @return array<string, array{kind: string, src: string, label: string, source: string, uses: list<array<string, string>>, id?: int, width: int, height: int}>
     */
    public static function library(): array
    {
        $pages = ContentController::pages();
        $uses = [];
        $rows = Db::run(
            "SELECT i.value, i.page_key, i.section_key, t.value AS title FROM page_contents i
             LEFT JOIN page_contents t ON t.page_key = i.page_key AND t.section_key = i.section_key AND t.field_key = 'title'
             WHERE i.field_key = 'image' AND i.value <> ''"
        )->fetchAll();
        foreach ($rows as $r) {
            $uses[$r['value']][] = [
                'label' => ($pages[$r['page_key']] ?? $r['page_key']) . ' › ' . ($r['title'] ?: 'section'),
                'href' => '/admin/pages/' . $r['page_key'] . '/sections/' . $r['section_key'],
                'page' => $r['page_key'],
                'section' => $r['section_key'],
            ];
        }
        $uses['accueil'][] = ['label' => 'Accueil › grand bandeau', 'href' => '/admin/pages/accueil'];
        foreach (SiteController::HEAD_PHOTO as $page => $photo) {
            $uses[$photo][] = ['label' => ($pages[$page] ?? $page) . ' › en-tête', 'href' => '/admin/pages/' . $page];
        }

        $items = [];
        foreach (Db::run("SELECT * FROM media WHERE path LIKE '/media/photos/%' ORDER BY id DESC")->fetchAll() as $m) {
            $variants = json_decode((string) $m['variants'], true) ?: [];
            ksort($variants);
            $items['media-' . $m['id']] = [
                'kind' => 'photo',
                'src' => (string) ($variants !== [] ? reset($variants) : $m['path']),
                'label' => $m['alt'] !== '' ? $m['alt'] : basename($m['path']),
                'source' => 'Ajoutée',
                'uses' => $uses['media-' . $m['id']] ?? [],
                'id' => (int) $m['id'],
                'width' => (int) $m['width'],
                'height' => (int) $m['height'],
            ];
        }
        foreach (Section::PHOTOS as $name => [$file, $ratio, , $widths]) {
            $w = min($widths);
            $items[$name] = [
                'kind' => 'photo',
                'src' => "/assets/img/photos/$name-$w.webp",
                'label' => self::PHOTO_LABELS[$name] ?? ucfirst(str_replace('-', ' ', $name)),
                'source' => str_starts_with($file, 'Charte/') ? 'Charte ESS Bretagne' : 'Libre de droits',
                'uses' => $uses[$name] ?? [],
                'width' => $w,
                'height' => (int) round($w / $ratio),
            ];
        }
        $logo = static function (string $label, string $src, string $source, array $use) use (&$items): void {
            $file = ROOT . '/public' . $src;
            if (!is_file($file)) {
                return;
            }
            [$w, $h] = getimagesize($file) ?: [0, 0];
            $items['logo:' . $src] = ['kind' => 'logo', 'src' => $src, 'label' => $label, 'source' => $source, 'uses' => [$use], 'width' => (int) $w, 'height' => (int) $h];
        };
        $logo('Logo ESS Finistère', '/assets/img/logo-ess-finistere-couleur.webp', 'Charte ESS Bretagne', ['label' => 'En-tête et pied de page du site']);
        foreach (Db::run('SELECT slug, name FROM poles ORDER BY position')->fetchAll() as $p) {
            $logo('Logo du ' . $p['name'], '/assets/img/logo-pole-' . $p['slug'] . '.webp', 'Charte du pôle', ['label' => 'Les pôles › ' . $p['name'], 'href' => '/admin/pages/' . $p['slug']]);
        }
        $logo('Logo TAg29', '/assets/img/logo-tag29-noir.webp', 'Charte TAg29', ['label' => 'Tag29 › en-tête', 'href' => '/admin/pages/tag29']);
        $logo('Qualiopi', '/assets/img/logos/qualiopi.webp', 'Certification', ['label' => 'Accueil › Qualiopi', 'href' => '/admin/documents']);
        foreach (Db::run("SELECT id, name, logo_path FROM partners WHERE logo_path <> '' ORDER BY kind, position")->fetchAll() as $p) {
            $logo($p['name'], (string) $p['logo_path'], 'Partenaire', ['label' => 'Partenaires et réseaux', 'href' => '/admin/partenaires/' . (int) $p['id']]);
        }
        foreach (Components::tag29Logos() as $l) {
            $logo($l['name'], $l['path'], 'TAg29', ['label' => 'Tag29 › logos', 'href' => '/admin/pages/tag29']);
        }
        return $items;
    }

    public static function index(): void
    {
        Auth::require();
        View::render('admin/media/index', ['title' => 'Médiathèque', 'library' => self::library()], 'admin/layout');
    }

    public static function create(): void
    {
        Auth::require();
        View::render('admin/media/form', ['title' => 'Ajouter une photo', 'item' => null], 'admin/layout');
    }

    public static function store(): void
    {
        Auth::require();
        Csrf::check();
        $alt = trim((string) ($_POST['alt'] ?? ''));
        if (empty($_POST['rights'])) {
            Session::flash('Confirmez que la photo est réelle et que vous avez le droit de la publier.', 'error');
            redirect('/admin/medias/nouvelle');
        }
        $id = self::upload('photo', $alt);
        if (!str_starts_with($id, 'media-')) {
            Session::flash($id, 'error');
            redirect('/admin/medias/nouvelle');
        }
        Session::flash('Photo ajoutée. Choisissez-la depuis une section de page, avec « Changer la photo ».');
        redirect('/admin/medias');
    }

    /**
     * Enregistre une photo envoyée dans la médiathèque. Retourne « media-<id> », ou le message d'erreur.
     * L'accord de publication (case « rights ») est exigé.
     */
    public static function upload(string $field, string $alt): string
    {
        if (empty($_POST['rights'])) {
            return 'Photo non ajoutée : confirmez qu\'elle est réelle et que vous avez le droit de la publier.';
        }
        $up = Upload::image($field, 'photos', [800, 1600]);
        if (is_string($up)) {
            return 'Photo non ajoutée : ' . lcfirst($up);
        }
        Db::run(
            'INSERT INTO media (path, variants, alt, width, height, mime, size) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$up['path'], json_encode($up['variants']), $alt, $up['width'], $up['height'], 'image/webp', $up['size']]
        );
        Auth::log('media.ajout', $up['path']);
        return 'media-' . Db::pdo()->lastInsertId();
    }

    public static function edit(string $id): void
    {
        Auth::require();
        $item = self::library()['media-' . (int) $id] ?? null;
        if ($item === null) {
            redirect('/admin/medias');
        }
        View::render('admin/media/form', ['title' => 'Photo', 'item' => $item], 'admin/layout');
    }

    public static function update(string $id): void
    {
        Auth::require();
        Csrf::check();
        Db::run('UPDATE media SET alt = ? WHERE id = ?', [trim((string) ($_POST['alt'] ?? '')), (int) $id]);
        Session::flash('Description enregistrée.');
        redirect('/admin/medias');
    }

    public static function destroy(string $id): void
    {
        Auth::require();
        Csrf::check();
        $m = Db::run('SELECT * FROM media WHERE id = ?', [(int) $id])->fetch();
        if (!$m) {
            redirect('/admin/medias');
        }
        if (Db::run("SELECT 1 FROM page_contents WHERE field_key = 'image' AND value = ?", ['media-' . $m['id']])->fetchColumn()) {
            Session::flash('Cette photo est utilisée dans une page : choisissez-en une autre dans la section avant de la supprimer.', 'error');
            redirect('/admin/medias');
        }
        foreach (json_decode((string) $m['variants'], true) ?: [] as $path) {
            @unlink(ROOT . '/public' . $path);
        }
        Db::run('DELETE FROM media WHERE id = ?', [$m['id']]);
        Auth::log('media.suppression', $m['path']);
        Session::flash('Photo supprimée.');
        redirect('/admin/medias');
    }
}
