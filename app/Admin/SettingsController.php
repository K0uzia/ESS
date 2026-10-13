<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Session;
use App\Core\View;

/** Identité du groupement : source unique pour le pied de page, les mentions légales et le JSON-LD. */
final class SettingsController
{
    // Promesse, baseline, nom court et adresse du site sont fixés par la charte et la mise en ligne : pas modifiables ici.
    public const FIELDS = [
        'raison_sociale' => ['label' => 'Raison sociale', 'type' => 'text', 'required' => true],
        'siret' => ['label' => 'SIRET', 'type' => 'text', 'required' => true],
        'email_contact' => ['label' => 'E-mail de contact générique', 'type' => 'email', 'required' => true],
        'directeur_publication' => ['label' => 'Directeur ou directrice de la publication', 'type' => 'text', 'required' => true],
        'hebergeur' => ['label' => 'Hébergeur (nom, adresse, téléphone ; une ligne chacun)', 'type' => 'textarea', 'required' => true],
        'gogocarto_url' => ['label' => 'Adresse de la carte GoGoCarto des adhérents', 'type' => 'url', 'hint' => 'Exemple : https://votre-carte.gogocarto.fr. Laisser vide pour masquer la carte.'],
    ];

    public static function edit(): void
    {
        Auth::require('admin');
        self::render(Db::run('SELECT key, value FROM settings')->fetchAll(\PDO::FETCH_KEY_PAIR), []);
    }

    public static function update(): void
    {
        Auth::require('admin');
        Csrf::check();
        $values = [];
        $errors = [];
        foreach (self::FIELDS as $key => $f) {
            $v = trim(str_replace("\r\n", "\n", (string) ($_POST[$key] ?? '')));
            $values[$key] = $v;
            if ($v === '' && !empty($f['required'])) {
                $errors[$key] = 'Champ obligatoire.';
            } elseif ($v !== '' && $f['type'] === 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $errors[$key] = 'Adresse e-mail invalide.';
            } elseif ($v !== '' && $f['type'] === 'url' && !preg_match('#^https://[^\s/]+(/[^\s]*)?$#', $v)) {
                $errors[$key] = 'Adresse invalide : elle doit commencer par https://.';
            }
        }
        if ($values['gogocarto_url'] !== '' && !preg_match('#^https://[a-z0-9-]+\.gogocarto\.fr(/[^\s]*)?$#', $values['gogocarto_url'])) {
            $errors['gogocarto_url'] = 'Seule une carte hébergée sur gogocarto.fr est acceptée.';
        }
        if ($errors !== []) {
            http_response_code(422);
            self::render($values, $errors);
            return;
        }
        foreach ($values as $key => $v) {
            Db::run('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP', [$key, $v]);
        }
        Auth::log('identite.modification');
        Session::flash('Identité enregistrée.');
        redirect('/admin/identite');
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private static function render(array $values, array $errors): void
    {
        View::render('admin/settings/edit', ['title' => 'Identité du site', 'values' => $values, 'errors' => $errors], 'admin/layout');
    }
}
