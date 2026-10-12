<?php if ($offers === []): ?>
<p>Aucune offre d'emploi n'est publiée en ce moment.</p>
<?php return; endif ?>
<ul class="offers" role="list">
  <?php foreach ($offers as $o): ?>
  <li class="card">
    <h<?= $h ?> class="card__title"><?= e($o['title']) ?></h<?= $h ?>>
    <p class="card__note"><?= e(implode(' · ', array_filter([$o['contract'], $o['location'], $o['pole']]))) ?><?= $o['closes_on'] ? ' · Candidature avant le ' . e(date('d/m/Y', strtotime($o['closes_on']))) : '' ?></p>
    <?= App\Core\Markdown::toHtml($o['body'], 1) ?>
    <?php if ($o['apply_url']): ?><p><a href="<?= e($o['apply_url']) ?>" rel="noopener">Candidater</a></p><?php endif ?>
    <?php if ($o['apply_email']): ?><p>Candidature : <a href="mailto:<?= e($o['apply_email']) ?>"><?= e($o['apply_email']) ?></a></p><?php endif ?>
  </li>
  <?php endforeach ?>
</ul>
