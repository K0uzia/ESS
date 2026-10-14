<?php
declare(strict_types=1);

namespace App\Site;

use App\Content\Page;
use App\Core\Db;

/** robots.txt, sitemap.xml et llms.txt, générés depuis la même base que les pages. */
final class Discovery
{
    public static function robots(): void
    {
        $site = rtrim(setting('site_url'), '/');
        header('Content-Type: text/plain; charset=UTF-8');
        echo "User-agent: *\nDisallow: /admin/\nDisallow: /newsletter/\nAllow: /\n\nSitemap: $site/sitemap.xml\n";
    }

    public static function sitemap(): void
    {
        $site = rtrim(setting('site_url'), '/');
        $updated = Db::run('SELECT page_key, MAX(updated_at) FROM page_contents GROUP BY page_key')->fetchAll(\PDO::FETCH_KEY_PAIR);
        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (SiteController::PAGES as $path => $key) {
            if (!isset($updated[$key])) {
                continue;
            }
            echo '  <url><loc>' . e($site . $path) . '</loc><lastmod>' . substr((string) $updated[$key], 0, 10) . "</lastmod></url>\n";
        }
        echo "</urlset>\n";
    }

    public static function llms(): void
    {
        $site = rtrim(setting('site_url'), '/');
        header('Content-Type: text/markdown; charset=UTF-8');
        $out = '# ' . setting('raison_sociale') . "\n\n> " . setting('baseline') . ' Réseau des trois pôles ESS du Finistère : Pays de Morlaix, Pays de Brest, Pays de Cornouaille. Contact : ' . setting('email_contact') . "\n\n## Pages\n\n";
        foreach (SiteController::PAGES as $path => $key) {
            $meta = Page::load($key)['meta'] ?? null;
            if ($meta !== null) {
                $out .= "- [{$meta['h1']}]($site$path)" . (!empty($meta['description']) ? ": {$meta['description']}" : '') . "\n";
            }
        }
        $out .= "\n## Pôles\n\n";
        foreach (Db::run('SELECT * FROM poles ORDER BY position')->fetchAll() as $p) {
            $out .= "- {$p['name']} : " . str_replace("\n", ', ', $p['address_lines']) . ", {$p['postal_code']} {$p['city']} ; " . str_replace("\n", ' / ', $p['phones']) . " ; {$p['email']}" . ($p['is_head_office'] ? ' (siège social)' : '') . "\n";
        }
        echo $out;
    }
}
