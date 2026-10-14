<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\FormGuard;
use App\Core\Session;
use App\Core\View;

final class AuthController
{
    public static function form(): void
    {
        if (Auth::user() !== null) {
            redirect('/admin/');
        }
        View::render('admin/login', ['title' => 'Connexion', 'error' => null], 'admin/layout');
    }

    public static function login(): void
    {
        Csrf::check();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (Auth::attempt($username, $password)) {
            redirect('/admin/');
        }
        http_response_code(422);
        View::render('admin/login', [
            'title' => 'Connexion',
            'error' => 'E-mail ou mot de passe incorrect, ou trop de tentatives. Réessayez dans quelques minutes.',
            'username' => $username,
        ], 'admin/layout');
    }

    public static function forgotForm(): void
    {
        View::render('admin/forgot', ['title' => 'Mot de passe oublié', 'sent' => false, 'error' => null, 'login' => ''], 'admin/layout');
    }

    /** Même réponse que le compte existe ou non, qu'il ait une adresse ou non (ne révèle aucun compte). */
    public static function forgot(): void
    {
        Csrf::check();
        $login = trim((string) ($_POST['login'] ?? ''));
        if ($login === '') {
            http_response_code(422);
            View::render('admin/forgot', ['title' => 'Mot de passe oublié', 'sent' => false, 'error' => 'Indiquez votre adresse e-mail.', 'login' => ''], 'admin/layout');
            return;
        }
        if (!FormGuard::rateLimited('mot-de-passe', 5, 15)) {
            $account = Db::run(
                'SELECT id, username, email, role, pole_id, last_login_at FROM users WHERE (username = ? OR email = ?) AND email IS NOT NULL',
                [$login, $login]
            )->fetch();
            if ($account) {
                AccountController::sendLink($account, 'reinitialisation');
                Auth::log('compte.mot_de_passe_oublie', $account['username']);
            }
        }
        View::render('admin/forgot', ['title' => 'Mot de passe oublié', 'sent' => true, 'error' => null, 'login' => ''], 'admin/layout');
    }

    /** Page ouverte depuis le lien reçu par e-mail : invitation ou changement de mot de passe. */
    public static function resetForm(): void
    {
        $token = (string) ($_GET['jeton'] ?? '');
        self::renderReset($token, Auth::findLink($token), []);
    }

    public static function reset(): void
    {
        Csrf::check();
        $token = (string) ($_POST['jeton'] ?? '');
        $link = Auth::findLink($token);
        if ($link === null) {
            self::renderReset($token, null, []);
            return;
        }
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];
        if (text_length($password) < Auth::MIN_PASSWORD_LENGTH) {
            $errors['password'] = 'Au moins ' . Auth::MIN_PASSWORD_LENGTH . ' caractères.';
        } elseif ($password !== ($_POST['password_confirm'] ?? '')) {
            $errors['password_confirm'] = 'Les deux mots de passe ne correspondent pas.';
        }
        if ($errors !== []) {
            http_response_code(422);
            self::renderReset($token, $link, $errors);
            return;
        }
        Auth::completeLink($link, $password);
        Auth::log($link['purpose'] === 'invitation' ? 'compte.activation' : 'compte.mot_de_passe', $link['username']);
        Session::flash($link['purpose'] === 'invitation' ? 'Compte activé : connectez-vous avec votre nouveau mot de passe.' : 'Mot de passe changé : connectez-vous avec le nouveau.');
        redirect('/admin/connexion');
    }

    /**
     * @param array<string, mixed>|null $link
     * @param array<string, string> $errors
     */
    private static function renderReset(string $token, ?array $link, array $errors): void
    {
        if ($link === null) {
            http_response_code(410);
        }
        View::render('admin/reset', [
            'title' => $link !== null && $link['purpose'] === 'invitation' ? 'Choisir votre mot de passe' : 'Nouveau mot de passe',
            'token' => $token,
            'link' => $link,
            'errors' => $errors,
        ], 'admin/layout');
    }

    public static function logout(): void
    {
        Csrf::check();
        Auth::logout();
        Session::start();
        Session::flash('Vous êtes déconnecté.');
        redirect('/admin/connexion');
    }
}
