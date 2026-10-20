<?php
declare(strict_types=1);

namespace App\Admin;

/**
 * Définition des modules de gestion simples : une ligne de table = une fiche.
 * Types de champ : text, email, url, tel, textarea, markdown, date, number, checkbox, select, pole, pdf, image, portrait.
 */
final class Resources
{
    /** @return array<string, array<string, mixed>> */
    public static function all(): array
    {
        return [
            'equipe' => [
                'table' => 'team_members',
                'label' => 'Équipe',
                'singular' => 'personne',
                'intro' => 'Les personnes affichées sur la page L\'équipe, rangées par pôle puis par groupe ; ESS29 et TAg29 à part.',
                'order' => 'COALESCE(pole_id, 99), CASE team WHEN \'groupement\' THEN 0 WHEN \'salaries\' THEN 1 WHEN \'ca\' THEN 2 ELSE 3 END, position, name',
                // Ordre réglé par flèches à l'intérieur d'un même pôle et d'un même groupe.
                'sortable' => ['pole_id', 'team'],
                // Le groupement et l'équipe TAg29 n'ont pas de pôle ; les autres groupes en ont toujours un.
                'no_pole' => ['team', ['groupement', 'tag29']],
                // Un éditeur ESS29 ou TAg29 ne voit et ne modifie que ces fiches (groupe et pôle imposés, champs masqués).
                'org_rows' => ['ess29' => ['team' => 'groupement', 'pole_id' => null], 'tag29' => ['team' => 'tag29', 'pole_id' => null]],
                'columns' => ['name' => 'Nom', 'role' => 'Rôle', 'pole_id' => 'Pôle', 'team' => 'Groupe', 'is_published' => 'En ligne'],
                // Liste rangée par onglet de pôle (ESS29 et TAg29 en onglets à part), puis par groupe : on ajoute directement au bon endroit.
                'tabs' => ['extra' => ['ess29' => ['label' => 'ESS29', 'groups' => ['groupement']], 'tag29' => ['label' => 'TAg29', 'groups' => ['tag29']]], 'group' => 'team', 'groups' => ['salaries', 'ca'], 'detail' => 'role'],
                'fields' => [
                    'name' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
                    'role' => ['label' => 'Rôle', 'type' => 'text', 'required' => true],
                    'team' => ['label' => 'Groupe', 'type' => 'select', 'options' => ['groupement' => 'Équipe ESS29', 'salaries' => 'Équipe salariée du pôle', 'ca' => 'Conseil d\'administration', 'tag29' => 'Équipe TAg29']],
                    'pole_id' => ['label' => 'Pôle', 'type' => 'pole', 'empty' => '—', 'hint' => 'Pas de pôle pour les équipes ESS29 et TAg29.'],
                    'structure' => ['label' => 'Structure représentée', 'type' => 'text', 'hint' => 'Pour les membres du conseil d\'administration.'],
                    'email' => ['label' => 'E-mail public', 'type' => 'email', 'hint' => 'Seulement s\'il peut être publié.'],
                    'phone' => ['label' => 'Téléphone public', 'type' => 'tel'],
                    'photo_path' => ['label' => 'Portrait', 'type' => 'portrait', 'hint' => 'Une vraie photo de la personne, avec son accord. Sans photo, ses initiales s\'affichent.'],
                    'is_vacant' => ['label' => 'Poste en recrutement', 'type' => 'checkbox', 'hint' => 'Carte « En recrutement » sans portrait : nom « En recrutement », rôle = intitulé du poste.'],
                    'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'adherents' => [
                'table' => 'members',
                'label' => 'Adhérents',
                'singular' => 'adhérent',
                'intro' => 'Les adhérents affichés dans la liste et sur la carte de la page Les pôles, rangés par pôle.',
                'order' => 'pole_id, name COLLATE NOCASE',
                'columns' => ['name' => 'Nom', 'pole_id' => 'Pôle', 'status' => 'Statut', 'is_published' => 'En ligne'],
                'tabs' => ['detail' => 'status'],
                // Position sur la carte calculée depuis l'adresse (Géoplateforme IGN, service public, appelé seulement depuis l'admin).
                'geocode' => 'address',
                'fields' => [
                    'name' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
                    'pole_id' => ['label' => 'Pôle', 'type' => 'pole', 'required' => true],
                    'status' => ['label' => 'Statut (association, coopérative…)', 'type' => 'text'],
                    'url' => ['label' => 'Site web', 'type' => 'url'],
                    'address' => ['label' => 'Adresse', 'type' => 'address', 'hint' => 'Commencez à taper (numéro, rue, ville) puis choisissez l\'adresse dans les suggestions : elle sera placée sur la carte.'],
                    'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'documents' => [
                'table' => 'documents',
                'label' => 'Documents',
                'singular' => 'document',
                'intro' => 'Documents PDF des pôles, fiches pratiques, ressources et certificat Qualiopi. Sur le site, les plus récents s\'affichent en premier.',
                'order' => 'category, year DESC, title',
                'list_group' => ['category', ['document' => 'Documents des pôles', 'fiche_pratique' => 'Fiches pratiques', 'ressource' => 'Ressources', 'certificat' => 'Certificat Qualiopi']],
                'columns' => ['title' => 'Titre', 'category' => 'Catégorie', 'pole_id' => 'Pôle', 'year' => 'Année', 'is_published' => 'En ligne'],
                // Un éditeur ESS29 gère les documents communs (sans pôle).
                'org_rows' => ['ess29' => ['pole_id' => null]],
                'fields' => [
                    'title' => ['label' => 'Titre', 'type' => 'text', 'required' => true],
                    'category' => ['label' => 'Catégorie', 'type' => 'select', 'options' => ['document' => 'Document de pôle', 'fiche_pratique' => 'Fiche pratique', 'ressource' => 'Ressource', 'certificat' => 'Certificat Qualiopi']],
                    'pole_id' => ['label' => 'Pôle', 'type' => 'pole', 'empty' => 'Tous les pôles'],
                    'year' => ['label' => 'Année', 'type' => 'number'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'file_path' => ['label' => 'Fichier PDF', 'type' => 'pdf', 'required' => true],
                    'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'offres' => [
                'table' => 'job_offers',
                'label' => 'Offres d\'emploi',
                'singular' => 'offre',
                'intro' => 'Une offre disparaît du site le lendemain de sa date limite.',
                'order' => 'created_at DESC',
                'columns' => ['title' => 'Intitulé', 'pole_id' => 'Pôle', 'closes_on' => 'Date limite', 'is_published' => 'En ligne'],
                // Un éditeur ESS29 gère les offres du groupement (sans pôle).
                'org_rows' => ['ess29' => ['pole_id' => null]],
                'fields' => [
                    'title' => ['label' => 'Intitulé du poste', 'type' => 'text', 'required' => true],
                    'pole_id' => ['label' => 'Pôle', 'type' => 'pole', 'empty' => 'Groupement'],
                    'contract' => ['label' => 'Contrat (CDI, CDD…)', 'type' => 'text'],
                    'location' => ['label' => 'Lieu', 'type' => 'text'],
                    'body' => ['label' => 'Description', 'type' => 'markdown', 'required' => true],
                    'apply_url' => ['label' => 'Lien pour candidater', 'type' => 'url'],
                    'apply_email' => ['label' => 'E-mail pour candidater', 'type' => 'email'],
                    'closes_on' => ['label' => 'Date limite de candidature', 'type' => 'date'],
                    'is_published' => ['label' => 'Publier', 'type' => 'checkbox', 'default' => 0],
                ],
            ],
            'partenaires' => [
                'table' => 'partners',
                'label' => 'Partenaires',
                'singular' => 'partenaire',
                'intro' => 'Partenaires et réseaux affichés en logos sur l\'accueil, communs à tous les pôles. Utilisez le logo officiel fourni par le partenaire.',
                // Commun à tous les comptes (pôles, ESS29 et TAg29) : pas de pôle, pas de restriction.
                'shared' => true,
                'order' => 'kind, position, name',
                'sortable' => ['kind'],
                'list_group' => ['kind', ['partenaire' => 'Partenaires', 'reseau' => 'Réseaux']],
                'columns' => ['name' => 'Nom', 'logo_path' => 'Logo', 'kind' => 'Type', 'is_published' => 'En ligne'],
                'fields' => [
                    'name' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
                    'kind' => ['label' => 'Type', 'type' => 'select', 'options' => ['partenaire' => 'Partenaire', 'reseau' => 'Réseau']],
                    'url' => ['label' => 'Site web', 'type' => 'url'],
                    'logo_path' => ['label' => 'Logo', 'type' => 'image', 'height' => 160],
                    'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
