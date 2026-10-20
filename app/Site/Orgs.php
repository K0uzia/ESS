<?php
declare(strict_types=1);

namespace App\Site;

use App\Core\Db;

/**
 * Organismes du site : les trois pôles (clé = slug), ESS29 et TAg29. Chacun a une adresse qui reçoit le formulaire
 * de contact, une adresse de réponse et deux newsletters (adhérents, inscrits via le site). Réglages : page E-mails.
 */
final class Orgs
{
    public const AUDIENCES = ['adherents' => 'Adhérents', 'inscrits' => 'Inscrits via le site'];

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $all = null;

    /**
     * Pôles d'abord (ordre du site), puis ESS29 et TAg29. ESS29 et TAg29 ont l'adresse postale du siège.
     * @return array<string, array{key: string, name: string, short: string, pole_id: ?int, address_lines: string, postal_code: string, city: string, email: string}>
     */
    public static function all(): array
    {
        if (self::$all !== null) {
            return self::$all;
        }
        $all = [];
        $head = null;
        foreach (Db::run('SELECT * FROM poles ORDER BY position')->fetchAll() as $p) {
            $all[$p['slug']] = [
                'key' => $p['slug'],
                'name' => $p['name'],
                'short' => preg_replace('/^Pôle ESS Pays de /', '', (string) $p['name']),
                'pole_id' => (int) $p['id'],
                'address_lines' => $p['address_lines'],
                'postal_code' => $p['postal_code'],
                'city' => $p['city'],
                'email' => $p['email'],
            ];
            if ((int) $p['is_head_office'] === 1) {
                $head = $p;
            }
        }
        $address = ['address_lines' => $head['address_lines'] ?? '', 'postal_code' => $head['postal_code'] ?? '', 'city' => $head['city'] ?? ''];
        $all['ess29'] = ['key' => 'ess29', 'name' => 'ESS29', 'short' => 'ESS29', 'pole_id' => null, 'email' => setting('email_contact')] + $address;
        $all['tag29'] = ['key' => 'tag29', 'name' => 'TAg29', 'short' => 'TAg29', 'pole_id' => null, 'email' => self::address('tag29', 'reponse')] + $address;
        return self::$all = $all;
    }

    /** @return array<string, mixed>|null */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** Clé de l'organisme d'un pôle. */
    public static function ofPole(int $poleId): ?string
    {
        foreach (self::all() as $key => $org) {
            if ($org['pole_id'] === $poleId) {
                return $key;
            }
        }
        return null;
    }

    /** Adresse de réception du formulaire de contact (« contact ») ou adresse de réponse (« reponse »). */
    public static function address(string $org, string $purpose): string
    {
        return (string) (Db::run('SELECT email FROM mail_addresses WHERE org = ? AND purpose = ?', [$org, $purpose])->fetchColumn() ?: '');
    }

    /**
     * Listes de newsletters, avec le nom de l'organisme ; seulement les listes ouvertes si $openOnly.
     * @param list<string>|null $orgs organismes à garder (null : tous)
     * @return list<array<string, mixed>>
     */
    public static function lists(?array $orgs = null, bool $openOnly = true): array
    {
        $all = self::all();
        $lists = [];
        foreach (Db::run('SELECT * FROM newsletter_lists ORDER BY id')->fetchAll() as $l) {
            if (!isset($all[$l['org']]) || ($orgs !== null && !in_array($l['org'], $orgs, true)) || ($openOnly && !$l['is_open'])) {
                continue;
            }
            $lists[] = $l + ['org_name' => $all[$l['org']]['name'], 'label' => self::listLabel($l)];
        }
        // Rangées par organisme dans l'ordre du site, adhérents puis inscrits.
        $rank = array_flip(array_keys($all));
        usort($lists, static fn (array $a, array $b): int => [$rank[$a['org']], $a['audience']] <=> [$rank[$b['org']], $b['audience']]);
        return $lists;
    }

    /** @return array<string, mixed>|null */
    public static function findList(int $id): ?array
    {
        foreach (self::lists(null, false) as $l) {
            if ((int) $l['id'] === $id) {
                return $l;
            }
        }
        return null;
    }

    /** @param array<string, mixed> $list */
    public static function listLabel(array $list): string
    {
        return (self::all()[$list['org']]['name'] ?? $list['org']) . ' · ' . self::AUDIENCES[$list['audience']];
    }
}
