<?php
use App\Admin\TextFields;
use App\Admin\Ui;

$meta = $page['meta'];
$base = '/admin/pages/' . e($key);
$todo = static fn (string $text): bool => str_contains($text, '__À_REMPLIR__');
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/pages"><?= Ui::icon('back') ?>Pages du site</a>
    <h1><?= e($label) ?></h1>
    <p class="page-head__sub">Choisissez la partie à modifier : ses textes s'ouvrent dans une fenêtre.</p>
  </div>
  <div class="page-head__actions">
    <a class="btn" href="<?= e($viewUrl) ?>" target="_blank" rel="noopener"><?= Ui::icon('eye') ?>Voir la page<span class="visually-hidden"> (nouvel onglet)</span></a>
  </div>
</header>

<ol class="parts" role="list">
  <?php if (!$isFragment): ?>
  <li class="card part" id="en-tete">
    <span class="part__num" aria-hidden="true"><?= Ui::icon('page') ?></span>
    <div class="part__text">
      <h2 class="part__title">En-tête de la page</h2>
      <p class="part__excerpt"><strong><?= e($meta['h1'] ?? '') ?></strong><?= ($meta['intro'] ?? '') !== '' ? ' · ' . e($meta['intro']) : '' ?></p>
      <?php if ($todo(implode(' ', $meta))): ?><p class="part__chips"><span class="badge badge--warn">À compléter</span></p><?php endif ?>
    </div>
    <div class="part__action button-row">
      <a class="btn btn--primary" href="<?= $base ?>/en-tete" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> l'en-tête de la page</span></a>
    </div>
  </li>
  <?php endif ?>
  <?php foreach ($page['sections'] as $i => $s): ?>
  <?php
    $units = TextFields::parse($s['body'], $s['variant']);
    $counts = array_count_values(array_column($units, 'type'));
    $auto = array_values(array_filter($units, static fn (array $u): bool => $u['type'] === 'component'));
    $onlyAuto = $auto !== [] && count($units) === count($auto);
    $chips = array_filter([
        isset($counts['card']) ? $counts['card'] . ' carte' . ($counts['card'] > 1 ? 's' : '') : '',
        isset($counts['quote']) ? $counts['quote'] . ' témoignage' . ($counts['quote'] > 1 ? 's' : '') : '',
        ($counts['heading'] ?? 0) > 1 ? $counts['heading'] . ' sous-parties' : '',
        isset($counts['button']) ? $counts['button'] . ' bouton' . ($counts['button'] > 1 ? 's' : '') : '',
        isset($counts['table']) ? 'tableau' : '',
    ]);
    $photo = $library[$s['image']] ?? null;
    $comp = $auto !== [] ? Ui::component($auto[0]['raw']) : null;
    $excerpt = TextFields::excerpt($s['body']);
  ?>
  <li class="card part" id="section-<?= e($s['key']) ?>">
    <span class="part__num" aria-hidden="true"><?= $i + 1 ?></span>
    <div class="part__text">
      <h2 class="part__title"><?= e($s['title'] !== '' ? $s['title'] : 'Section sans titre') ?></h2>
      <?php if ($excerpt !== ''): ?><p class="part__excerpt"><?= e($excerpt) ?></p><?php endif ?>
      <?php if ($comp !== null || $chips !== [] || $todo($s['title'] . $s['body'])): ?>
      <p class="part__chips">
        <?php if ($todo($s['title'] . $s['body'])): ?><span class="badge badge--warn">À compléter</span><?php endif ?>
        <?php foreach ($chips as $chip): ?><span class="chip"><?= e($chip) ?></span><?php endforeach ?>
        <?php if ($comp !== null): ?><span class="chip chip--auto"><?= Ui::icon('link') ?>Affiche <?= e($comp[0]) ?></span><?php endif ?>
      </p>
      <?php endif ?>
    </div>
    <?php if ($photo !== null): ?><img class="part__thumb" src="<?= e($photo['src']) ?>" alt="" width="<?= $photo['width'] ?>" height="<?= $photo['height'] ?>" loading="lazy" decoding="async"><?php endif ?>
    <div class="part__action button-row">
      <?php if ($onlyAuto && $comp !== null && $comp[1] !== null): ?>
      <a class="btn" href="<?= e($comp[1]) ?>"><?= e($comp[2]) ?><?= Ui::icon('arrow') ?></a>
      <?php endif ?>
      <a class="btn btn--primary" href="<?= $base ?>/sections/<?= e($s['key']) ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> « <?= e($s['title']) ?> »</span></a>
    </div>
  </li>
  <?php endforeach ?>
</ol>

<p><a class="btn" href="<?= $base ?>/sections/nouvelle" data-modal><?= Ui::icon('plus') ?>Ajouter une section de texte</a></p>
