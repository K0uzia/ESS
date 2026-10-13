<?php use App\Site\Components; ?>
<ul class="pole-cards" role="list">
  <?php foreach ($poles as $p): ?>
  <li class="pole-card">
    <?php if ($logo = Components::poleLogo($p['slug'])): ?>
    <img class="pole-card__logo" src="<?= e($logo) ?>" alt="" width="204" height="125" loading="lazy" decoding="async">
    <?php endif ?>
    <?php if ($p['is_head_office']): ?><span class="pole-card__badge">Siège du groupement</span><?php endif ?>
    <h<?= $h ?> class="pole-card__name"><a href="/les-poles/#<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></h<?= $h ?>>
    <address><?= Components::address($p) ?></address>
    <p class="pole-card__links">
      <?php foreach (explode("\n", $p['phones']) as $phone): ?>
      <a href="tel:+33<?= e(substr(str_replace(' ', '', $phone), 1)) ?>"><?= e($phone) ?></a>
      <?php endforeach ?>
      <a href="mailto:<?= e($p['email']) ?>"><?= e($p['email']) ?></a>
    </p>
    <span class="card__more" aria-hidden="true">Découvrir le pôle <svg class="button__arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M5 12h14M13 6l6 6-6 6"/></svg></span>
  </li>
  <?php endforeach ?>
</ul>
