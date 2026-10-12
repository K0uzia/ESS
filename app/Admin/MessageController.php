<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Session;
use App\Core\View;

/** Messages du formulaire de contact (conservés 12 mois, voir /confidentialite/). */
final class MessageController
{
    public static function index(): void
    {
        $user = Auth::require();
        Db::run("DELETE FROM contact_messages WHERE created_at < datetime('now', '-12 months')");
        [$where, $params] = self::scope($user);
        $messages = Db::run(
            "SELECT m.*, p.name AS pole FROM contact_messages m LEFT JOIN poles p ON p.id = m.pole_id $where ORDER BY m.created_at DESC",
            $params
        )->fetchAll();
        View::render('admin/messages/index', ['title' => 'Messages reçus', 'messages' => $messages], 'admin/layout');
    }

    public static function show(string $id): void
    {
        $message = self::find(Auth::require(), (int) $id);
        View::render('admin/messages/show', ['title' => 'Message de ' . $message['name'], 'm' => $message], 'admin/layout');
    }

    public static function destroy(string $id): void
    {
        $message = self::find(Auth::require(), (int) $id);
        Csrf::check();
        Db::run('DELETE FROM contact_messages WHERE id = ?', [$message['id']]);
        Auth::log('message.suppression', (string) $message['id']);
        Session::flash('Message supprimé.');
        redirect('/admin/messages');
    }

    /**
     * Un éditeur voit les messages adressés à son pôle.
     * @param array<string, mixed> $user
     * @return array{0: string, 1: list<int>}
     */
    private static function scope(array $user): array
    {
        return $user['role'] === 'editor' ? ['WHERE m.pole_id = ?', [(int) $user['pole_id']]] : ['', []];
    }

    /**
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private static function find(array $user, int $id): array
    {
        [$where, $params] = self::scope($user);
        $where = $where === '' ? 'WHERE m.id = ?' : "$where AND m.id = ?";
        $m = Db::run("SELECT m.*, p.name AS pole FROM contact_messages m LEFT JOIN poles p ON p.id = m.pole_id $where", [...$params, $id])->fetch();
        if (!$m) {
            http_response_code(404);
            exit('Message introuvable.');
        }
        return $m;
    }
}
