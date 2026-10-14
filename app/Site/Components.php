<?php
declare(strict_types=1);

namespace App\Site;

use App\Core\Db;

/** Blocs dynamiques insérés par une section dont le corps est « @nom » ou « @nom:argument ». */
final class Components
{
    public static function isDirective(string $body): bool
    {
        return (bool) preg_match('/^@[a-z0-9_-]+(:[a-z0-9_-]+)?$/', trim($body));
    }

    /** Rendu du bloc ; chaîne vide si la donnée manque (la section est alors masquée). */
    public static function render(string $body, int $headingLevel = 3): string
    {
        [$name, $arg] = array_pad(explode(':', substr(trim($body), 1), 2), 2, null);
        $data = match ($name) {
            'poles', 'coordonnees' => ['poles' => self::poles()],
            'poles-onglets' => ['poles' => array_map(
                static fn (array $p): array => $p + ['page' => \App\Content\Page::load($p['slug'])],
                self::poles()
            )],
            'equipe' => ['groups' => self::team($arg)],
            'adherents' => ['groups' => self::members()],
            'documents' => ['documents' => self::documents((string) $arg)],
            'offres' => ['offers' => self::offers()],
            'partenaires' => ['partners' => Db::run('SELECT * FROM partners WHERE is_published = 1 ORDER BY kind, position')->fetchAll()],
            'qualiopi' => ['certificate' => Db::run("SELECT * FROM documents WHERE category = 'certificat' AND is_published = 1 ORDER BY year DESC LIMIT 1")->fetch() ?: null],
            'newsletter', 'contact' => ['poles' => self::poles()],
            'tag29-logos' => ['logos' => array_filter(self::tag29Logos((string) $arg), static fn (array $l): bool => is_file(ROOT . '/public' . $l['path']))],
            'identite' => ['head' => Db::run('SELECT * FROM poles WHERE is_head_office = 1')->fetch() ?: null],
            default => null,
        };
        if ($data === null || !is_file(ROOT . "/templates/site/components/$name.php")) {
            return '';
        }
        $data['h'] = $headingLevel;
        $data['arg'] = $arg;
        extract($data, EXTR_SKIP);
        ob_start();
        require ROOT . "/templates/site/components/$name.php";
        return trim((string) ob_get_clean());
    }

    /**
     * Adresse postale cliquable vers Google Maps (simple lien, aucun script ni carte embarquée).
     * $short : seulement « code postal ville » affiché (pied de page), le lien pointe vers l'adresse complète.
     * @param array<string, mixed> $p ligne de la table poles
     */
    public static function address(array $p, bool $short = false): string
    {
        $lines = array_filter(array_map('trim', explode("\n", (string) $p['address_lines'])));
        $city = trim($p['postal_code'] . ' ' . $p['city']);
        $url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(implode(', ', [...$lines, $city]));
        $text = $short ? e($city) : implode('<br>', array_map('e', [...$lines, $city]));
        return '<a class="address-link" href="' . e($url) . '" rel="noopener">' . $text . '<span class="visually-hidden"> (voir sur Google Maps)</span></a>';
    }

    /** Logo de pôle tiré de Charte/ (bin/build-images.php), ou null s'il n'existe pas (Cornouaille). */
    public static function poleLogo(string $slug): ?string
    {
        $file = "/assets/img/logo-pole-$slug.webp";
        return is_file(ROOT . '/public' . $file) ? $file : null;
    }

    /**
     * Logos de la page TAg29 (content/tag29/logos.tsv : groupe, nom, fichier source dans Charte/tag29/), WebP générés par bin/build-images.php.
     * @return list<array{name: string, source: string, path: string}>
     */
    public static function tag29Logos(string $group = ''): array
    {
        $rows = array_slice(file(ROOT . '/content/tag29/logos.tsv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], 1);
        $logos = [];
        foreach ($rows as $row) {
            [$g, $name, $source] = array_pad(explode("\t", $row), 3, '');
            if ($group === '' || $g === $group) {
                $slug = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT', $name))), '-');
                $logos[] = ['name' => $name, 'source' => $source, 'path' => "/assets/img/logos/tag29/$slug.webp"];
            }
        }
        return $logos;
    }

    /** @return list<array<string, mixed>> */
    public static function poles(): array
    {
        return Db::run('SELECT * FROM poles ORDER BY position')->fetchAll();
    }

    /** @return list<array{pole: ?array<string, mixed>, label: string, slug: string, members: list<array<string, mixed>>}> */
    private static function team(?string $poleSlug): array
    {
        $rows = Db::run(
            "SELECT t.*, p.slug AS pole_slug FROM team_members t LEFT JOIN poles p ON p.id = t.pole_id
             WHERE t.is_published = 1 AND (? IS NULL OR p.slug = ?)
             ORDER BY COALESCE(p.position, 99), CASE t.team WHEN 'salaries' THEN 0 WHEN 'ca' THEN 1 ELSE 2 END, t.position",
            [$poleSlug, $poleSlug]
        )->fetchAll();
        return self::groupByPole($rows);
    }

    /** @return list<array{pole: ?array<string, mixed>, label: string, slug: string, members: list<array<string, mixed>>}> */
    private static function members(): array
    {
        $rows = Db::run(
            'SELECT m.*, p.slug AS pole_slug FROM members m JOIN poles p ON p.id = m.pole_id
             WHERE m.is_published = 1 ORDER BY p.position, m.name COLLATE NOCASE'
        )->fetchAll();
        return self::groupByPole($rows);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{pole: ?array<string, mixed>, label: string, slug: string, members: list<array<string, mixed>>}>
     */
    private static function groupByPole(array $rows): array
    {
        $poles = array_column(self::poles(), null, 'slug');
        $groups = [];
        foreach ($rows as $row) {
            $slug = $row['pole_slug'] ?? 'tag29';
            $groups[$slug] ??= [
                'pole' => $poles[$slug] ?? null,
                'label' => $poles[$slug]['name'] ?? 'TAg29',
                'slug' => $slug,
                'members' => [],
            ];
            $groups[$slug]['members'][] = $row;
        }
        return array_values($groups);
    }

    /** @return list<array<string, mixed>> */
    private static function documents(string $filter): array
    {
        $isCategory = in_array($filter, ['fiche_pratique', 'ressource', 'document'], true);
        return Db::run(
            'SELECT d.* FROM documents d LEFT JOIN poles p ON p.id = d.pole_id
             WHERE d.is_published = 1 AND d.category != \'certificat\' AND ' . ($isCategory ? 'd.category = ?' : "p.slug = ? AND d.category = 'document'") . '
             ORDER BY d.year DESC, d.position',
            [$filter]
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    private static function offers(): array
    {
        return Db::run(
            "SELECT o.*, p.name AS pole FROM job_offers o LEFT JOIN poles p ON p.id = o.pole_id
             WHERE o.is_published = 1 AND (o.closes_on IS NULL OR o.closes_on >= date('now', 'localtime'))
             ORDER BY o.created_at DESC"
        )->fetchAll();
    }
}
