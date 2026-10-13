<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Db;
use App\Core\View;

final class DashboardController
{
    private const TODO = '__À_REMPLIR__';

    public static function index(): void
    {
        $user = Auth::require();
        $isAdmin = $user['role'] === 'admin';
        $pole = $isAdmin ? null : (int) $user['pole_id'];
        View::render('admin/dashboard', [
            'title' => 'Tableau de bord',
            'user' => $user,
            'alerts' => self::alerts($isAdmin, $pole),
        ], 'admin/layout');
    }

    /**
     * Points réels à traiter, du plus bloquant au plus léger.
     * @return list<array{level: string, text: string, href: string, action: string}>
     */
    private static function alerts(bool $isAdmin, ?int $pole): array
    {
        $alerts = [];
        if ($isAdmin && Auth::defaultAccountActive()) {
            $id = (int) Db::run("SELECT id FROM users WHERE username = 'lacapsule'")->fetchColumn();
            $alerts[] = ['level' => 'bloquant', 'text' => 'Le compte temporaire « lacapsule » a encore son mot de passe d\'origine.', 'href' => "/admin/comptes/$id#mot-de-passe", 'action' => 'Changer le mot de passe'];
        }
        if ($isAdmin) {
            $labels = [];
            foreach (Db::run('SELECT key FROM settings WHERE instr(value, ?) > 0', [self::TODO])->fetchAll(\PDO::FETCH_COLUMN) as $key) {
                $labels[] = SettingsController::FIELDS[$key]['label'] ?? $key;
            }
            if ($labels !== []) {
                $alerts[] = ['level' => 'bloquant', 'text' => 'Identité du site à compléter : ' . implode(', ', $labels) . '.', 'href' => '/admin/identite', 'action' => 'Compléter'];
            }
            $poles = Db::run('SELECT id, name FROM poles WHERE instr(address_lines || phones || email, ?) > 0', [self::TODO])->fetchAll();
            foreach ($poles as $p) {
                $alerts[] = ['level' => 'bloquant', 'text' => 'Coordonnées à compléter : ' . $p['name'] . '.', 'href' => '/admin/poles/' . (int) $p['id'], 'action' => 'Compléter'];
            }
        }
        $allowed = ContentController::allowedPages();
        $rows = Db::run("SELECT page_key, section_key FROM page_contents WHERE instr(value, ?) > 0 GROUP BY page_key, section_key ORDER BY page_key", [self::TODO])->fetchAll();
        foreach ($rows as $r) {
            if (!isset($allowed[$r['page_key']])) {
                continue;
            }
            $anchor = $r['section_key'] === '_meta' ? '/en-tete' : '/sections/' . $r['section_key'];
            $alerts[] = ['level' => 'bloquant', 'text' => 'Texte à compléter dans la page « ' . $allowed[$r['page_key']] . ' ».', 'href' => '/admin/pages/' . $r['page_key'] . $anchor, 'action' => 'Ouvrir la section'];
        }
        $failed = (int) Db::run('SELECT COUNT(*) FROM contact_messages WHERE mail_sent = 0' . ($pole === null ? '' : ' AND pole_id = ' . $pole))->fetchColumn();
        if ($failed > 0) {
            $alerts[] = ['level' => 'important', 'text' => $failed . ' message' . ($failed > 1 ? 's' : '') . ' de contact n\'' . ($failed > 1 ? 'ont' : 'a') . ' pas pu être transmis par e-mail : répondez depuis le tableau de bord.', 'href' => '/admin/messages', 'action' => 'Voir les messages'];
        }
        if ($isAdmin && !(int) Db::run("SELECT COUNT(*) FROM documents WHERE category = 'certificat' AND is_published = 1")->fetchColumn()) {
            $alerts[] = ['level' => 'important', 'text' => 'Aucun certificat Qualiopi publié (catégorie « Certificat » des documents).', 'href' => '/admin/documents', 'action' => 'Voir les documents'];
        }
        $noGeo = (int) Db::run('SELECT COUNT(*) FROM members WHERE is_published = 1 AND (latitude IS NULL OR longitude IS NULL)' . ($pole === null ? '' : ' AND pole_id = ' . $pole))->fetchColumn();
        if ($noGeo > 0) {
            $alerts[] = ['level' => 'info', 'text' => $noGeo . ' adhérent' . ($noGeo > 1 ? 's' : '') . ' en ligne sans latitude ni longitude : absent' . ($noGeo > 1 ? 's' : '') . ' de la carte.', 'href' => '/admin/adherents', 'action' => 'Voir les adhérents'];
        }
        $pending = (int) Db::run("SELECT COUNT(*) FROM newsletter_deliveries WHERE status = 'pending'")->fetchColumn();
        if ($pending > 0) {
            $alerts[] = ['level' => 'info', 'text' => $pending . ' e-mail' . ($pending > 1 ? 's' : '') . ' de newsletter en attente d\'envoi.', 'href' => '/admin/newsletters', 'action' => 'Suivre l\'envoi'];
        }
        return $alerts;
    }
}
