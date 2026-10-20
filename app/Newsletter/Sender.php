<?php
declare(strict_types=1);

namespace App\Newsletter;

use App\Core\Db;
use App\Core\Mailer;
use App\Site\Orgs;

/**
 * Envoi par lots : une campagne mise en file crée une ligne de livraison par abonné actif,
 * puis chaque lot (cron bin/newsletter-send, ou bouton de l'admin) en envoie une partie
 * sans dépasser le quota quotidien du SMTP Hostinger.
 */
final class Sender
{
    public static function batchSize(): int
    {
        return max(1, (int) ($_ENV['NEWSLETTER_BATCH'] ?? 40));
    }

    public static function dailyLimit(): int
    {
        return max(1, (int) ($_ENV['NEWSLETTER_DAILY_LIMIT'] ?? 300));
    }

    public static function sentLast24h(): int
    {
        return (int) Db::run("SELECT COUNT(*) FROM newsletter_deliveries WHERE status = 'sent' AND sent_at >= datetime('now', '-1 day')")->fetchColumn();
    }

    /** Met la campagne en file : une livraison par abonné actif de sa liste. Retourne le nombre de destinataires. */
    public static function queue(int $campaignId): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        Db::run(
            "INSERT OR IGNORE INTO newsletter_deliveries (campaign_id, subscriber_id)
             SELECT c.id, s.id FROM newsletter_campaigns c JOIN newsletter_subscribers s ON s.list_id = c.list_id AND s.status = 'active'
             WHERE c.id = ? AND c.status = 'draft'",
            [$campaignId]
        );
        $count = (int) Db::run('SELECT COUNT(*) FROM newsletter_deliveries WHERE campaign_id = ?', [$campaignId])->fetchColumn();
        Db::run("UPDATE newsletter_campaigns SET status = 'queued', updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'draft'", [$campaignId]);
        $pdo->commit();
        return $count;
    }

    /** Envoie un lot. Retourne [envoyés, échecs, restant en file]. @return array{0: int, 1: int, 2: int} */
    public static function sendBatch(?int $limit = null): array
    {
        $room = self::dailyLimit() - self::sentLast24h();
        $limit = min($limit ?? self::batchSize(), max(0, $room));
        $sent = 0;
        $failed = 0;
        if ($limit > 0) {
            $rows = Db::run(
                "SELECT d.id, d.campaign_id, s.email, s.token, s.status AS sub_status
                 FROM newsletter_deliveries d
                 JOIN newsletter_subscribers s ON s.id = d.subscriber_id
                 JOIN newsletter_campaigns c ON c.id = d.campaign_id
                 WHERE d.status = 'pending' AND c.status IN ('queued', 'sending')
                 ORDER BY d.campaign_id, d.id LIMIT ?",
                [$limit]
            )->fetchAll();
            $site = rtrim(setting('site_url'), '/');
            $cache = [];
            foreach ($rows as $r) {
                // Désinscrit entre la mise en file et l'envoi : on n'envoie pas.
                if ($r['sub_status'] !== 'active') {
                    Db::run("UPDATE newsletter_deliveries SET status = 'failed', error = 'désinscrit avant envoi' WHERE id = ?", [$r['id']]);
                    continue;
                }
                $cache[$r['campaign_id']] ??= self::context((int) $r['campaign_id']);
                [$campaign, $template, $list] = $cache[$r['campaign_id']];
                Db::run("UPDATE newsletter_campaigns SET status = 'sending' WHERE id = ? AND status = 'queued'", [$campaign['id']]);
                $unsubscribe = $site . '/newsletter/desinscription?jeton=' . $r['token'];
                $mail = Renderer::render($campaign, $template, $list, $unsubscribe);
                $ok = Mailer::send($r['email'], (string) $campaign['subject'], $mail['text'], $mail['html'], $list['reply'] ?: null, $list['sender_email'], [
                    'List-Unsubscribe' => "<$unsubscribe>, <mailto:" . $list['sender_email'] . '?subject=desinscription>',
                    'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                ], (string) $list['org_name']);
                // Refus passager (limite d'envoi de Hostinger) : la lettre reste à envoyer, le lot s'arrête là.
                if (!$ok && Mailer::temporary()) {
                    $failed++;
                    break;
                }
                Db::run(
                    'UPDATE newsletter_deliveries SET status = ?, error = ?, sent_at = CURRENT_TIMESTAMP WHERE id = ?',
                    [$ok ? 'sent' : 'failed', $ok ? null : (string) Mailer::$lastError, $r['id']]
                );
                $ok ? $sent++ : $failed++;
            }
        }
        // Campagnes terminées.
        Db::run(
            "UPDATE newsletter_campaigns SET status = 'sent', sent_at = COALESCE(sent_at, CURRENT_TIMESTAMP)
             WHERE status IN ('queued', 'sending') AND NOT EXISTS (SELECT 1 FROM newsletter_deliveries d WHERE d.campaign_id = newsletter_campaigns.id AND d.status = 'pending')"
        );
        $left = (int) Db::run("SELECT COUNT(*) FROM newsletter_deliveries WHERE status = 'pending'")->fetchColumn();
        return [$sent, $failed, $left];
    }

    /**
     * Lettre d'une liste Sympa : un seul e-mail, à l'adresse de la liste, qui le distribue à ses abonnés.
     * L'expéditeur doit être autorisé à écrire sur la liste (sinon Sympa la met en modération).
     */
    public static function sendToSympa(int $campaignId): bool
    {
        [$campaign, $template, $list] = self::context($campaignId);
        $mail = Renderer::render($campaign, $template, $list, null);
        $ok = Mailer::send((string) $list['sympa_address'], (string) $campaign['subject'], $mail['text'], $mail['html'], $list['reply'] ?: null, $list['sender_email'], [], (string) $list['org_name']);
        if ($ok) {
            Db::run("UPDATE newsletter_campaigns SET status = 'sent', sent_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'draft'", [$campaignId]);
        }
        return $ok;
    }

    /** Envoi de test à une adresse, sans lien de désinscription réel. */
    public static function sendTest(int $campaignId, string $to): bool
    {
        [$campaign, $template, $list] = self::context($campaignId);
        $mail = Renderer::render($campaign, $template, $list, null);
        return Mailer::send($to, '[Test] ' . $campaign['subject'], $mail['text'], $mail['html'], $list['reply'] ?: null, $list['sender_email'], [], (string) $list['org_name']);
    }

    /**
     * Lettre, modèle et liste ; la liste porte l'organisme (« org_data ») et son adresse de réponse (« reply »).
     * @return array{0: array<string, mixed>, 1: array<string, mixed>|null, 2: array<string, mixed>}
     */
    public static function context(int $campaignId): array
    {
        $campaign = Db::run('SELECT * FROM newsletter_campaigns WHERE id = ?', [$campaignId])->fetch();
        $template = $campaign['template_id'] ? (Db::run('SELECT * FROM newsletter_templates WHERE id = ?', [$campaign['template_id']])->fetch() ?: null) : null;
        $list = (array) Orgs::findList((int) $campaign['list_id']);
        $list['org_data'] = Orgs::get((string) $list['org']);
        $list['reply'] = Orgs::address((string) $list['org'], 'reponse');
        return [$campaign, $template, $list];
    }
}
