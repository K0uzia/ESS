<?php
declare(strict_types=1);

namespace App\Site;

/** Anciennes URL WordPress → pages cibles (config/redirections.tsv, issu de l'inventaire de l'ancien site). */
final class Redirects
{
    public static function find(string $path): ?string
    {
        $file = ROOT . '/config/redirections.tsv';
        $normalized = rtrim($path, '/') . '/';
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            [$from, $to] = array_pad(explode("\t", $line), 2, '/');
            if (str_ends_with($from, '*') ? str_starts_with($normalized, rtrim($from, '*')) : $normalized === $from) {
                return $to;
            }
        }
        // PDF de l'ancien site repris dans les documents : même nom de fichier.
        if (preg_match('#^/wp-content/uploads/\d{4}/\d{2}/([^/]+\.pdf)$#i', $path, $m) && is_file(ROOT . '/public/media/documents/' . $m[1])) {
            return '/media/documents/' . $m[1];
        }
        return null;
    }
}
