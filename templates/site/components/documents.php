<?php
if ($documents === []) {
    return;
}
// Classement d'après le titre : rubriques stables, « Autres documents » pour le reste.
$types = [
    'adhesion' => ['Adhésion', '/adh[ée]sion/iu'],
    'rapports' => ['Rapports d\'activité', '/rapport|bilan|synth[èe]se/iu'],
    'statuts' => ['Statuts et assemblée générale', '/statuts|\bAG\b|assembl[ée]e/iu'],
    'autres' => ['Autres documents', '/.*/'],
];
$groups = [];
foreach ($documents as $d) {
    foreach ($types as $type => [, $pattern]) {
        if (preg_match($pattern, $d['title'])) {
            $groups[$type][] = $d;
            break;
        }
    }
}
$groups = array_intersect_key(array_replace($types, $groups), $groups);
$prefix = 'documents-' . preg_replace('/[^a-z0-9-]/', '', (string) $arg);
$filterable = count($groups) > 1 && count($documents) > 6;
?>
<?php if ($filterable): ?>
<div class="filter filter--compact" data-filter="<?= e($prefix) ?>" data-filter-default="<?= e((string) array_key_first($groups)) ?>">
  <p class="filter__label" id="<?= e($prefix) ?>-filtre">Afficher :</p>
  <ul class="filter__list" role="list" aria-labelledby="<?= e($prefix) ?>-filtre">
    <?php foreach ($groups as $type => $items): ?>
    <li><a class="filter__option" href="#<?= e("$prefix-$type") ?>" data-filter-value="<?= e($type) ?>"><?= e($types[$type][0]) ?> (<?= count($items) ?>)</a></li>
    <?php endforeach ?>
  </ul>
  <p class="visually-hidden" role="status" data-filter-status></p>
</div>
<?php endif ?>
<div id="<?= e($prefix) ?>">
<?php foreach ($groups as $type => $items): ?>
<div class="documents-group" id="<?= e("$prefix-$type") ?>" data-filter-group="<?= e($type) ?>">
  <?php if ($filterable): ?><p class="documents-group__title" data-filter-title><?= e($types[$type][0]) ?></p><?php endif ?>
  <ul class="documents<?= $filterable ? ' documents--compact' : '' ?>" role="list">
    <?php foreach ($items as $d): ?>
    <li class="document">
      <span class="document__badge" aria-hidden="true">PDF</span>
      <span>
        <a href="<?= e($d['file_path']) ?>"><?= e($d['title']) ?><?= $d['year'] && !str_contains($d['title'], (string) $d['year']) ? ' (' . (int) $d['year'] . ')' : '' ?></a>
        <span class="document__meta">PDF, <?= e(number_format($d['size'] / 1048576, 1, ',', ' ')) ?> Mo</span>
      </span>
    </li>
    <?php endforeach ?>
  </ul>
</div>
<?php endforeach ?>
</div>
