<?php
/**
 * Liste en lignes (comme Ulamir) : vignette, nom, détails, état, flèches d'ordre, « Modifier ».
 * Avec 'list_group' [champ de choix, titres des groupes], une carte par valeur, chacune avec son bouton « Ajouter ».
 */
use App\Admin\Ui;

$poleNames = array_column($poles, 'name', 'id');
$poleSlug = static fn ($id): string => $id === null || !isset($poleNames[$id]) ? 'aucun' : strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', Ui::poleShort($poleNames[$id])));
$first = array_key_first($res['columns']);
[$groupField, $groups] = $res['list_group'] ?? [null, ['' => '']];
$sortable = isset($res['sortable']);
$imageCol = null;
foreach ($res['columns'] as $col => $label) {
    if (($res['fields'][$col]['type'] ?? '') === 'image') {
        $imageCol = $col;
    }
}
// Détails d'une ligne : colonnes hors nom, image, état et groupe, en texte simple.
$details = static function (array $row) use ($res, $poleNames, $first, $imageCol, $groupField): array {
    $out = [];
    foreach ($res['columns'] as $col => $label) {
        if (in_array($col, [$first, $imageCol, $groupField, 'is_published'], true)) {
            continue;
        }
        $f = $res['fields'][$col] ?? ['type' => 'text'];
        $v = $row[$col] ?? null;
        if ($v === null || $v === '') {
            continue;
        }
        $out[] = match ($f['type']) {
            'pole' => Ui::poleBadge($poleNames[$v] ?? null),
            'select' => e($f['options'][$v] ?? (string) $v),
            'date' => e($label . ' : ' . date('d/m/Y', (int) strtotime((string) $v))),
            default => e((string) $v),
        };
    }
    return $out;
};

// Filtres instantanés : pôle, listes de choix et statut de publication, seulement s'ils départagent la liste.
$filters = [];
$keys = [];
foreach ($res['fields'] as $name => $f) {
    if ($f['type'] === 'pole' && $canFilter) {
        $keys['pole'] = [$name, static fn ($v) => $poleSlug($v)];
        $opts = [];
        foreach ($poles as $p) {
            $opts[$poleSlug($p['id'])] = Ui::poleShort($p['name']);
        }
        if (isset($f['empty'])) {
            $opts['aucun'] = $f['empty'];
        }
        $filters['pole'] = ['Pôle', $opts];
    } elseif ($f['type'] === 'select' && $name !== $groupField) {
        $keys[$name] = [$name, static fn ($v) => (string) $v];
        $filters[$name] = [$f['label'], $f['options']];
    } elseif ($name === 'is_published') {
        $keys['statut'] = [$name, static fn ($v) => (int) $v === 1 ? 'en-ligne' : 'masque'];
        $filters['statut'] = ['Sur le site', ['en-ligne' => 'Affiché', 'masque' => 'Masqué']];
    }
}
foreach ($keys as $fk => [$col, $map]) {
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
  <?php if ($groupField === null): ?>
  <div class="page-head__actions"><a class="btn btn--primary" href="/admin/<?= e($key) ?>/nouveau" data-modal><?= Ui::icon('plus') ?>Ajouter <?= $article ?> <?= e($singular) ?></a></div>
  <?php endif ?>
</header>

<div data-list>
  <?php if (count($rows) > 5) { $searchLabel = 'Rechercher'; $countWords = [$singular, $plural]; $total = count($rows); require ROOT . '/templates/admin/partials/filters.php'; } ?>
  <?php foreach ($groups as $group => $groupLabel): $items = array_values(array_filter($rows, static fn (array $r): bool => $groupField === null || (string) $r[$groupField] === (string) $group)); ?>
  <section class="card card--flush group-card" data-filter-grid aria-label="<?= e($groupLabel !== '' ? $groupLabel : $res['label']) ?>">
    <?php if ($groupField !== null): ?>
    <div class="group-card__head">
      <h2 class="group-card__title"><?= e($groupLabel) ?> <span class="tabset__count"><?= count($items) ?></span></h2>
      <a class="btn btn--primary" href="/admin/<?= e($key) ?>/nouveau?<?= e(http_build_query([$groupField => $group])) ?>" data-modal><?= Ui::icon('plus') ?>Ajouter<span class="visually-hidden"> : <?= e($groupLabel) ?></span></a>
    </div>
    <?php endif ?>
    <?php if ($items === []): ?>
    <p class="group-card__empty">Aucun<?= $article === 'une' ? 'e' : '' ?> <?= e($singular) ?> pour l'instant.<?php if ($groupField === null): ?> <a href="/admin/<?= e($key) ?>/nouveau" data-modal>Ajouter <?= $article ?> <?= e($singular) ?></a><?php endif ?></p>
    <?php else: ?>
    <ul class="rows" role="list">
      <?php foreach ($items as $n => $row): $name = (string) ($row[$first] ?? ''); $href = '/admin/' . $key . '/' . (int) $row['id']; ?>
      <li class="rows__item" id="ligne-<?= (int) $row['id'] ?>" data-filter-row<?php foreach ($keys as $fk => [$col, $map]): ?> data-f-<?= e($fk) ?>="<?= e($map($row[$col] ?? null)) ?>"<?php endforeach ?>>
        <?php if ($imageCol !== null): ?>
        <span class="rows__thumb"><?php if ($row[$imageCol]): ?><img src="<?= e($row[$imageCol]) ?>" alt="" loading="lazy" decoding="async"><?php endif ?></span>
        <?php endif ?>
        <a class="rows__main" href="<?= e($href) ?>" data-modal>
          <span class="rows__name"><?= e($name) ?></span>
          <?php if ($d = $details($row)): ?><span class="rows__detail"><?= implode(' · ', $d) ?></span><?php endif ?>
        </a>
        <?php if (isset($row['is_published']) && (int) $row['is_published'] !== 1): ?><span class="badge badge--off">Masqué</span><?php endif ?>
        <?php if ($sortable && count($items) > 1) { $isFirst = $n === 0; $isLast = $n === count($items) - 1; $moveTab = ''; require ROOT . '/templates/admin/partials/move.php'; } ?>
        <a class="btn" href="<?= e($href) ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> <?= e($name) ?></span></a>
      </li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>
  </section>
  <?php endforeach ?>
  <div class="card empty" data-filter-empty hidden><strong>Aucun résultat</strong><p>Aucune fiche ne correspond à la recherche ou aux filtres.</p><button class="btn" type="button" data-filter-reset>Effacer les filtres</button></div>
</div>
