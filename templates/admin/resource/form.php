<?php use App\Core\Csrf; ?>
<div class="page-title">
  <p class="page-title__kicker"><a href="/admin/<?= e($key) ?>"><?= e($res['label']) ?></a></p>
  <h1><?= e($title) ?></h1>
</div>
<?php if ($errors !== []): ?>
<p class="alert alert--error" role="alert">Erreur : la fiche n'a pas été enregistrée. Corrigez les champs signalés.</p>
<?php endif ?>
<form class="panel form-grid" method="post" action="/admin/<?= e($key) ?><?= $row ? '/' . (int) $row['id'] : '' ?>" enctype="multipart/form-data" novalidate>
  <?= Csrf::field() ?>
  <?php foreach ($res['fields'] as $name => $f): ?>
  <?php
    $value = $values[$name] ?? '';
    $error = $errors[$name] ?? null;
    require ROOT . '/templates/admin/partials/field.php';
  ?>
  <?php endforeach ?>
  <div class="form-actions">
    <button class="button" type="submit">Enregistrer</button>
    <a class="button button--secondary" href="/admin/<?= e($key) ?>">Annuler</a>
  </div>
</form>
