<?php
declare(strict_types=1);

namespace App\Admin;

/** Petits éléments d'interface partagés par les vues du tableau de bord. */
final class Ui
{
    /** Tracés 24×24 au trait (stroke), locaux : aucune police d'icônes ni CDN. */
    private const ICONS = [
        'home' => 'M3 11 12 4l9 7M5 9.5V20h5v-6h4v6h5V9.5',
        'page' => 'M6 3h8l4 4v14H6zM14 3v4h4M9 12h6M9 16h6',
        'image' => 'M3 5h18v14H3zM3 16l5-5 4 4 3-3 6 6M15.5 9.5h.01',
        'folder' => 'M3 6h6l2 2h10v11H3z',
        'users' => 'M9 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM3 20c0-3.3 2.7-6 6-6s6 2.7 6 6M16 11a2.5 2.5 0 1 0 0-5M17 14c2.3.4 4 2.6 4 5',
        'pin' => 'M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11zM12 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
        'briefcase' => 'M3 7h18v13H3zM8 7V4h8v3M3 12.5h18',
        'mail' => 'M3 5h18v14H3zM3 7l9 6 9-6',
        'inbox' => 'M3 13h5l1.5 3h5l1.5-3h5M5.5 5h13L21 13v6H3v-6z',
        'link' => 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1',
        'building' => 'M4 21V5l8-2v18M12 8h8v13M2 21h20M8 8h.01M8 12h.01M8 16h.01M16 12h.01M16 16h.01',
        'id' => 'M3 5h18v14H3zM9 12a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM6 16c.5-1.4 1.6-2 3-2s2.5.6 3 2M14.5 10h4M14.5 14h3',
        'key' => 'M8 19a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM11 12l9-9M16 7l3 3',
        'lock' => 'M5 11h14v10H5zM8 11V7a4 4 0 0 1 8 0v4',
        'external' => 'M14 4h6v6M20 4l-9 9M18 14v6H4V6h6',
        'logout' => 'M15 4h4v16h-4M10 8l-4 4 4 4M6 12h10',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'close' => 'M6 6l12 12M18 6 6 18',
        'plus' => 'M12 5v14M5 12h14',
        'back' => 'M19 12H5M11 6l-6 6 6 6',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
        'alert' => 'M12 3 2 20h20zM12 10v4M12 17h.01',
        'check' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM8 12l3 3 5-6',
        'search' => 'M11 17a6 6 0 1 0 0-12 6 6 0 0 0 0 12zM20 20l-4.5-4.5',
        'download' => 'M12 4v11M7 10l5 5 5-5M4 20h16',
        'upload' => 'M12 16V5M7 10l5-5 5 5M4 20h16',
        'trash' => 'M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3',
        'eye' => 'M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12zM12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z',
        'eye-off' => 'M3 3l18 18M10.6 5.1A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A17 17 0 0 0 2 12s3.6 7 10 7a9.6 9.6 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2',
        'send' => 'M21 3 3 10l7 3 3 7zM10 13l11-10',
        'edit' => 'M4 20h4L19 9l-4-4L4 16zM13.5 6.5l4 4',
        'up' => 'M12 19V5M6 11l6-6 6 6',
        'down' => 'M12 5v14M6 13l6 6 6-6',
        'chevron' => 'M9 6l6 6-6 6',
        'bold' => 'M7 5h6a3.5 3.5 0 0 1 0 7H7zM7 12h7a3.5 3.5 0 0 1 0 7H7z',
        'italic' => 'M10 5h8M6 19h8M14 5l-4 14',
        'list' => 'M9 6h11M9 12h11M9 18h11M4.5 6h.01M4.5 12h.01M4.5 18h.01',
        'monitor' => 'M3 4h18v12H3zM8 20h8M12 16v4',
        'phone' => 'M7 2h10v20H7zM11 18h2',
        'olist' => 'M10 6h10M10 12h10M10 18h10M4 4h1.5v5M4 9h3M4 14.5a1.5 1.5 0 0 1 3 0c0 1.5-3 2-3 4.5h3',
        'heading' => 'M6 5v14M18 5v14M6 12h12',
        'button' => 'M3 8h18v8H3zM8 12h8',
        'help' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM9.5 9a2.5 2.5 0 0 1 4.9.7c0 1.8-2.4 2.1-2.4 3.8M12 17h.01',
    ];

    /** Icône décorative : le nom accessible est porté par le texte du contrôle. */
    public static function icon(string $name): string
    {
        $d = self::ICONS[$name] ?? self::ICONS['page'];
        return '<svg class="icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="' . $d . '"/></svg>';
    }

    /**
     * Champ mot de passe avec bouton « œil » pour afficher la saisie (le bouton n'apparaît qu'avec JavaScript).
     * $attrs : attributs supplémentaires déjà échappés (required, aria-*, minlength…).
     */
    public static function password(string $id, string $name, string $autocomplete, string $attrs = ''): string
    {
        return '<div class="password"><input id="' . e($id) . '" name="' . e($name) . '" type="password" autocomplete="' . e($autocomplete) . '"' . $attrs . '>'
            . '<button class="password__toggle" type="button" aria-controls="' . e($id) . '" aria-pressed="false" data-password-toggle hidden>'
            . str_replace('class="icon"', 'class="icon icon--show"', self::icon('eye'))
            . str_replace('class="icon"', 'class="icon icon--hide"', self::icon('eye-off'))
            . '<span class="visually-hidden">Afficher le mot de passe</span></button></div>';
    }

    /** Bloc dynamique d'une section (ligne « @nom ») → [ce qu'il affiche, module où le modifier]. */
    public static function component(string $body): ?array
    {
        if (!preg_match('/^@([a-z_-]+)/m', $body, $m)) {
            return null;
        }
        return match ($m[1]) {
            'equipe' => ['la liste de l\'équipe', '/admin/equipe', 'Équipe'],
            'adherents' => ['la liste et la carte des adhérents', '/admin/adherents', 'Adhérents'],
            'documents', 'qualiopi' => ['des documents', '/admin/documents', 'Documents'],
            'offres' => ['les offres d\'emploi', '/admin/offres', 'Offres d\'emploi'],
            'partenaires' => ['les logos des partenaires', '/admin/partenaires', 'Partenaires'],
            'newsletter' => ['l\'inscription aux newsletters', '/admin/newsletters', 'Newsletters'],
            'contact' => ['le formulaire de contact', null, null],
            'identite' => ['l\'identité du groupement', null, null],
            'poles', 'poles-onglets' => ['les coordonnées des pôles', null, null],
            default => ['un contenu automatique', null, null],
        };
    }

    /** « Pôle ESS Pays de Brest » → « Brest », pour les colonnes étroites. */
    public static function poleShort(?string $name): string
    {
        return $name === null || $name === '' ? '' : (string) preg_replace('/^Pôle ESS (Pays de )?/u', '', $name);
    }

    /** Pastille de pôle à la couleur du pôle (tokens --color-pole-*). */
    public static function poleBadge(?string $name, string $fallback = '—'): string
    {
        if ($name === null || $name === '') {
            return '<span class="muted nowrap">' . e($fallback) . '</span>';
        }
        $short = self::poleShort($name);
        $slug = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $short));
        $mod = in_array($slug, ['brest', 'morlaix', 'cornouaille'], true) ? ' badge--' . $slug : '';
        return '<span class="badge badge--pole' . $mod . '" title="' . e($name) . '">' . e($short) . '</span>';
    }

    /** Date SQLite (UTC) affichée en heure locale. */
    public static function date(?string $value, bool $time = false): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        return date($time ? 'd/m/Y à H:i' : 'd/m/Y', (int) strtotime($value . ' UTC'));
    }
}
