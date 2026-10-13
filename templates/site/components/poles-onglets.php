<?php
use App\Site\Components;
use App\Site\Section;

// Sans JavaScript : les trois pôles se suivent, chacun avec son ancre. Avec : onglets (site.js, data-tabs).
// Chaque pôle prend la couleur de son logo (classe pole--<slug>, tokens --color-pole-*).
?>
<div class="tabs" data-tabs>
  <div class="tabs__list" data-tabs-list hidden>
    <?php foreach ($poles as $p): ?>
    <button class="tabs__tab pole--<?= e($p['slug']) ?>" type="button" id="onglet-<?= e($p['slug']) ?>" data-tab="<?= e($p['slug']) ?>" aria-controls="<?= e($p['slug']) ?>"><?= e(str_replace('Pôle ESS ', '', $p['name'])) ?></button>
    <?php endforeach ?>
  </div>
  <?php foreach ($poles as $p): ?>
  <?php
  $main = $aside = [];
  foreach ($p['page']['sections'] ?? [] as $s) {
      $html = Section::body($s, $h + 2);
      if ($html === '') {
          continue;
      }
      if (in_array('aside', explode(' ', $s['variant']), true)) {
          $aside[] = [$s, $html];
      } else {
          $main[] = [$s, $html];
      }
  }
  ?>
  <div class="tabs__panel pole--<?= e($p['slug']) ?>" id="<?= e($p['slug']) ?>" data-tab-panel="<?= e($p['slug']) ?>">
    <div class="pole-intro">
      <div class="pole-intro__brand">
        <?php if ($logo = Components::poleLogo($p['slug'])): ?>
        <img class="pole-intro__logo" src="<?= e($logo) ?>" alt="" width="217" height="125" loading="lazy" decoding="async">
        <?php endif ?>
        <div>
          <h<?= $h ?> class="pole-intro__name"><?= e($p['name']) ?></h<?= $h ?>>
          <?php if ($p['is_head_office']): ?><p class="pole-intro__badge">Siège du groupement</p><?php endif ?>
        </div>
      </div>
      <address class="pole-intro__contact">
        <span><?= App\Site\Components::address($p) ?></span>
        <span>
          <?php foreach (explode("\n", $p['phones']) as $phone): ?>
          <a href="tel:+33<?= e(substr(str_replace(' ', '', $phone), 1)) ?>"><?= e($phone) ?></a><br>
          <?php endforeach ?>
          <a href="mailto:<?= e($p['email']) ?>"><?= e($p['email']) ?></a>
        </span>
      </address>
    </div>
    <div class="pole-layout<?= $aside === [] ? ' pole-layout--single' : '' ?>">
      <div class="pole-main">
        <?php foreach ($main as [$s, $html]): ?>
        <section class="pole-part" aria-labelledby="<?= e($p['slug'] . '-' . $s['key']) ?>">
          <h<?= $h + 1 ?> class="pole-part__title" id="<?= e($p['slug'] . '-' . $s['key']) ?>"><?= e($s['title']) ?></h<?= $h + 1 ?>>
          <div class="prose"><?= $html ?></div>
        </section>
        <?php endforeach ?>
      </div>
      <?php if ($aside !== []): ?>
      <div class="pole-aside">
        <?php foreach ($aside as [$s, $html]): ?>
        <section class="pole-aside__item" aria-labelledby="<?= e($p['slug'] . '-' . $s['key']) ?>">
          <h<?= $h + 1 ?> class="pole-aside__title" id="<?= e($p['slug'] . '-' . $s['key']) ?>"><?= e($s['title']) ?></h<?= $h + 1 ?>>
          <div class="prose"><?= $html ?></div>
        </section>
        <?php endforeach ?>
      </div>
      <?php endif ?>
    </div>
  </div>
  <?php endforeach ?>
</div>
