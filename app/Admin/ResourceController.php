<?php
declare(strict_types=1);

namespace App\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Db;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;

/** Liste, création, modification et suppression des fiches définies dans Resources. */
final class ResourceController
{
    public static function index(string $key): void
    {
        [$res, $user] = self::authorize($key);
        $poleFilter = self::editorPole($res, $user) ?? ((int) ($_GET['pole'] ?? 0) ?: null);
        $where = $poleFilter !== null && isset($res['fields']['pole_id']) ? 'WHERE pole_id = ?' : '';
        $rows = Db::run("SELECT * FROM {$res['table']} $where ORDER BY {$res['order']}", $where ? [$poleFilter] : [])->fetchAll();
        View::render(isset($res['tabs']) ? 'admin/resource/tabs' : 'admin/resource/index', [
            'title' => $res['label'],
            'key' => $key,
            'res' => $res,
            'rows' => $rows,
            'poles' => self::poles(),
            'poleFilter' => $poleFilter,
            'canFilter' => isset($res['fields']['pole_id']) && self::editorPole($res, $user) === null,
        ], 'admin/layout');
    }

    public static function create(string $key): void
    {
        [$res] = self::authorize($key);
        if (!empty($res['fixed'])) {
            self::notFound();
        }
        $defaults = array_map(fn ($f) => $f['default'] ?? '', $res['fields']);
        // « Ajouter » depuis un groupe de la liste : pôle et groupe déjà choisis.
        foreach ($res['fields'] as $name => $f) {
            $wanted = $_GET[$name] ?? null;
            if (is_string($wanted) && $wanted !== '' && ($f['type'] === 'pole' || ($f['type'] === 'select' && isset($f['options'][$wanted])))) {
                $defaults[$name] = $f['type'] === 'pole' ? (int) $wanted : $wanted;
            }
        }
        self::form($key, $res, null, $defaults, []);
    }

    public static function store(string $key): void
    {
        [$res, $user] = self::authorize($key);
        Csrf::check();
        if (!empty($res['fixed'])) {
            self::notFound();
        }
        [$values, $errors] = self::input($res, $user, null);
        if ($errors !== []) {
            self::form($key, $res, null, $values, $errors);
            return;
        }
        $cols = array_keys($values);
        Db::run(
            "INSERT INTO {$res['table']} (" . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
            array_values($values)
        );
        Auth::log("$key.creation", (string) ($values['name'] ?? $values['title'] ?? ''));
        Session::flash(ucfirst($res['singular']) . ' ajouté' . self::agree($res) . '.');
        redirect("/admin/$key");
    }

    public static function edit(string $key, string $id): void
    {
        [$res, $user] = self::authorize($key);
        $row = self::find($res, $user, (int) $id);
        self::form($key, $res, $row, $row, []);
    }

    public static function update(string $key, string $id): void
    {
        [$res, $user] = self::authorize($key);
        Csrf::check();
        $row = self::find($res, $user, (int) $id);
        [$values, $errors] = self::input($res, $user, $row);
        if ($errors !== []) {
            self::form($key, $res, $row, $values + $row, $errors);
            return;
        }
        $set = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($values)));
        $touch = self::hasColumn($res['table'], 'updated_at') ? ', updated_at = CURRENT_TIMESTAMP' : '';
        Db::run("UPDATE {$res['table']} SET $set$touch WHERE id = ?", [...array_values($values), $row['id']]);
        Auth::log("$key.modification", (string) ($values['name'] ?? $values['title'] ?? $row['id']));
        Session::flash('Modifications enregistrées.');
        redirect("/admin/$key");
    }

    public static function destroy(string $key, string $id): void
    {
        [$res, $user] = self::authorize($key);
        Csrf::check();
        if (!empty($res['fixed'])) {
            self::notFound();
        }
        $row = self::find($res, $user, (int) $id);
        Db::run("DELETE FROM {$res['table']} WHERE id = ?", [$row['id']]);
        foreach ($res['fields'] as $name => $f) {
            // Un fichier encore utilisé par une autre fiche (logo choisi dans la médiathèque) est gardé.
            if (in_array($f['type'], ['pdf', 'image'], true) && str_starts_with((string) ($row[$name] ?? ''), '/media/')
                && !Db::run("SELECT 1 FROM {$res['table']} WHERE $name = ?", [$row[$name]])->fetchColumn()) {
                @unlink(ROOT . '/public' . $row[$name]);
            }
        }
        Auth::log("$key.suppression", (string) ($row['name'] ?? $row['title'] ?? $row['id']));
        Session::flash(ucfirst($res['singular']) . ' supprimé' . self::agree($res) . '.');
        redirect("/admin/$key");
    }

    /**
     * @param array<string, mixed> $res
     * @param array<string, mixed> $user
     * @param array<string, mixed>|null $row
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private static function input(array $res, array $user, ?array $row): array
    {
        $values = [];
        $errors = [];
        $extra = [];
        foreach ($res['fields'] as $name => $f) {
            $raw = $_POST[$name] ?? null;
            $value = match ($f['type']) {
                'checkbox' => isset($_POST[$name]) ? 1 : 0,
                'number' => is_string($raw) && trim($raw) !== '' ? (($f['step'] ?? '') === 'any' ? (float) str_replace(',', '.', $raw) : (int) $raw) : null,
                'pole' => (int) $raw > 0 ? (int) $raw : null,
                'pdf', 'image' => $row[$name] ?? null,
                default => is_string($raw) ? trim(str_replace("\r\n", "\n", $raw)) : '',
            };
            if ($f['type'] === 'pole' && ($forced = self::editorPole($res, $user)) !== null) {
                $value = $forced;
            }
            if ($f['type'] === 'pdf' && Upload::hasFile($name)) {
                $up = Upload::pdf($name);
                if (is_string($up)) {
                    $errors[$name] = $up;
                } else {
                    $value = $up['path'];
                    $extra = ['size' => $up['size'], 'mime' => 'application/pdf'];
                }
            }
            if ($f['type'] === 'image' && is_string($_POST[$name . '_pick'] ?? null) && $_POST[$name . '_pick'] !== '') {
                $pick = $_POST[$name . '_pick'];
                foreach (MediaController::library() as $item) {
                    if ($item['kind'] === 'logo' && $item['src'] === $pick) {
                        $value = $pick;
                    }
                }
            }
            if ($f['type'] === 'image' && Upload::hasFile($name)) {
                $up = Upload::image($name, 'logos', [640], $f['height'] ?? null);
                if (is_string($up)) {
                    $errors[$name] = $up;
                } else {
                    $value = $up['path'];
                }
            }
            if (in_array($f['type'], ['text', 'email', 'url', 'tel', 'textarea', 'markdown', 'date', 'select'], true) && $value === '') {
                $value = null;
            }
            if ($value === null && $f['type'] === 'number' && isset($f['default'])) {
                $value = $f['default'];
            }
            if (!empty($f['required']) && ($value === null || $value === '') && !isset($errors[$name])) {
                $errors[$name] = 'Champ obligatoire.';
            }
            if ($value !== null && !isset($errors[$name])) {
                $errors[$name] = match ($f['type']) {
                    'email' => filter_var($value, FILTER_VALIDATE_EMAIL) ? null : 'Adresse e-mail invalide.',
                    'url' => preg_match('#^https?://[^\s]+$#i', (string) $value) ? null : 'Adresse web invalide (elle doit commencer par https://).',
                    'date' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? null : 'Date invalide.',
                    'select' => array_key_exists((string) $value, $f['options']) ? null : 'Choix invalide.',
                    'pole' => Db::run('SELECT 1 FROM poles WHERE id = ?', [$value])->fetchColumn() ? null : 'Pôle inconnu.',
                    default => text_length((string) $value) > 20000 ? 'Texte trop long.' : null,
                } ?? ($errors[$name] ?? null);
                if ($errors[$name] === null) {
                    unset($errors[$name]);
                }
            }
            $values[$name] = $value;
        }
        return [$values + $extra, array_filter($errors)];
    }

    /**
     * @param array<string, mixed> $res
     * @param array<string, mixed>|null $row
     * @param array<string, mixed> $values
     * @param array<string, string> $errors
     */
    private static function form(string $key, array $res, ?array $row, array $values, array $errors): void
    {
        if ($errors !== []) {
            http_response_code(422);
        }
        $user = Auth::user();
        View::render('admin/resource/form', [
            'title' => ($row ? 'Modifier : ' : 'Ajouter : ') . $res['singular'],
            'key' => $key,
            'res' => $res,
            'row' => $row,
            'values' => $values,
            'errors' => $errors,
            'poles' => self::poles(),
            'lockedPole' => self::editorPole($res, $user),
        ], 'admin/layout');
    }

    /** @return array{0: array<string, mixed>, 1: array<string, mixed>} */
    private static function authorize(string $key): array
    {
        $user = Auth::require();
        $res = Resources::get($key) ?? self::notFound();
        if (!empty($res['admin_only']) && $user['role'] !== 'admin') {
            http_response_code(403);
            exit('Accès réservé aux administrateurs.');
        }
        return [$res, $user];
    }

    /**
     * Pôle imposé à un éditeur pour les fiches rattachées à un pôle.
     * @param array<string, mixed> $res
     * @param array<string, mixed>|null $user
     */
    private static function editorPole(array $res, ?array $user): ?int
    {
        return $user !== null && $user['role'] === 'editor' && isset($res['fields']['pole_id']) ? (int) $user['pole_id'] : null;
    }

    /**
     * @param array<string, mixed> $res
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private static function find(array $res, array $user, int $id): array
    {
        $row = Db::run("SELECT * FROM {$res['table']} WHERE id = ?", [$id])->fetch();
        $pole = self::editorPole($res, $user);
        if (!$row || ($pole !== null && (int) $row['pole_id'] !== $pole)) {
            self::notFound();
        }
        return $row;
    }

    /** @return list<array<string, mixed>> */
    private static function poles(): array
    {
        return Db::run('SELECT id, name, slug FROM poles ORDER BY position')->fetchAll();
    }

    private static function hasColumn(string $table, string $column): bool
    {
        return in_array($column, array_column(Db::run("PRAGMA table_info($table)")->fetchAll(), 'name'), true);
    }

    /** @param array<string, mixed> $res */
    private static function agree(array $res): string
    {
        return in_array($res['singular'], ['personne', 'offre'], true) ? 'e' : '';
    }

    private static function notFound(): never
    {
        http_response_code(404);
        exit('Fiche introuvable.');
    }
}
