<?php
use App\Core\Markdown;
use App\Site\Section;
use App\Site\SiteController;

$meta = $page['meta'];
$isHome = $pageKey === 'accueil';
$headPhoto = SiteController::HEAD_PHOTO[$pageKey] ?? null;

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
  <img class="hero__photo" src="/assets/img/photos/accueil-960.webp" srcset="/assets/img/photos/accueil-960.webp 960w, /assets/img/photos/accueil-1920.webp 1920w" sizes="100vw" width="1920" height="1080" alt="" fetchpriority="high">
  <div class="hero__inner container">
    <h1 class="hero__eyebrow"><?= e($meta['h1'] ?? setting('raison_sociale')) ?></h1>
    <p class="hero__promesse"><?= e(setting('promesse')) ?></p>
    <p class="hero__baseline"><?= e(setting('baseline')) ?></p>
    <?php if (!empty($meta['intro'])): ?>
    <p class="hero__intro"><?= Markdown::inline($meta['intro']) ?></p>
    <?php endif ?>
    <p class="hero__actions">
      <a class="button button--accent" href="/les-poles/">Découvrir les pôles <span class="button__arrow" aria-hidden="true">→</span></a>
      <a class="button button--ghost" href="#contact">Nous contacter</a>
    </p>
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
      <?php if ($pageKey === 'tag29'): ?>
      <img class="page-head__logo" src="/assets/img/logo-tag29-noir.webp" width="240" height="348" alt="TAg29" fetchpriority="high">
      <?php endif ?>
      <h1><?= e($meta['h1'] ?? '') ?></h1>
      <?php if (!empty($meta['intro'])): ?>
      <p class="page-head__intro"><?= Markdown::inline($meta['intro']) ?></p>
      <?php endif ?>
      <?php $jumps = array_values(array_filter($rendered, fn ($r) => $r[0]['title'] !== '')); ?>
      <?php if (count($jumps) >= 3): ?>
      <nav class="jump" aria-label="Sur cette page">
        <ul>
          <?php foreach ($jumps as [$s]): ?>
          <li><a href="#<?= e($s['key']) ?>"><?= e($s['title']) ?></a></li>
          <?php endforeach ?>
        </ul>
      </nav>
      <?php endif ?>
    </div>
    <?php if ($headPhoto): ?>
    <figure class="page-head__media">
      <img src="/assets/img/photos/<?= e($headPhoto) ?>-800.webp" srcset="/assets/img/photos/<?= e($headPhoto) ?>-800.webp 800w, /assets/img/photos/<?= e($headPhoto) ?>-1600.webp 1600w" sizes="(min-width: 52rem) 40vw, 100vw" width="1600" height="900" alt="" fetchpriority="high">
    </figure>
    <?php endif ?>
  </div>
  <img class="deux-points" src="/assets/img/deux-points-or.webp" width="240" height="97" alt="">
</div>
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
    <div class="split<?= in_array('inverse', $variants, true) ? ' split--inverse' : '' ?>">
      <div>
        <?php if ($s['title'] !== ''): ?><div class="section__head"><h2 id="<?= e($s['key']) ?>-titre"><?= e($s['title']) ?></h2></div><?php endif ?>
        <div class="prose"><?= $html ?></div>
      </div>
      <figure class="split__media"><?= $photo ?></figure>
    </div>
    <?php else: ?>
    <?php if ($s['title'] !== ''): ?><div class="section__head"><h2 id="<?= e($s['key']) ?>-titre"><?= e($s['title']) ?></h2></div><?php endif ?>
    <div class="prose"><?= $html ?></div>
    <?php endif ?>
  </div>
</section>
<?php endforeach ?>
