<?php
if ($documents === []) {
    return;
}
?>
<ul class="documents" role="list">
  <?php foreach ($documents as $d): ?>
  <li class="document">
    <span class="document__badge" aria-hidden="true">PDF</span>
    <span>
      <a href="<?= e($d['file_path']) ?>"><?= e($d['title']) ?><?= $d['year'] && !str_contains($d['title'], (string) $d['year']) ? ' (' . (int) $d['year'] . ')' : '' ?></a>
      <span class="document__meta">PDF, <?= e(number_format($d['size'] / 1048576, 1, ',', ' ')) ?> Mo</span>
    </span>
  </li>
  <?php endforeach ?>
</ul>
