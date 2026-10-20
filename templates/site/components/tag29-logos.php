<?php
if ($logos === []) {
    return;
}
?>
<ul class="logos logos--tag29" role="list">
  <?php foreach ($logos as $l): ?>
  <?php $size = getimagesize(ROOT . '/public' . $l['path']); ?>
  <?php $img = '<img src="' . e($l['path']) . '" alt="' . e($l['name']) . '" width="' . $size[0] . '" height="' . $size[1] . '" loading="lazy" decoding="async">'; ?>
  <li class="logo-tile"><?= $l['url'] !== '' ? '<a href="' . e($l['url']) . '">' . $img . '</a>' : $img ?></li>
  <?php endforeach ?>
</ul>
