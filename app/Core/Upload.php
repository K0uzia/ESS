<?php
declare(strict_types=1);

namespace App\Core;

/** Fichiers envoyés depuis l'admin : type vérifié par le contenu, nom aléatoire, images ré-encodées (aucune charge utile conservée). */
final class Upload
{
    public const MAX_PDF = 20 * 1024 * 1024;
    public const MAX_IMAGE = 15 * 1024 * 1024;

    /** @return array{path: string, size: int}|string Chemin public, ou message d'erreur. */
    public static function pdf(string $field): array|string
    {
        $file = self::file($field);
        if (is_string($file)) {
            return $file;
        }
        if ($file['size'] > self::MAX_PDF) {
            return 'Le PDF dépasse 20 Mo.';
        }
        if ((new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) !== 'application/pdf') {
            return 'Le fichier n\'est pas un PDF.';
        }
        $base = self::slug(pathinfo($file['name'], PATHINFO_FILENAME));
        $name = $base . '-' . bin2hex(random_bytes(3)) . '.pdf';
        $dir = ROOT . '/public/media/documents';
        is_dir($dir) || mkdir($dir, 0775, true);
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            return 'Enregistrement du fichier impossible.';
        }
        return ['path' => "/media/documents/$name", 'size' => (int) filesize("$dir/$name")];
    }

    /**
     * Image ré-encodée en WebP. $widths : largeurs à produire (la plus grande ≤ original) ; $maxHeight limite la hauteur (logos).
     * @param list<int> $widths
     * @return array{path: string, width: int, height: int, size: int, variants: array<int, string>}|string
     */
    public static function image(string $field, string $folder, array $widths, ?int $maxHeight = null): array|string
    {
        $file = self::file($field);
        if (is_string($file)) {
            return $file;
        }
        if ($file['size'] > self::MAX_IMAGE) {
            return 'L\'image dépasse 15 Mo.';
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'Format accepté : JPG, PNG ou WebP.';
        }
        ini_set('memory_limit', '1G');
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($src === false) {
            return 'Image illisible.';
        }
        $w = imagesx($src);
        $h = imagesy($src);
        $dir = ROOT . "/public/media/$folder";
        is_dir($dir) || mkdir($dir, 0775, true);
        $base = self::slug(pathinfo($file['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(3));
        $variants = [];
        foreach ($widths as $target) {
            $tw = min($target, $w);
            if ($maxHeight !== null) {
                $tw = min($tw, (int) round($w * $maxHeight / $h));
            }
            $th = max(1, (int) round($h * $tw / $w));
            $dst = imagecreatetruecolor($tw, $th);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $tw, $th, $w, $h);
            $name = "$base-$tw.webp";
            imagewebp($dst, "$dir/$name", 72);
            $variants[$tw] = "/media/$folder/$name";
        }
        ksort($variants);
        $largest = (int) array_key_last($variants);
        $path = $variants[$largest];
        [$fw, $fh] = getimagesize(ROOT . '/public' . $path) ?: [0, 0];
        return ['path' => $path, 'width' => $fw, 'height' => $fh, 'size' => (int) filesize(ROOT . '/public' . $path), 'variants' => $variants];
    }

    /** @return array{name: string, tmp_name: string, size: int}|string */
    private static function file(string $field): array|string
    {
        $f = $_FILES[$field] ?? null;
        if (!is_array($f) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'Aucun fichier envoyé.';
        }
        if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
            return 'L\'envoi du fichier a échoué (taille maximale du serveur dépassée ?).';
        }
        return ['name' => (string) $f['name'], 'tmp_name' => (string) $f['tmp_name'], 'size' => (int) $f['size']];
    }

    public static function hasFile(string $field): bool
    {
        return isset($_FILES[$field]) && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Portrait recadré en carré (centré, un peu vers le haut pour garder le visage), WebP de 320 px.
     * @return array{path: string}|string Chemin public, ou message d'erreur.
     */
    public static function portrait(string $field): array|string
    {
        $file = self::file($field);
        if (is_string($file)) {
            return $file;
        }
        if ($file['size'] > self::MAX_IMAGE) {
            return 'L\'image dépasse 15 Mo.';
        }
        if (!in_array((new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']), ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return 'Format accepté : JPG, PNG ou WebP.';
        }
        ini_set('memory_limit', '1G');
        $src = @imagecreatefromstring((string) file_get_contents($file['tmp_name']));
        if ($src === false) {
            return 'Image illisible.';
        }
        $side = min(imagesx($src), imagesy($src));
        $size = min(320, $side);
        $dst = imagecreatetruecolor($size, $size);
        imagecopyresampled($dst, $src, 0, 0, intdiv(imagesx($src) - $side, 2), intdiv(imagesy($src) - $side, 3), $size, $size, $side, $side);
        $dir = ROOT . '/public/media/portraits';
        is_dir($dir) || mkdir($dir, 0775, true);
        $name = self::slug(pathinfo($file['name'], PATHINFO_FILENAME)) . '-' . bin2hex(random_bytes(3)) . '.webp';
        imagewebp($dst, "$dir/$name", 78);
        return ['path' => "/media/portraits/$name"];
    }

    private static function slug(string $value): string
    {
        $value = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value)));
        return trim(substr($value, 0, 60), '-') ?: 'fichier';
    }
}
