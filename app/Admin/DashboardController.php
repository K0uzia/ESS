<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;

/** Accueil du tableau de bord : raccourcis, chiffres et derniers contenus, comme Ulamir. Pas d'alerte technique. */
final class DashboardController
{
    public static function index(): void
    {
        $user = Auth::require();
        if (Auth::scope($user) === 'tag29') {
            View::render('admin/dashboard', [
                'title' => 'Tableau de bord',
                'user' => $user,
                'tag29' => true,
                'tempAccount' => false,
                'stats' => [['/admin/equipe', 'Personnes dans l\'équipe TAg29', (int) Db::run("SELECT COUNT(*) FROM team_members WHERE team = 'tag29' AND is_published = 1")->fetchColumn()]],
                'offers' => [],
                'letters' => [],
            ], 'admin/layout');
            return;
        }
        $isAdmin = $user['role'] === 'admin';
        // Un éditeur ne voit que les chiffres de son pôle.
        [$and, $params] = $isAdmin ? ['', []] : [' AND pole_id = ?', [(int) $user['pole_id']]];
        $openOffers = "is_published = 1 AND (closes_on IS NULL OR closes_on >= date('now', 'localtime'))";
        View::render('admin/dashboard', [
            'title' => 'Tableau de bord',
            'user' => $user,
            'tempAccount' => $isAdmin && Auth::defaultAccountActive(),
            'stats' => [
                ['/admin/adherents', 'Adhérents', (int) Db::run("SELECT COUNT(*) FROM members WHERE is_published = 1$and", $params)->fetchColumn()],
                ['/admin/offres', 'Offres en ligne', (int) Db::run("SELECT COUNT(*) FROM job_offers WHERE $openOffers$and", $params)->fetchColumn()],
                ['/admin/newsletters/abonnes', 'Abonnés aux newsletters', (int) Db::run("SELECT COUNT(DISTINCT email) FROM newsletter_subscribers WHERE status = 'active'$and", $params)->fetchColumn()],
                ['/admin/equipe', 'Personnes dans l\'équipe', (int) Db::run("SELECT COUNT(*) FROM team_members WHERE is_published = 1$and", $params)->fetchColumn()],
            ],
            'offers' => Db::run("SELECT id, title, closes_on FROM job_offers WHERE $openOffers$and ORDER BY closes_on IS NULL, closes_on LIMIT 5", $params)->fetchAll(),
            'letters' => Db::run(
                "SELECT c.id, c.subject, c.status, c.sent_at, c.created_at, p.name AS pole,
                        (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id) AS total,
                        (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id AND d.status = 'sent') AS sent
                 FROM newsletter_campaigns c JOIN poles p ON p.id = c.pole_id WHERE 1 = 1" . str_replace('pole_id', 'c.pole_id', $and) . '
                 ORDER BY c.created_at DESC LIMIT 5',
                $params
            )->fetchAll(),
        ], 'admin/layout');
    }
}
