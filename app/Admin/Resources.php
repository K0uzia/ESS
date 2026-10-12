<?php
declare(strict_types=1);

namespace App\Admin;

/**
 * Définition des modules de gestion simples : une ligne de table = une fiche.
 * Types de champ : text, email, url, tel, textarea, markdown, date, number, checkbox, select, pole, pdf, image.
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
                'intro' => 'Une fiche par personne. La page Équipe et les onglets des pôles sont construits à partir de ces fiches.',
                'order' => 'COALESCE(pole_id, 99), CASE team WHEN \'salaries\' THEN 0 WHEN \'ca\' THEN 1 ELSE 2 END, position, name',
                'columns' => ['name' => 'Nom', 'role' => 'Rôle', 'pole_id' => 'Pôle', 'team' => 'Groupe', 'is_published' => 'En ligne'],
                'fields' => [
                    'name' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
                    'role' => ['label' => 'Rôle', 'type' => 'text', 'required' => true],
                    'pole_id' => ['label' => 'Pôle', 'type' => 'pole', 'empty' => 'Aucun (TAg29)'],
                    'team' => ['label' => 'Groupe', 'type' => 'select', 'options' => ['salaries' => 'Équipe salariée', 'ca' => 'Conseil d\'administration', 'tag29' => 'Équipe TAg29']],
                    'structure' => ['label' => 'Structure représentée', 'type' => 'text', 'hint' => 'Pour les membres du conseil d\'administration.'],
                    'email' => ['label' => 'E-mail public', 'type' => 'email', 'hint' => 'Seulement s\'il peut être publié.'],
                    'phone' => ['label' => 'Téléphone public', 'type' => 'tel'],
                    'position' => ['label' => 'Ordre d\'affichage', 'type' => 'number', 'default' => 0],
                    'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'adherents' => [
                'table' => 'members',
                'label' => 'Adhérents',
                'singular' => 'adhérent',
                'intro' => 'Liste des adhérents affichée sur la page Les pôles. Elle alimente aussi la carte GoGoCarto par l\'export /les-poles/adherents.json.',
                'order' => 'pole_id, name COLLATE NOCASE',
                'columns' => ['name' => 'Nom', 'pole_id' => 'Pôle', 'status' => 'Statut', 'is_published' => 'En ligne'],
                'fields' => [
                    'name' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
                    'pole_id' => ['label' => 'Pôle', 'type' => 'pole', 'required' => true],
                    'status' => ['label' => 'Statut (association, coopérative…)', 'type' => 'text'],
                    'url' => ['label' => 'Site web', 'type' => 'url'],
                    'address' => ['label' => 'Adresse (pour la carte)', 'type' => 'text'],
                    'latitude' => ['label' => 'Latitude', 'type' => 'number', 'step' => 'any'],
                    'longitude' => ['label' => 'Longitude', 'type' => 'number', 'step' => 'any'],
                    'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'documents' => [
                'table' => 'documents',
                'label' => 'Documents',
                'singular' => 'document',
                'intro' => 'Documents PDF : documents des pôles, fiches pratiques, ressources et certificat Qualiopi (catégorie « Certificat »).',
                'order' => 'category, year DESC, position, title',
                'columns' => ['title' => 'Titre', 'category' => 'Catégorie', 'pole_id' => 'Pôle', 'year' => 'Année', 'is_published' => 'En ligne'],
                'fields' => [
                    'title' => ['label' => 'Titre', 'type' => 'text', 'required' => true],
                    'category' => ['label' => 'Catégorie', 'type' => 'select', 'options' => ['document' => 'Document de pôle', 'fiche_pratique' => 'Fiche pratique', 'ressource' => 'Ressource', 'certificat' => 'Certificat Qualiopi']],
                    'pole_id' => ['label' => 'Pôle', 'type' => 'pole', 'empty' => 'Tous les pôles'],
                    'year' => ['label' => 'Année', 'type' => 'number'],
                    'description' => ['label' => 'Description', 'type' => 'textarea'],
                    'file_path' => ['label' => 'Fichier PDF', 'type' => 'pdf', 'required' => true],
                    'position' => ['label' => 'Ordre d\'affichage', 'type' => 'number', 'default' => 0],
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
                'intro' => 'Partenaires et réseaux affichés en logos sur l\'accueil. Utilisez le logo officiel fourni par le partenaire.',
                'admin_only' => true,
                'order' => 'kind, position, name',
                'columns' => ['name' => 'Nom', 'logo_path' => 'Logo', 'kind' => 'Type', 'is_published' => 'En ligne'],
                'fields' => [
                    'name' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
                    'kind' => ['label' => 'Type', 'type' => 'select', 'options' => ['partenaire' => 'Partenaire', 'reseau' => 'Réseau']],
                    'pole_id' => ['label' => 'Pôle concerné', 'type' => 'pole', 'empty' => 'Tous'],
                    'url' => ['label' => 'Site web', 'type' => 'url'],
                    'logo_path' => ['label' => 'Logo (PNG, JPG ou WebP)', 'type' => 'image', 'height' => 160],
                    'position' => ['label' => 'Ordre d\'affichage', 'type' => 'number', 'default' => 0],
                    'is_published' => ['label' => 'Afficher sur le site', 'type' => 'checkbox', 'default' => 1],
                ],
            ],
            'poles' => [
                'table' => 'poles',
                'label' => 'Coordonnées des pôles',
                'singular' => 'pôle',
                'intro' => 'Source unique des coordonnées : pied de page, cartes des pôles, formulaires, mentions légales et données structurées.',
                'admin_only' => true,
                'fixed' => true,
                'order' => 'position',
                'columns' => ['name' => 'Pôle', 'city' => 'Ville', 'email' => 'E-mail', 'is_head_office' => 'Siège'],
                'fields' => [
                    'name' => ['label' => 'Nom', 'type' => 'text', 'required' => true],
                    'address_lines' => ['label' => 'Adresse (une ligne par ligne d\'adresse)', 'type' => 'textarea', 'required' => true],
                    'postal_code' => ['label' => 'Code postal', 'type' => 'text', 'required' => true],
                    'city' => ['label' => 'Ville', 'type' => 'text', 'required' => true],
                    'phones' => ['label' => 'Téléphones (un par ligne)', 'type' => 'textarea', 'required' => true],
                    'email' => ['label' => 'E-mail (aussi expéditeur de la newsletter du pôle)', 'type' => 'email', 'required' => true],
                    'is_head_office' => ['label' => 'Siège social du groupement', 'type' => 'checkbox'],
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
