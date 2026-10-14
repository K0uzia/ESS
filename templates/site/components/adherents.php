<?php
use App\Site\Components;
use App\Site\MembersMap;

if ($groups === []) {
    return;
}
$town = static fn (?string $address): string => trim((string) preg_replace('/^.*?\d{5}\s*/', '', (string) $address));

// Points de la carte ; plusieurs adhérents à la même adresse sont écartés en spirale pour rester visibles.
$points = [];
$taken = [];
$outside = 0;
foreach ($groups as $g) {
    foreach ($g['members'] as $m) {
        $p = $m['latitude'] !== null ? MembersMap::project((float) $m['latitude'], (float) $m['longitude']) : null;
        if ($p === null) {
            $outside++;
            continue;
        }
        $key = round($p[0]) . ',' . round($p[1]);
        $n = $taken[$key] = ($taken[$key] ?? -1) + 1;
        $points[] = [$g['slug'], $m, $p[0] + ($n ? 7 * cos($n * 2.4) : 0), $p[1] + ($n ? 7 * sin($n * 2.4) : 0)];
    }
}
$poleLabels = array_column(array_map(static fn (array $g): array => [$g['slug'], $g['label']], $groups), 1, 0);
$width = MembersMap::WIDTH;
$height = MembersMap::height();
?>
<div class="members-toolbar">
<div class="filter" data-filter="adherents">
  <p class="filter__label" id="filtre-adherents">Afficher :</p>
  <ul class="filter__list" role="list" aria-labelledby="filtre-adherents">
    <li><a class="filter__option" href="#adherents-tout" data-filter-value="tout">Tous les pôles</a></li>
    <?php foreach ($groups as $g): ?>
    <li><a class="filter__option pole--<?= e($g['slug']) ?>" href="#adherents-<?= e($g['slug']) ?>" data-filter-value="<?= e($g['slug']) ?>"><span class="filter__dot" aria-hidden="true"></span><?= e($g['label']) ?> (<?= count($g['members']) ?>)</a></li>
    <?php endforeach ?>
  </ul>
  <p class="visually-hidden" role="status" data-filter-status></p>
</div>
<?php if ($points !== []): ?>
<div class="view-switch" data-view-switch hidden>
  <span class="view-switch__label" id="vue-adherents">Vue :</span>
  <div class="view-switch__options" role="group" aria-labelledby="vue-adherents">
    <button class="view-switch__option" type="button" data-view="carte" aria-pressed="true">Carte</button>
    <button class="view-switch__option" type="button" data-view="liste" aria-pressed="false">Liste</button>
  </div>
</div>
<?php endif ?>
</div>
<?php if ($points !== []): ?>
<figure class="members-map" data-map data-view-panel="carte">
  <div class="members-map__viewport">
  <svg class="members-map__svg" viewBox="0 0 <?= $width ?> <?= $height ?>" role="img" aria-labelledby="carte-adherents-legende" data-map-svg>
    <use class="members-map__land" href="/assets/img/finistere.svg#contour"/>
    <?php foreach (MembersMap::TOWNS as [$name, $lat, $lon]): [$x, $y] = MembersMap::project($lat, $lon); ?>
    <text class="members-map__town" x="<?= $x ?>" y="<?= $y + 30 ?>" data-y="<?= $y ?>" font-size="24" aria-hidden="true"><?= e($name) ?></text>
    <?php endforeach ?>
    <?php foreach ($points as [$slug, $m, $x, $y]): ?>
    <?php $place = $town($m['address']); ?>
    <g class="members-map__point pole--<?= e($slug) ?>" data-filter-group="<?= e($slug) ?>" data-name="<?= e($m['name']) ?>" data-meta="<?= e(implode(' · ', array_filter([$m['status'], $place, $poleLabels[$slug] ?? '']))) ?>" data-url="<?= e((string) $m['url']) ?>" data-id="adherent-<?= (int) $m['id'] ?>">
      <circle cx="<?= round($x, 1) ?>" cy="<?= round($y, 1) ?>" r="8"><title><?= e($m['name'] . ($place !== '' ? ' (' . $place . ')' : '')) ?></title></circle>
    </g>
    <?php endforeach ?>
  </svg>
  <div class="members-map__controls" data-map-controls hidden>
    <button class="members-map__control" type="button" data-map-zoom="in" aria-label="Zoomer"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M7 2h2v5h5v2H9v5H7V9H2V7h5z"/></svg></button>
    <button class="members-map__control" type="button" data-map-zoom="out" aria-label="Dézoomer"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M2 7h12v2H2z"/></svg></button>
    <button class="members-map__control" type="button" data-map-zoom="reset" aria-label="Revoir tout le Finistère"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M1 1h5v2H3v3H1zm9 0h5v5h-2V3h-3zM1 10h2v3h3v2H1zm12 0h2v5h-5v-2h3z"/></svg></button>
  </div>
  <div class="members-map__card" data-map-card role="dialog" aria-modal="false" aria-labelledby="carte-fiche-nom" hidden>
    <button class="members-map__close" type="button" data-map-close aria-label="Fermer la fiche"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3.4 2 8 6.6 12.6 2 14 3.4 9.4 8l4.6 4.6-1.4 1.4L8 9.4 3.4 14 2 12.6 6.6 8 2 3.4z"/></svg></button>
    <p class="members-map__name" id="carte-fiche-nom" data-map-name></p>
    <p class="members-map__meta" data-map-meta></p>
    <p class="members-map__links"><a data-map-url rel="noopener" hidden>Site web</a> <a data-map-list href="#">Voir dans la liste</a></p>
  </div>
  </div>
  <figcaption class="members-map__caption" id="carte-adherents-legende">
    Carte des adhérents situés en Finistère : <?= count($points) ?> points, par pôle. <span class="members-map__help" data-map-help hidden>Cliquez sur un point pour voir l'adhérent. Molette (souris sur la carte), boutons ou double-clic pour zoomer ; glisser pour se déplacer ; au clavier, flèches pour passer d'un point à l'autre.</span>
    <?php if ($outside > 0): ?><?= $outside ?> adhérents sans adresse publiée ou hors du département figurent seulement dans la liste.<?php endif ?>
    Sources : cartes GoGoCarto des pôles (licence ODbL), contour IGN.
  </figcaption>
</figure>
<?php endif ?>
<div id="adherents-tout" data-view-panel="liste">
<?php foreach ($groups as $g): ?>
<div class="team-group pole--<?= e($g['slug']) ?>" id="adherents-<?= e($g['slug']) ?>" data-filter-group="<?= e($g['slug']) ?>">
  <div class="team-group__pole">
    <?php if ($logo = Components::poleLogo($g['slug'])): ?><img src="<?= e($logo) ?>" alt="" width="217" height="125" loading="lazy" decoding="async"><?php endif ?>
    <h<?= $h ?> class="team-group__name"><?= e($g['label']) ?> <span class="members__count">(<?= count($g['members']) ?>)</span></h<?= $h ?>>
  </div>
  <ul class="members" role="list">
    <?php foreach ($g['members'] as $m): ?>
    <li id="adherent-<?= (int) $m['id'] ?>">
      <?php if ($m['url']): ?><a href="<?= e($m['url']) ?>" rel="noopener"><?= e($m['name']) ?></a><?php else: ?><span><?= e($m['name']) ?></span><?php endif ?>
      <?php $meta = array_filter([$m['status'], $town($m['address'])]); ?>
      <?php if ($meta !== []): ?><span class="members__status"><?= e(implode(' · ', $meta)) ?></span><?php endif ?>
    </li>
    <?php endforeach ?>
  </ul>
</div>
<?php endforeach ?>
</div>
