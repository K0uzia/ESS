<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Mailer;
use App\Core\Session;
use App\Core\View;

/**
 * Toutes les adresses e-mail du site, réservé aux administrateurs (décision du 15 octobre 2026) :
 * boîte d'envoi, destinataire du formulaire de contact, expéditeurs et réponses des newsletters.
 * Liste en lignes, chaque ligne se modifie dans une fenêtre ; serveur et port restent ceux par défaut (Hostinger).
 */
final class EmailController
{
    public static function index(): void
    {
        Auth::require('admin');
        View::render('admin/emails/index', ['title' => 'E-mails', 'poles' => self::poles(), 'mail' => Mailer::config()], 'admin/layout');
    }

    public static function mailboxForm(): void
    {
        Auth::require('admin');
        $mail = Mailer::config();
        self::form('boite', ['smtp_user' => $mail['source'] === 'admin' ? $mail['user'] : ''], []);
    }

    /** Boîte mail qui envoie tous les e-mails du site. */
    public static function mailbox(): void
    {
        Auth::require('admin');
        Csrf::check();
        $values = ['smtp_user' => trim((string) ($_POST['smtp_user'] ?? ''))];
        $password = (string) ($_POST['smtp_password'] ?? '');
        $errors = [];
        if (!filter_var($values['smtp_user'], FILTER_VALIDATE_EMAIL)) {
            $errors['smtp_user'] = 'Adresse e-mail invalide.';
        }
        if ($password === '' && setting('smtp_password') === '') {
            $errors['smtp_password'] = 'Indiquez le mot de passe de la boîte mail.';
        }
        if ($errors !== []) {
            self::form('boite', $values, $errors);
            return;
        }
        $save = $values + ($password !== '' ? ['smtp_password' => Mailer::seal($password)] : []);
        foreach ($save as $key => $value) {
            Db::run('INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = CURRENT_TIMESTAMP', [$key, $value]);
        }
        Auth::log('emails.boite', $values['smtp_user']);
        Session::flash('Boîte d\'envoi enregistrée. Envoyez-vous un e-mail de test pour vérifier.');
        redirect('/admin/emails');
    }

    public static function test(): void
    {
        $me = Auth::require('admin');
        Csrf::check();
        if (empty($me['email'])) {
            Session::flash('Votre compte n\'a pas d\'adresse e-mail : ajoutez-en une dans Comptes pour recevoir le test.', 'error');
        } elseif (Mailer::send((string) $me['email'], 'Test d\'envoi du tableau de bord ess29.org', "Bonjour,\n\nCet e-mail confirme que le site ess29.org peut envoyer des messages.\n\n" . setting('raison_sociale') . "\n")) {
            Session::flash('E-mail de test envoyé à ' . $me['email'] . '. Vérifiez votre boîte de réception (et les indésirables).');
        } else {
            $error = (string) Mailer::$lastError;
            Session::flash(match (true) {
                str_contains($error, '535') => 'L\'adresse ou le mot de passe de la boîte d\'envoi a été refusé. Vérifiez-les puis réessayez.',
                str_starts_with($error, 'connexion impossible') => 'Le service d\'envoi ne répond pas. Réessayez plus tard ; si cela dure, prévenez La Capsule.',
                default => 'L\'e-mail n\'a pas pu partir. Vérifiez la boîte d\'envoi ; si cela échoue encore, prévenez La Capsule.',
            }, 'error');
        }
        redirect('/admin/emails');
    }

    public static function contactForm(): void
    {
        Auth::require('admin');
        self::form('contact', ['email_contact' => setting('email_contact')], []);
    }

    /** Adresse qui reçoit le formulaire de contact, affichée aussi sur le site. */
    public static function contact(): void
    {
        Auth::require('admin');
        Csrf::check();
        $values = ['email_contact' => trim((string) ($_POST['email_contact'] ?? ''))];
        if (!filter_var($values['email_contact'], FILTER_VALIDATE_EMAIL)) {
            self::form('contact', $values, ['email_contact' => 'Adresse e-mail invalide.']);
            return;
        }
        Db::run('UPDATE settings SET value = ?, updated_at = CURRENT_TIMESTAMP WHERE key = ?', [$values['email_contact'], 'email_contact']);
        Auth::log('emails.contact', $values['email_contact']);
        Session::flash('Adresse du formulaire de contact enregistrée.');
        redirect('/admin/emails');
    }

    public static function poleForm(string $id): void
    {
        Auth::require('admin');
        $pole = self::findPole($id);
        self::form('pole', ['newsletter_email' => (string) $pole['newsletter_email'], 'email' => (string) $pole['email']], [], $pole);
    }

    /** Expéditeur des newsletters d'un pôle et adresse du pôle (réponses, affichée sur le site). */
    public static function poleUpdate(string $id): void
    {
        Auth::require('admin');
        Csrf::check();
        $pole = self::findPole($id);
        $values = [
            'newsletter_email' => trim((string) ($_POST['newsletter_email'] ?? '')),
            'email' => trim((string) ($_POST['email'] ?? '')),
        ];
        $errors = array_map(static fn (): string => 'Adresse e-mail invalide.', array_filter($values, static fn (string $v): bool => !filter_var($v, FILTER_VALIDATE_EMAIL)));
        if ($errors !== []) {
            self::form('pole', $values, $errors, $pole);
            return;
        }
        Db::run('UPDATE poles SET newsletter_email = ?, email = ? WHERE id = ?', [$values['newsletter_email'], $values['email'], $pole['id']]);
        Auth::log('emails.pole', (string) $pole['name']);
        Session::flash('Adresses du ' . $pole['name'] . ' enregistrées.');
        redirect('/admin/emails');
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     * @param array<string, mixed>|null $pole
     */
    private static function form(string $kind, array $values, array $errors, ?array $pole = null): void
    {
        if ($errors !== []) {
            http_response_code(422);
        }
        $titles = ['boite' => 'Boîte d\'envoi', 'contact' => 'Formulaire de contact', 'pole' => (string) ($pole['name'] ?? '')];
        View::render('admin/emails/form', [
            'title' => $titles[$kind],
            'kind' => $kind,
            'values' => $values,
            'errors' => $errors,
            'pole' => $pole,
        ], 'admin/layout');
    }

    /** @return list<array<string, mixed>> */
    private static function poles(): array
    {
        return Db::run('SELECT id, name, slug, email, newsletter_email FROM poles ORDER BY position')->fetchAll();
    }

    /** @return array<string, mixed> */
    private static function findPole(string $id): array
    {
        $pole = Db::run('SELECT id, name, slug, email, newsletter_email FROM poles WHERE id = ?', [(int) $id])->fetch();
        if (!$pole) {
            http_response_code(404);
            exit('Pôle introuvable.');
        }
        return $pole;
    }
}
