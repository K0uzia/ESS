<?php
if ($groups === []) {
    return;
}
?>
<div class="filter" data-filter="adherents">
  <p class="filter__label" id="filtre-adherents">Afficher :</p>
  <ul class="filter__list" role="list" aria-labelledby="filtre-adherents">
    <li><a class="filter__option" href="#adherents-tout" data-filter-value="tout">Tous les pôles</a></li>
    <?php foreach ($groups as $g): ?>
    <li><a class="filter__option" href="#adherents-<?= e($g['slug']) ?>" data-filter-value="<?= e($g['slug']) ?>"><?= e($g['label']) ?> (<?= count($g['members']) ?>)</a></li>
    <?php endforeach ?>
  </ul>
  <p class="visually-hidden" role="status" data-filter-status></p>
</div>
<?php if ($map !== ''): ?>
<div class="map-facade" data-map-src="<?= e($map) ?>">
  <p>La carte des adhérents est hébergée par GoGoCarto : l'afficher charge un service extérieur. <a href="<?= e($map) ?>" rel="noopener">Ouvrir la carte sur GoGoCarto</a></p>
  <button class="button button--secondary" type="button" data-map-load>Afficher la carte</button>
</div>
<?php endif ?>
<div id="adherents-tout">
<?php foreach ($groups as $g): ?>
<div class="team-group" id="adherents-<?= e($g['slug']) ?>" data-filter-group="<?= e($g['slug']) ?>">
  <div class="team-group__pole">
    <?php if ($logo = App\Site\Components::poleLogo($g['slug'])): ?><img src="<?= e($logo) ?>" alt="" width="204" height="125" loading="lazy" decoding="async"><?php endif ?>
    <h<?= $h ?> class="team-group__name"><?= e($g['label']) ?> <span class="members__status">(<?= count($g['members']) ?>)</span></h<?= $h ?>>
  </div>
  <ul class="members" role="list">
    <?php foreach ($g['members'] as $m): ?>
    <li>
      <?php if ($m['url']): ?><a href="<?= e($m['url']) ?>" rel="noopener"><?= e($m['name']) ?></a><?php else: ?><?= e($m['name']) ?><?php endif ?>
      <?php if ($m['status']): ?><span class="members__status"><?= e($m['status']) ?></span><?php endif ?>
    </li>
    <?php endforeach ?>
  </ul>
</div>
<?php endforeach ?>
</div>
