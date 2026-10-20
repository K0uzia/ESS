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
    /** Phrase ajoutée au message d'enregistrement quand l'adresse vient d'être placée sur la carte. */
    private static string $geoNote = '';

    /** Suggestions d'adresses pour la saisie des adhérents (Géoplateforme IGN, recherche orientée vers le Finistère). */
    public static function addresses(): void
    {
        Auth::require();
        $q = trim((string) ($_GET['q'] ?? ''));
        $out = [];
        if (text_length($q) >= 3 && text_length($q) <= 200) {
            $context = stream_context_create(['http' => ['timeout' => 4, 'ignore_errors' => true]]);
            $json = @file_get_contents('https://data.geopf.fr/geocodage/search?limit=6&lat=48.25&lon=-4.1&q=' . rawurlencode($q), false, $context);
            foreach ((is_string($json) ? (json_decode($json, true)['features'] ?? []) : []) as $f) {
                if (isset($f['properties']['label'])) {
                    $out[] = ['label' => (string) $f['properties']['label'], 'context' => (string) ($f['properties']['context'] ?? '')];
                }
            }
        }
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: private, max-age=300');
        echo json_encode($out, JSON_UNESCAPED_UNICODE);
    }

    public static function index(string $key): void
    {
        [$res, $user] = self::authorize($key);
        $poleFilter = self::editorPole($res, $user) ?? ((int) ($_GET['pole'] ?? 0) ?: null);
        $where = $poleFilter !== null && isset($res['fields']['pole_id']) ? 'WHERE pole_id = ?' : '';
        $params = $where ? [$poleFilter] : [];
        if (isset($res['fixed'])) {
            $where = 'WHERE ' . implode(' AND ', array_map(static fn (string $col): string => "$col IS ?", array_keys($res['fixed'])));
            $params = array_values($res['fixed']);
        }
        $rows = Db::run("SELECT * FROM {$res['table']} $where ORDER BY {$res['order']}", $params)->fetchAll();
        View::render(isset($res['tabs']) ? 'admin/resource/tabs' : 'admin/resource/index', [
            'title' => $res['label'],
            'key' => $key,
            'res' => $res,
            'rows' => $rows,
            'poles' => isset($res['fixed']) ? [] : self::poles(),
            'poleFilter' => $poleFilter,
            'canFilter' => isset($res['fields']['pole_id']) && self::editorPole($res, $user) === null,
        ], 'admin/layout');
    }

    public static function create(string $key): void
    {
        [$res] = self::authorize($key);
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
        [$values, $errors] = self::input($res, $user, null);
        if ($errors !== []) {
            self::form($key, $res, null, $values, $errors);
            return;
        }
        $values += self::geocode($res, $values, null);
        if (isset($res['sortable'])) {
            [$scope, $params] = self::scope($res, $values);
            $values['position'] = (int) Db::run("SELECT COALESCE(MAX(position), 0) + 1 FROM {$res['table']} WHERE $scope", $params)->fetchColumn();
        }
        $cols = array_keys($values);
        Db::run(
            "INSERT INTO {$res['table']} (" . implode(', ', $cols) . ') VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')',
            array_values($values)
        );
        Auth::log("$key.creation", (string) ($values['name'] ?? $values['title'] ?? ''));
        Session::flash(ucfirst($res['singular']) . ' ajouté' . self::agree($res) . '.' . self::$geoNote);
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
        $values += self::geocode($res, $values, $row);
        // Changement de pôle ou de groupe : la fiche passe en fin de son nouveau groupe.
        if (isset($res['sortable']) && self::scope($res, $values + $row) !== self::scope($res, $row)) {
            [$scope, $params] = self::scope($res, $values + $row);
            $values['position'] = (int) Db::run("SELECT COALESCE(MAX(position), 0) + 1 FROM {$res['table']} WHERE $scope", $params)->fetchColumn();
        }
        $set = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($values)));
        $touch = self::hasColumn($res['table'], 'updated_at') ? ', updated_at = CURRENT_TIMESTAMP' : '';
        Db::run("UPDATE {$res['table']} SET $set$touch WHERE id = ?", [...array_values($values), $row['id']]);
        Auth::log("$key.modification", (string) ($values['name'] ?? $values['title'] ?? $row['id']));
        Session::flash('Modifications enregistrées.' . self::$geoNote);
        redirect("/admin/$key");
    }

    /** Monter ou descendre une fiche d'un rang, parmi les fiches du même pôle et du même groupe. */
    public static function move(string $key, string $id): void
    {
        [$res, $user] = self::authorize($key);
        Csrf::check();
        $row = self::find($res, $user, (int) $id);
        $back = '/admin/' . $key . (is_string($_POST['onglet'] ?? null) && preg_match('/^[a-z0-9-]+$/', $_POST['onglet']) ? '?onglet=' . $_POST['onglet'] : '');
        if (!isset($res['sortable'])) {
            redirect($back);
        }
        [$scope, $params] = self::scope($res, $row);
        $ids = array_map('intval', Db::run("SELECT id FROM {$res['table']} WHERE $scope ORDER BY position, name", $params)->fetchAll(\PDO::FETCH_COLUMN));
        $i = array_search((int) $row['id'], $ids, true);
        $j = ($_POST['sens'] ?? '') === 'haut' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) {
            [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]];
            foreach ($ids as $n => $rid) {
                Db::run("UPDATE {$res['table']} SET position = ? WHERE id = ?", [$n + 1, $rid]);
            }
        }
        redirect($back . '#ligne-' . (int) $row['id']);
    }

    /**
     * Latitude et longitude depuis l'adresse, quand elle est nouvelle ou modifiée. Adresse introuvable : fiche hors carte.
     * @param array<string, mixed> $res
     * @param array<string, mixed> $values
     * @param array<string, mixed>|null $row
     * @return array<string, float|null>
     */
    private static function geocode(array $res, array $values, ?array $row): array
    {
        $field = $res['geocode'] ?? null;
        if ($field === null) {
            return [];
        }
        $address = (string) ($values[$field] ?? '');
        if ($row !== null && $address === (string) ($row[$field] ?? '') && $row['latitude'] !== null) {
            return [];
        }
        if ($address === '') {
            return ['latitude' => null, 'longitude' => null];
        }
        $context = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
        $json = @file_get_contents('https://data.geopf.fr/geocodage/search?limit=1&q=' . rawurlencode($address), false, $context);
        $hit = is_string($json) ? (json_decode($json, true)['features'][0] ?? null) : null;
        if (!is_array($hit) || ($hit['properties']['score'] ?? 0) < 0.5) {
            Session::flash('Adresse introuvable : la fiche est enregistrée et figure dans la liste, mais pas sur la carte. Vérifiez l\'adresse (numéro, rue, code postal, ville).', 'error');
            return ['latitude' => null, 'longitude' => null];
        }
        [$lng, $lat] = $hit['geometry']['coordinates'];
        self::$geoNote = ' Adresse trouvée : placé' . self::agree($res) . ' sur la carte (' . $hit['properties']['label'] . ').';
        return ['latitude' => round((float) $lat, 6), 'longitude' => round((float) $lng, 6)];
    }

    /**
     * Fiches partageant le même rang (même pôle, même groupe…) : clause WHERE et paramètres.
     * @param array<string, mixed> $res
     * @param array<string, mixed> $row
     * @return array{0: string, 1: list<mixed>}
     */
    private static function scope(array $res, array $row): array
    {
        $where = [];
        $params = [];
        foreach ($res['sortable'] as $col) {
            if ($row[$col] === null) {
                $where[] = "$col IS NULL";
            } else {
                $where[] = "$col = ?";
                $params[] = $row[$col];
            }
        }
        return [implode(' AND ', $where), $params];
    }

    public static function destroy(string $key, string $id): void
    {
        [$res, $user] = self::authorize($key);
        Csrf::check();
        $row = self::find($res, $user, (int) $id);
        Db::run("DELETE FROM {$res['table']} WHERE id = ?", [$row['id']]);
        foreach ($res['fields'] as $name => $f) {
            // Un fichier encore utilisé par une autre fiche (logo choisi dans la médiathèque) est gardé.
            if (in_array($f['type'], ['pdf', 'image', 'portrait'], true) && str_starts_with((string) ($row[$name] ?? ''), '/media/')
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
                'portrait' => isset($_POST[$name . '_remove']) ? null : ($row[$name] ?? null),
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
            if ($f['type'] === 'portrait' && Upload::hasFile($name)) {
                $up = Upload::portrait($name);
                if (is_string($up)) {
                    $errors[$name] = $up;
                } else {
                    $value = $up['path'];
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
            if (in_array($f['type'], ['text', 'address', 'email', 'url', 'tel', 'textarea', 'markdown', 'date', 'select'], true) && $value === '') {
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
        $values = ($res['fixed'] ?? []) + $values;
        if (isset($res['no_pole'])) {
            [$field, $none] = $res['no_pole'];
            if (in_array($values[$field], $none, true)) {
                $values['pole_id'] = null;
                if (self::editorPole($res, $user) !== null) {
                    $errors[$field] = 'Seul un administrateur gère le groupement et l\'équipe TAg29.';
                }
            } elseif ($values['pole_id'] === null) {
                $errors['pole_id'] = 'Choisissez le pôle.';
            }
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
        $scope = Auth::scope($user);
        if (in_array($scope, ['ess29', 'tag29'], true) && empty($res['shared'])) {
            if (!isset($res['org_rows'][$scope])) {
                http_response_code(403);
                exit('Accès réservé aux administrateurs et aux comptes concernés.');
            }
            // Groupe et pôle imposés : les champs disparaissent du formulaire et sont fixés à l'enregistrement.
            $res['fixed'] = $res['org_rows'][$scope];
            $res['fields'] = array_diff_key($res['fields'], $res['fixed']);
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
        foreach ($res['fixed'] ?? [] as $col => $wanted) {
            if ($row[$col] !== $wanted) {
                self::notFound();
            }
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
