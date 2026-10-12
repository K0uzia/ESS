<div class="page-title">
  <p class="page-title__kicker">Contenus</p>
  <h1>Pages du site</h1>
</div>
<p class="lead">Modifiez les textes, l'ordre et la mise en page des sections. Les blocs dynamiques (équipe, adhérents, documents…) se gèrent dans leur propre module.</p>
<ul class="tiles" role="list">
  <?php foreach ($pages as $key => $label): ?>
  <li class="tile">
    <a class="tile__link" href="/admin/pages/<?= e($key) ?>"><?= e($label) ?></a>
    <span class="tile__meta"><?= (int) ($updated[$key]['n'] ?? 0) ?> sections<?= isset($updated[$key]['u']) ? ' · modifiée le ' . e(date('d/m/Y', strtotime($updated[$key]['u'] . ' UTC'))) : '' ?></span>
  </li>
  <?php endforeach ?>
</ul>
