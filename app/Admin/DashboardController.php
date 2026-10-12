<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\View;

final class DashboardController
{
    public static function index(): void
    {
        $user = Auth::require();
        View::render('admin/dashboard', ['title' => 'Tableau de bord', 'user' => $user], 'admin/layout');
    }
}
