<?php
if ($pages === []) {
    return;
}
?>
<ul class="sitemap" role="list">
  <?php foreach ($pages as $p): ?>
  <li class="sitemap__page">
    <h<?= $h ?> class="sitemap__title"><a href="<?= e($p['path']) ?>"><?= e($p['title']) ?></a></h<?= $h ?>>
    <?php if ($p['items'] !== []): ?>
    <ul class="sitemap__sections">
      <?php foreach ($p['items'] as $item): ?>
      <li><a href="<?= e($item['href']) ?>"><?= e($item['title']) ?></a>
        <?php if ($item['children'] !== []): ?>
        <ul><?php foreach ($item['children'] as $c): ?><li><a href="<?= e($c['href']) ?>"><?= e($c['title']) ?></a></li><?php endforeach ?></ul>
        <?php endif ?>
      </li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>
  </li>
  <?php endforeach ?>
</ul>
