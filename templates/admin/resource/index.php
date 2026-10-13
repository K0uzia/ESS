<?php
use App\Admin\Ui;

$poleNames = array_column($poles, 'name', 'id');
$poleSlug = static fn ($id): string => $id === null || !isset($poleNames[$id]) ? 'aucun' : strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', Ui::poleShort($poleNames[$id])));
$display = static function (string $col, array $row) use ($res, $poleNames): string {
    $f = $res['fields'][$col] ?? ['type' => 'text'];
    $v = $row[$col] ?? null;
    return match (true) {
        $col === 'is_published' => (int) $v === 1 ? '<span class="badge badge--on">En ligne</span>' : '<span class="badge badge--off">Masqué</span>',
        $f['type'] === 'checkbox' => (int) $v === 1 ? 'Oui' : '<span class="muted">Non</span>',
        $f['type'] === 'pole' => Ui::poleBadge($poleNames[$v] ?? null, $f['empty'] ?? '—'),
        $f['type'] === 'select' => e($f['options'][$v] ?? (string) $v),
        $f['type'] === 'image' => $v ? '<img class="thumb" src="' . e($v) . '" alt="" height="32" loading="lazy">' : '<span class="muted">Aucun</span>',
        $f['type'] === 'date' => $v ? e(date('d/m/Y', (int) strtotime($v))) : '<span class="muted">—</span>',
        default => $v === null || $v === '' ? '<span class="muted">—</span>' : e((string) $v),
    };
};
$first = array_key_first($res['columns']);
$fixed = !empty($res['fixed']);
$total = count($rows);
$numeric = static fn (string $col): bool => in_array($res['fields'][$col]['type'] ?? '', ['number', 'date'], true);

// Filtres instantanés : pôle, listes de choix et statut de publication, seulement s'ils départagent la liste.
$filters = [];
$keys = [];
foreach ($res['fields'] as $name => $f) {
    if ($f['type'] === 'pole' && $canFilter) {
        $keys['pole'] = [$name, 'Pôle', static fn ($v) => $poleSlug($v)];
        $opts = [];
        foreach ($poles as $p) {
            $opts[$poleSlug($p['id'])] = Ui::poleShort($p['name']);
        }
        if (isset($f['empty'])) {
            $opts['aucun'] = $f['empty'];
        }
        $filters['pole'] = ['Pôle', $opts];
    } elseif ($f['type'] === 'select') {
        $keys[$name] = [$name, $f['label'], static fn ($v) => (string) $v];
        $filters[$name] = [$f['label'], $f['options']];
    } elseif ($name === 'is_published') {
        $keys['statut'] = [$name, 'Statut', static fn ($v) => (int) $v === 1 ? 'en-ligne' : 'masque'];
        $filters['statut'] = ['Statut', ['en-ligne' => 'En ligne', 'masque' => 'Masqué']];
    }
}
foreach ($keys as $fk => [$col, , $map]) {
    $values = array_unique(array_map(static fn ($r) => $map($r[$col] ?? null), $rows));
    if (count($values) < 2) {
        unset($filters[$fk], $keys[$fk]);
    } else {
        $filters[$fk][1] = array_intersect_key($filters[$fk][1], array_flip($values));
    }
}
$singular = $res['singular'];
$plural = str_ends_with($singular, 's') ? $singular : $singular . 's';
$article = in_array($singular, ['personne', 'offre'], true) ? 'une' : 'un';
?>
<header class="page-head">
  <div class="page-head__text">
    <h1><?= e($res['label']) ?></h1>
    <p class="page-head__sub"><?= e($res['intro']) ?></p>
  </div>
  <?php if (!$fixed): ?>
  <div class="page-head__actions"><a class="btn btn--primary" href="/admin/<?= e($key) ?>/nouveau" data-modal><?= Ui::icon('plus') ?>Ajouter <?= $article ?> <?= e($singular) ?></a></div>
  <?php endif ?>
</header>

<section class="card card--flush" data-list aria-label="<?= e($res['label']) ?>">
  <?php if ($rows === []): ?>
  <div class="empty">
    <strong>Aucun<?= $article === 'une' ? 'e' : '' ?> <?= e($singular) ?> pour l'instant</strong>
    <?php if (!$fixed): ?><a class="btn btn--primary" href="/admin/<?= e($key) ?>/nouveau" data-modal><?= Ui::icon('plus') ?>Ajouter <?= $article ?> <?= e($singular) ?></a><?php endif ?>
  </div>
  <?php else: ?>
  <?php if ($total > 5) { $searchLabel = 'Rechercher'; $countWords = [$singular, $plural]; require ROOT . '/templates/admin/partials/filters.php'; } ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden"><?= e($res['label']) ?></caption>
      <thead>
        <tr>
          <?php foreach ($res['columns'] as $col => $label): ?><th scope="col"<?= $numeric($col) ? ' class="num"' : '' ?>><?= e($label) ?></th><?php endforeach ?>
          <th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as $row): $name = (string) ($row[$first] ?? ''); ?>
        <tr<?php foreach ($keys as $fk => [$col, , $map]): ?> data-f-<?= e($fk) ?>="<?= e($map($row[$col] ?? null)) ?>"<?php endforeach ?>>
          <?php foreach ($res['columns'] as $col => $label): ?>
          <?php if ($col === $first): ?>
          <th scope="row"><a href="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>" data-modal><?= $display($col, $row) ?></a></th>
          <?php else: ?>
          <td data-label="<?= e($label) ?>"<?= $numeric($col) ? ' class="num"' : '' ?>><?= $display($col, $row) ?></td>
          <?php endif ?>
          <?php endforeach ?>
          <td class="actions-col"><a class="btn" href="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> <?= e($name) ?></span></a></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="empty" data-filter-empty hidden><strong>Aucun résultat</strong><p>Aucune fiche ne correspond à la recherche ou aux filtres.</p><button class="btn" type="button" data-filter-reset>Effacer les filtres</button></div>
  <?php endif ?>
</section>
