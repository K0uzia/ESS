<?php
use App\Site\Components;
use App\Site\Section;

// Sans JavaScript : les trois pôles se suivent, chacun avec son ancre. Avec : onglets (site.js, data-tabs).
?>
<div class="tabs" data-tabs>
  <div class="tabs__list" data-tabs-list hidden>
    <?php foreach ($poles as $i => $p): ?>
    <button class="tabs__tab" type="button" id="onglet-<?= e($p['slug']) ?>" data-tab="<?= e($p['slug']) ?>" aria-controls="<?= e($p['slug']) ?>"><?= e(str_replace('Pôle ESS ', '', $p['name'])) ?></button>
    <?php endforeach ?>
  </div>
  <?php foreach ($poles as $p): ?>
  <div class="tabs__panel" id="<?= e($p['slug']) ?>" data-tab-panel="<?= e($p['slug']) ?>">
    <div class="pole-intro">
      <div class="pole-intro__brand">
        <?php if ($logo = Components::poleLogo($p['slug'])): ?>
        <img class="pole-intro__logo" src="<?= e($logo) ?>" alt="" width="204" height="125" loading="lazy" decoding="async">
        <?php endif ?>
        <h<?= $h ?> class="pole-intro__name"><?= e($p['name']) ?></h<?= $h ?>>
      </div>
      <address class="pole-intro__contact">
        <span><?= nl2br(e($p['address_lines'])) ?><br><?= e($p['postal_code'] . ' ' . $p['city']) ?></span>
        <span>
          <?php foreach (explode("\n", $p['phones']) as $phone): ?>
          <a href="tel:+33<?= e(substr(str_replace(' ', '', $phone), 1)) ?>"><?= e($phone) ?></a><br>
          <?php endforeach ?>
          <a href="mailto:<?= e($p['email']) ?>"><?= e($p['email']) ?></a>
        </span>
      </address>
    </div>
    <div class="pole-blocks">
    <?php foreach ($p['page']['sections'] ?? [] as $s): ?>
    <?php $html = Section::body($s, $h + 2); ?>
    <?php if ($html !== ''): ?>
    <?php $wide = str_starts_with(trim($s['body']), '@') || text_length($s['body']) > 900; ?>
    <div class="pole-block<?= $wide ? ' pole-block--wide' : '' ?>">
      <h<?= $h + 1 ?> class="pole-block__title"><?= e($s['title']) ?></h<?= $h + 1 ?>>
      <div class="prose"><?= $html ?></div>
    </div>
    <?php endif ?>
    <?php endforeach ?>
    </div>
  </div>
  <?php endforeach ?>
</div>
