<?php
declare(strict_types=1);

namespace App\Content;

/** Lit un fichier content/seed/pages/<clé>.md (format : content/seed/FORMAT.md). */
final class SeedPage
{
    /** @return array{meta: array<string, string>, sections: list<array{key: string, title: string, variant: string, image: string, body: string}>} */
    public static function parse(string $file): array
    {
        $source = str_replace("\r\n", "\n", (string) file_get_contents($file));
        if (!preg_match('/^---\n(.*?)\n---\n(.*)$/s', $source, $m)) {
            throw new \RuntimeException("Front matter absent : $file");
        }
        $meta = [];
        foreach (explode("\n", $m[1]) as $line) {
            if (preg_match('/^(\w+):\s*(.*)$/', $line, $kv)) {
                $meta[$kv[1]] = trim($kv[2], " \"'");
            }
        }
        $sections = [];
        $parts = preg_split('/^## /m', $m[2]) ?: [];
        array_shift($parts);
        foreach ($parts as $part) {
            [$heading, $body] = array_pad(explode("\n", $part, 2), 2, '');
            if (!preg_match('/^(.*?)\s*\{#([a-z0-9-]+)((?:\s+(?:\.[a-z-]+|[a-z]+=[a-z0-9-]+))*)\}\s*$/', $heading, $h)) {
                throw new \RuntimeException("Section sans clé {#…} dans $file : $heading");
            }
            preg_match_all('/\.([a-z-]+)/', $h[3], $classes);
            preg_match('/image=([a-z0-9-]+)/', $h[3], $image);
            $sections[] = [
                'key' => $h[2],
                'title' => trim($h[1]),
                'variant' => implode(' ', $classes[1]),
                'image' => $image[1] ?? '',
                'body' => trim($body),
            ];
        }
        return ['meta' => $meta, 'sections' => $sections];
    }
}
