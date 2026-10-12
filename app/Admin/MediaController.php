<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;

/** Photos réelles fournies par les pôles, utilisables comme photo de section. */
final class MediaController
{
    public static function index(): void
    {
        Auth::require();
        $media = Db::run("SELECT * FROM media WHERE path LIKE '/media/photos/%' ORDER BY id DESC")->fetchAll();
        $used = Db::run("SELECT value, COUNT(*) FROM page_contents WHERE field_key = 'image' AND value LIKE 'media-%' GROUP BY value")->fetchAll(\PDO::FETCH_KEY_PAIR);
        View::render('admin/media/index', ['title' => 'Médiathèque', 'media' => $media, 'used' => $used], 'admin/layout');
    }

    public static function store(): void
    {
        Auth::require();
        Csrf::check();
        $alt = trim((string) ($_POST['alt'] ?? ''));
        if (empty($_POST['rights'])) {
            Session::flash('Confirmez que la photo est réelle et que vous avez le droit de la publier.', 'error');
            redirect('/admin/medias');
        }
        $up = Upload::image('photo', 'photos', [800, 1600]);
        if (is_string($up)) {
            Session::flash($up, 'error');
            redirect('/admin/medias');
        }
        Db::run(
            'INSERT INTO media (path, variants, alt, width, height, mime, size) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$up['path'], json_encode($up['variants']), $alt, $up['width'], $up['height'], 'image/webp', $up['size']]
        );
        Auth::log('media.ajout', $up['path']);
        Session::flash('Photo ajoutée. Choisissez-la dans une section de page (mise en page « Texte et photo »).');
        redirect('/admin/medias');
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
            Session::flash('Cette photo est utilisée dans une page : retirez-la de la section avant de la supprimer.', 'error');
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
