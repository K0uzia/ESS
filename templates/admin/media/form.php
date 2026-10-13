<?php
use App\Admin\Ui;
use App\Core\Csrf;

$isNew = $item === null;
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/medias"><?= Ui::icon('back') ?>Médiathèque</a>
    <h1><?= e($isNew ? 'Ajouter une photo' : $item['label']) ?></h1>
  </div>
</header>
<div class="card" data-modal-content data-modal-title="<?= e($isNew ? 'Ajouter une photo' : 'Photo « ' . $item['label'] . ' »') ?>">
<?php if ($isNew): ?>
<form id="media-form" method="post" action="/admin/medias" enctype="multipart/form-data" novalidate>
  <?= Csrf::field() ?>
  <div class="field">
    <label for="photo">Photo <span class="req">(obligatoire)</span></label>
    <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" required aria-describedby="photo-hint">
    <p class="field__hint" id="photo-hint">JPG, PNG ou WebP, 15 Mo au plus.</p>
  </div>
  <div class="field">
    <label for="alt">Description courte</label>
    <input id="alt" name="alt" maxlength="120" aria-describedby="alt-hint">
    <p class="field__hint" id="alt-hint">Pour la retrouver au moment de la choisir.</p>
  </div>
  <div class="field">
    <label class="switch" for="rights">
      <input type="checkbox" id="rights" name="rights" value="1" required>
      <span class="switch__track" aria-hidden="true"></span>
      <span class="switch__text">Cette photo est réelle, et j'ai l'accord des personnes reconnaissables et du photographe pour la publier.</span>
    </label>
  </div>
</form>
<div class="form-foot" data-modal-foot>
  <button class="btn btn--primary" type="submit" form="media-form"><?= Ui::icon('upload') ?>Ajouter la photo</button>
  <a class="btn" href="/admin/medias" data-modal-close>Annuler</a>
</div>
<?php else: ?>
<img class="media-preview" src="<?= e($item['src']) ?>" alt="" width="<?= $item['width'] ?>" height="<?= $item['height'] ?>">
<form id="media-form" method="post" action="/admin/medias/<?= $item['id'] ?>" novalidate>
  <?= Csrf::field() ?>
  <div class="field">
    <label for="alt">Description courte</label>
    <input id="alt" name="alt" maxlength="120" value="<?= e($item['label']) ?>">
  </div>
  <?php if ($item['uses'] !== []): ?>
  <p class="field__hint">Utilisée : <?= e(implode(', ', array_column($item['uses'], 'label'))) ?>. Pour la supprimer, choisissez d'abord une autre photo dans cette section.</p>
  <?php endif ?>
</form>
<form id="media-delete" method="post" action="/admin/medias/<?= $item['id'] ?>/suppression" hidden data-confirm="Supprimer cette photo ? Cette action est définitive.">
  <?= Csrf::field() ?>
</form>
<div class="form-foot" data-modal-foot>
  <button class="btn btn--primary" type="submit" form="media-form">Enregistrer</button>
  <a class="btn" href="/admin/medias" data-modal-close>Annuler</a>
  <?php if ($item['uses'] === []): ?><button class="btn btn--danger form-foot__end" type="submit" form="media-delete"><?= Ui::icon('trash') ?>Supprimer<span class="visually-hidden"> la photo</span></button><?php endif ?>
</div>
<?php endif ?>
</div>
