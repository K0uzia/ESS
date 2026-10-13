<?php
use App\Core\Markdown;
use App\Site\Section;
use App\Site\SiteController;

$meta = $page['meta'];
$isHome = $pageKey === 'accueil';
$headPhoto = SiteController::HEAD_PHOTO[$pageKey] ?? null;

// Boutons d'en-tête : méta « actions », « Texte | lien ; Texte | lien » (deux au plus).
$actions = [];
foreach (array_slice(array_filter(array_map('trim', explode(';', $meta['actions'] ?? ''))), 0, 2) as $action) {
    [$label, $href] = array_map('trim', array_pad(explode('|', $action, 2), 2, ''));
    if ($label !== '' && preg_match('~^(/|#|https?://)~', $href)) {
        $actions[] = [$label, $href];
    }
}

$rendered = [];
foreach ($page['sections'] as $s) {
    $html = Section::body($s);
    if ($html !== '') {
        $rendered[] = [$s, $html];
    }
}
?>
<?php if ($isHome): ?>
<div class="hero">
  <img class="hero__photo" <?= Section::photoAttributes('accueil', '100vw') ?> alt="" fetchpriority="high">
  <div class="hero__inner container">
    <h1 class="hero__eyebrow"><?= e($meta['h1'] ?? setting('raison_sociale')) ?></h1>
    <p class="hero__promesse"><?= e(setting('promesse')) ?></p>
    <p class="hero__baseline"><?= e(setting('baseline')) ?></p>
    <?php if (!empty($meta['intro'])): ?>
    <p class="hero__intro"><?= Markdown::inline($meta['intro']) ?></p>
    <?php endif ?>
    <?php if ($actions !== []): ?>
    <p class="hero__actions">
      <?php foreach ($actions as $i => [$label, $href]): ?>
      <a class="button <?= $i === 0 ? 'button--accent' : 'button--ghost' ?>" href="<?= e($href) ?>"><?= e($label) ?><?php if ($i === 0): ?> <svg class="button__arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg><?php endif ?></a>
      <?php endforeach ?>
    </p>
    <?php endif ?>
  </div>
  <img class="deux-points" src="/assets/img/deux-points-or.webp" width="240" height="97" alt="">
</div>
<?php else: ?>
<div class="page-head<?= $headPhoto ? '' : ' page-head--plain' ?>">
  <div class="page-head__inner container">
    <div>
      <?php if ($breadcrumb !== []): ?>
      <nav class="breadcrumb" aria-label="Fil d'Ariane">
        <ol>
          <?php foreach ($breadcrumb as [$href, $label]): ?>
          <li><a href="<?= e($href) ?>"><?= e($label) ?></a></li>
          <?php endforeach ?>
          <li aria-current="page"><?= e($meta['h1'] ?? '') ?></li>
        </ol>
      </nav>
      <?php endif ?>
      <?php if ($pageKey === 'tag29'): ?><div class="page-head__brand">
      <img class="page-head__logo" src="/assets/img/logo-tag29-noir.webp" width="240" height="348" alt="TAg29" fetchpriority="high">
      <div><?php endif ?>
      <h1><?= e($meta['h1'] ?? '') ?></h1>
      <?php if (!empty($meta['intro'])): ?>
      <p class="page-head__intro"><?= Markdown::inline($meta['intro']) ?></p>
      <?php endif ?>
      <?php if ($pageKey === 'tag29'): ?></div></div><?php endif ?>
      <?php if ($actions !== []): ?>
      <p class="page-head__actions">
        <?php foreach ($actions as $i => [$label, $href]): ?>
        <a class="button <?= $i === 0 ? 'button--accent' : 'button--ghost' ?>" href="<?= e($href) ?>"><?= e($label) ?><?php if ($i === 0): ?> <svg class="button__arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg><?php endif ?></a>
        <?php endforeach ?>
      </p>
      <?php endif ?>
    </div>
    <?php if ($headPhoto): ?>
    <figure class="page-head__media">
      <img <?= Section::photoAttributes($headPhoto, '(min-width: 52rem) 40vw, 100vw') ?> alt="" fetchpriority="high">
    </figure>
    <?php endif ?>
  </div>
  <img class="deux-points" src="/assets/img/deux-points-or.webp" width="240" height="97" alt="">
</div>
<?php endif ?>
<?php $jumps = $isHome ? [] : array_values(array_filter($rendered, fn ($r) => $r[0]['title'] !== '')); ?>
<?php if (count($jumps) >= 3): ?>
<nav class="subnav" aria-label="Sur cette page" data-subnav>
  <div class="subnav__inner container">
    <ul class="subnav__list">
      <?php foreach ($jumps as [$s]): ?>
      <li><a class="subnav__link" href="#<?= e($s['key']) ?>"><?= e($s['title']) ?></a></li>
      <?php endforeach ?>
    </ul>
  </div>
</nav>
<?php endif ?>

<?php
$plain = 0;
foreach ($rendered as [$s, $html]):
    $variants = array_filter(explode(' ', $s['variant']));
    $isSplit = in_array('split', $variants, true) && ($photo = Section::photo($s['image'])) !== '';
    $tone = in_array('sombre', $variants, true) || in_array('encart', $variants, true) ? 'sombre' : (in_array('fond', $variants, true) ? 'fond' : null);
    if ($tone === null) {
        // Alternance automatique blanc / teinté pour rythmer la page.
        $tone = $plain++ % 2 === 1 ? 'fond' : null;
    }
?>
<?php $extra = implode('', array_map(fn ($v) => ' section--' . $v, array_intersect($variants, ['etapes', 'temoignages', 'encart', 'blocs', 'frise']))); ?>
<section class="section<?= $tone ? ' section--' . $tone : '' ?><?= $extra ?>" id="<?= e($s['key']) ?>"<?= $s['title'] !== '' ? ' aria-labelledby="' . e($s['key']) . '-titre"' : '' ?>>
  <div class="container">
    <?php if ($isSplit): ?>
    <?php [$beside, $below] = Section::splitParts($s); ?>
    <div class="split<?= in_array('inverse', $variants, true) ? ' split--inverse' : '' ?>">
      <div>
        <?php if ($s['title'] !== ''): ?><div class="section__head"><h2 id="<?= e($s['key']) ?>-titre"><?= e($s['title']) ?></h2></div><?php endif ?>
        <div class="prose"><?= $beside ?></div>
      </div>
      <figure class="split__media"><?= $photo ?></figure>
    </div>
    <?php if ($below !== ''): ?><div class="prose split__below"><?= $below ?></div><?php endif ?>
    <?php else: ?>
    <?php if ($s['title'] !== ''): ?><div class="section__head"><h2 id="<?= e($s['key']) ?>-titre"><?= e($s['title']) ?></h2></div><?php endif ?>
    <div class="prose"><?= $html ?></div>
    <?php endif ?>
  </div>
</section>
<?php endforeach ?>
