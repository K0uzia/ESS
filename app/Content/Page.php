<?php
declare(strict_types=1);

namespace App\Content;

use App\Core\Db;

final class Page
{
    /** @return array{meta: array<string, string>, sections: list<array{key: string, title: string, variant: string, image: string, body: string}>}|null */
    public static function load(string $pageKey): ?array
    {
        $rows = Db::run(
            'SELECT section_key, field_key, value, position FROM page_contents WHERE page_key = ? ORDER BY position, section_key',
            [$pageKey]
        )->fetchAll();
        if ($rows === []) {
            return null;
        }
        $meta = [];
        $sections = [];
        foreach ($rows as $r) {
            if ($r['section_key'] === '_meta') {
                $meta[$r['field_key']] = $r['value'];
                continue;
            }
            $sections[$r['section_key']] ??= ['key' => $r['section_key'], 'title' => '', 'variant' => '', 'image' => '', 'body' => ''];
            $sections[$r['section_key']][$r['field_key']] = $r['value'];
        }
        return ['meta' => $meta, 'sections' => array_values($sections)];
    }
}
