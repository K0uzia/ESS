<?php
if ($partners === []) {
    return;
}
$byKind = ['partenaire' => [], 'reseau' => []];
foreach ($partners as $p) {
    $byKind[$p['kind']][] = $p;
}
$labels = ['partenaire' => 'Partenaires institutionnels', 'reseau' => 'Réseaux'];
?>
<?php foreach ($byKind as $kind => $items): ?>
<?php if ($items !== []): ?>
<h<?= $h ?> class="logos__group"><?= $labels[$kind] ?></h<?= $h ?>>
<ul class="logos" role="list">
  <?php foreach ($items as $p): ?>
  <li class="logo-tile">
    <?php
    $size = $p['logo_path'] && is_file(ROOT . '/public' . $p['logo_path']) ? getimagesize(ROOT . '/public' . $p['logo_path']) : false;
    $img = $size ? '<img src="' . e($p['logo_path']) . '" alt="' . e($p['name']) . '" width="' . $size[0] . '" height="' . $size[1] . '" loading="lazy" decoding="async">' : '<span>' . e($p['name']) . '</span>';
    ?>
    <?php if ($p['url']): ?><a href="<?= e($p['url']) ?>" rel="noopener"><?= $img ?></a><?php else: ?><?= $img ?><?php endif ?>
  </li>
  <?php endforeach ?>
</ul>
<?php endif ?>
<?php endforeach ?>
