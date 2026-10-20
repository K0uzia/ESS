<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;
use App\Site\Orgs;

/** Accueil du tableau de bord : raccourcis, chiffres et derniers contenus, comme Ulamir. Pas d'alerte technique. */
final class DashboardController
{
    public static function index(): void
    {
        $user = Auth::require();
        $scope = Auth::scope($user);
        $isAdmin = $scope === 'all';
        $org = Auth::org($user);
        $listIds = array_map(static fn (array $l): int => (int) $l['id'], Orgs::lists($org === null ? null : [$org]));
        $inLists = implode(',', $listIds) ?: '0';
        $openOffers = "is_published = 1 AND (closes_on IS NULL OR closes_on >= date('now', 'localtime'))";
        $subscribers = ['/admin/newsletters/abonnes', 'Abonnés aux newsletters', (int) Db::run("SELECT COUNT(DISTINCT email) FROM newsletter_subscribers WHERE status = 'active' AND list_id IN ($inLists)")->fetchColumn()];

        // Chiffres et offres du périmètre : tout (administrateur), un pôle, le groupement (ESS29) ou TAg29.
        [$and, $params] = $scope === 'pole' ? [' AND pole_id = ?', [(int) $user['pole_id']]] : ($scope === 'ess29' ? [' AND pole_id IS NULL', []] : ['', []]);
        $stats = match ($scope) {
            'tag29' => [['/admin/equipe', 'Personnes dans l\'équipe TAg29', (int) Db::run("SELECT COUNT(*) FROM team_members WHERE team = 'tag29' AND is_published = 1")->fetchColumn()]],
            'ess29' => [
                ['/admin/offres', 'Offres en ligne', (int) Db::run("SELECT COUNT(*) FROM job_offers WHERE $openOffers$and")->fetchColumn()],
                ['/admin/documents', 'Documents communs', (int) Db::run("SELECT COUNT(*) FROM documents WHERE is_published = 1$and")->fetchColumn()],
                ['/admin/equipe', 'Personnes dans l\'équipe ESS29', (int) Db::run("SELECT COUNT(*) FROM team_members WHERE team = 'groupement' AND is_published = 1")->fetchColumn()],
            ],
            default => [
                ['/admin/adherents', 'Adhérents', (int) Db::run("SELECT COUNT(*) FROM members WHERE is_published = 1$and", $params)->fetchColumn()],
                ['/admin/offres', 'Offres en ligne', (int) Db::run("SELECT COUNT(*) FROM job_offers WHERE $openOffers$and", $params)->fetchColumn()],
                ['/admin/equipe', 'Personnes dans l\'équipe', (int) Db::run("SELECT COUNT(*) FROM team_members WHERE is_published = 1$and", $params)->fetchColumn()],
            ],
        };
        if ($listIds !== []) {
            $stats[] = $subscribers;
        }

        $letters = Db::run(
            "SELECT c.id, c.subject, c.status, c.sent_at, c.created_at, c.list_id,
                    (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id) AS total,
                    (SELECT COUNT(*) FROM newsletter_deliveries d WHERE d.campaign_id = c.id AND d.status = 'sent') AS sent
             FROM newsletter_campaigns c WHERE c.list_id IN ($inLists) ORDER BY c.created_at DESC LIMIT 5"
        )->fetchAll();
        foreach ($letters as &$l) {
            $list = Orgs::findList((int) $l['list_id']);
            $l['org'] = $list['org_name'] ?? '';
            $l['audience'] = Orgs::AUDIENCES[$list['audience'] ?? ''] ?? '';
        }
        unset($l);

        View::render('admin/dashboard', [
            'title' => 'Tableau de bord',
            'user' => $user,
            'scope' => $scope,
            'hasNewsletters' => $listIds !== [],
            'tempAccount' => $isAdmin && Auth::defaultAccountActive(),
            'stats' => $stats,
            'offers' => $scope === 'tag29' ? [] : Db::run("SELECT id, title, closes_on FROM job_offers WHERE $openOffers$and ORDER BY closes_on IS NULL, closes_on LIMIT 5", $params)->fetchAll(),
            'letters' => $letters,
        ], 'admin/layout');
    }
}
