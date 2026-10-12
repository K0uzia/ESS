<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
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
            'error' => 'Identifiant ou mot de passe incorrect, ou trop de tentatives. Réessayez dans quelques minutes.',
            'username' => $username,
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
