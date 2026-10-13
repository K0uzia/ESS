<?php
use App\Admin\Ui;

$photos = count(array_filter($library, static fn (array $i): bool => $i['kind'] === 'photo'));
$filters = [
    'type' => ['Type', ['photo' => 'Photos', 'logo' => 'Logos']],
    'emploi' => ['Utilisation', ['utilisee' => 'Utilisées', 'libre' => 'Disponibles']],
];
$searchLabel = 'Rechercher une image';
$countWords = ['image', 'images'];
$total = count($library);
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Médiathèque</h1>
    <p class="page-head__sub">Toutes les images du site : <?= $photos ?> photos et <?= $total - $photos ?> logos. Une photo se change depuis la section qui l'affiche.</p>
  </div>
  <div class="page-head__actions"><a class="btn btn--primary" href="/admin/medias/nouvelle" data-modal><?= Ui::icon('upload') ?>Ajouter une photo</a></div>
</header>

<section data-list aria-label="Images du site">
  <div class="card filters-card"><?php require ROOT . '/templates/admin/partials/filters.php'; ?></div>
  <ul class="library" role="list" data-filter-grid>
    <?php foreach ($library as $value => $item): $used = $item['uses'] !== []; ?>
    <li class="card library__item library__item--<?= e($item['kind']) ?>" data-filter-row data-f-type="<?= e($item['kind']) ?>" data-f-emploi="<?= $used ? 'utilisee' : 'libre' ?>">
      <div class="library__img"><img src="<?= e($item['src']) ?>" alt="" width="<?= $item['width'] ?>" height="<?= $item['height'] ?>" loading="lazy" decoding="async"></div>
      <div class="library__body">
        <p class="library__name"><?= e($item['label']) ?></p>
        <p class="library__meta"><span class="chip"><?= e($item['source']) ?></span><?= $used ? '' : ' <span class="badge badge--off">Disponible</span>' ?></p>
        <?php if ($used): ?>
        <ul class="library__uses" role="list">
          <?php foreach ($item['uses'] as $use): ?>
          <li><?= isset($use['href']) ? '<a href="' . e($use['href']) . '">' . e($use['label']) . '</a>' : e($use['label']) ?></li>
          <?php endforeach ?>
        </ul>
        <?php endif ?>
      </div>
      <?php if (isset($item['id'])): ?>
      <a class="btn library__edit" href="/admin/medias/<?= $item['id'] ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> <?= e($item['label']) ?></span></a>
      <?php endif ?>
    </li>
    <?php endforeach ?>
  </ul>
  <div class="card empty" data-filter-empty hidden><strong>Aucune image</strong><p>Aucune image ne correspond à la recherche ou aux filtres.</p><button class="btn" type="button" data-filter-reset>Effacer les filtres</button></div>
</section>
