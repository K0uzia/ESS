<?php
/**
 * Liste rangée par pôle (un onglet par pôle, plus un onglet hors pôle si prévu), puis par groupe.
 * Chaque groupe a son bouton « Ajouter », qui ouvre la fiche avec le pôle et le groupe déjà remplis.
 */
use App\Admin\Ui;

$cfg = $res['tabs'];
$groupField = $cfg['group'] ?? null;
$groupLabels = $groupField !== null ? $res['fields'][$groupField]['options'] : [];
$singular = $res['singular'];
$article = in_array($singular, ['personne', 'offre'], true) ? 'une' : 'un';

// Onglets : pôles visibles par l'utilisateur (un éditeur ne voit que le sien), puis l'onglet hors pôle.
$tabs = [];
foreach ($poles as $p) {
    if ($poleFilter === null || (int) $p['id'] === (int) $poleFilter) {
        $tabs[$p['slug']] = ['label' => Ui::poleShort($p['name']), 'pole' => (int) $p['id'], 'groups' => $cfg['groups'] ?? [''], 'slug' => $p['slug']];
    }
}
if (isset($cfg['extra']) && $poleFilter === null) {
    $tabs['hors-pole'] = ['label' => $cfg['extra'], 'pole' => null, 'groups' => $cfg['extra_groups'] ?? [''], 'slug' => ''];
}
foreach ($tabs as $slug => &$tab) {
    $tab['rows'] = array_values(array_filter($rows, static fn (array $r): bool => ($r['pole_id'] === null ? null : (int) $r['pole_id']) === $tab['pole']));
    // Groupe présent dans les données mais non prévu pour cet onglet : affiché quand même.
    foreach ($tab['rows'] as $r) {
        $g = $groupField !== null ? (string) $r[$groupField] : '';
        if (!in_array($g, $tab['groups'], true)) {
            $tab['groups'][] = $g;
        }
    }
}
unset($tab);
$active = isset($tabs[$_GET['onglet'] ?? '']) ? $_GET['onglet'] : array_key_first($tabs);
$multi = count($tabs) > 1;
$addUrl = static function (array $tab, string $group) use ($key, $groupField): string {
    $q = array_filter(['pole_id' => $tab['pole'], $groupField ?? '' => $group]);
    return '/admin/' . $key . '/nouveau' . ($q !== [] ? '?' . http_build_query($q) : '');
};
?>
<header class="page-head">
  <div class="page-head__text">
    <h1><?= e($res['label']) ?></h1>
    <p class="page-head__sub"><?= e($res['intro']) ?></p>
  </div>
</header>

<div class="tabset" data-tabset>
  <?php if ($multi): ?>
  <div class="tabset__list" role="tablist" aria-label="Pôles" hidden data-tabset-list>
    <?php foreach ($tabs as $slug => $tab): ?>
    <button class="tabset__tab" type="button" role="tab" id="tab-<?= e($slug) ?>" aria-controls="panel-<?= e($slug) ?>" aria-selected="<?= $slug === $active ? 'true' : 'false' ?>" tabindex="<?= $slug === $active ? '0' : '-1' ?>" data-tab="<?= e($slug) ?>">
      <?php if ($tab['slug'] !== ''): ?><span class="tabset__dot pole-dot--<?= e(preg_replace('/^pays-de-/', '', $tab['slug'])) ?>" aria-hidden="true"></span><?php endif ?>
      <?= e($tab['label']) ?> <span class="tabset__count"><?= count($tab['rows']) ?></span>
    </button>
    <?php endforeach ?>
  </div>
  <?php endif ?>

  <?php foreach ($tabs as $slug => $tab): ?>
  <section class="tabset__panel" id="panel-<?= e($slug) ?>"<?= $multi ? ' role="tabpanel" aria-labelledby="tab-' . e($slug) . '"' : '' ?> data-tab-panel="<?= e($slug) ?>" data-list data-list-local>
    <h2 class="tabset__heading"><?= e($tab['pole'] !== null ? 'Pôle ESS Pays de ' . $tab['label'] : $tab['label']) ?></h2>
    <?php if (count($tab['rows']) > 8): ?>
    <div class="tabset__search" data-filters hidden>
      <div class="field field--search">
        <label for="q-<?= e($slug) ?>">Rechercher dans <?= e($tab['label']) ?></label>
        <div class="search"><?= Ui::icon('search') ?><input id="q-<?= e($slug) ?>" type="search" autocomplete="off" data-filter-q></div>
      </div>
      <button class="btn btn--ghost" type="button" data-filter-reset hidden><?= Ui::icon('close') ?>Effacer</button>
      <p class="filters__count" data-filter-count data-one="<?= e($singular) ?>" data-many="<?= e($singular . 's') ?>" aria-live="polite"></p>
    </div>
    <?php endif ?>
    <?php foreach ($tab['groups'] as $group): $items = array_values(array_filter($tab['rows'], static fn (array $r): bool => $groupField === null || (string) $r[$groupField] === $group)); ?>
    <div class="card card--flush group-card" data-filter-grid>
      <div class="group-card__head">
        <h3 class="group-card__title"><?= e($groupLabels[$group] ?? ($groupField === null ? ucfirst($singular) . 's du pôle' : 'Sans groupe')) ?> <span class="tabset__count"><?= count($items) ?></span></h3>
        <a class="btn btn--primary" href="<?= e($addUrl($tab, $group)) ?>" data-modal><?= Ui::icon('plus') ?>Ajouter<span class="visually-hidden"> <?= $article ?> <?= e($singular) ?> : <?= e($tab['label']) ?>, <?= e($groupLabels[$group] ?? '') ?></span></a>
      </div>
      <?php if ($items === []): ?>
      <p class="group-card__empty">Aucun<?= $article === 'une' ? 'e' : '' ?> <?= e($singular) ?> pour l'instant.</p>
      <?php else: ?>
      <ul class="rows" role="list">
        <?php foreach ($items as $row): $detail = (string) ($row[$cfg['detail'] ?? ''] ?? ''); ?>
        <li class="rows__item" data-filter-row>
          <a class="rows__main" href="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>" data-modal>
            <span class="rows__name"><?= e((string) $row['name']) ?></span>
            <?php if ($detail !== ''): ?><span class="rows__detail"><?= e($detail) ?></span><?php endif ?>
          </a>
          <?php if (isset($row['is_published']) && (int) $row['is_published'] !== 1): ?><span class="badge badge--off">Masqué</span><?php endif ?>
          <a class="btn btn--icon btn--ghost" href="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>" data-modal tabindex="-1" aria-hidden="true"><?= Ui::icon('edit') ?></a>
        </li>
        <?php endforeach ?>
      </ul>
      <?php endif ?>
    </div>
    <?php endforeach ?>
    <div class="card empty" data-filter-empty hidden><strong>Aucun résultat</strong><p>Aucun résultat pour cette recherche dans cet onglet.</p><button class="btn" type="button" data-filter-reset>Effacer la recherche</button></div>
  </section>
  <?php endforeach ?>
</div>
