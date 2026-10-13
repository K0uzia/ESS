<?php
use App\Admin\Ui;
use App\Core\Csrf;
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/pages/<?= e($key) ?>"><?= Ui::icon('back') ?><?= e($label) ?></a>
    <h1><?= e($title) ?></h1>
  </div>
</header>
<div class="card" data-modal-content data-modal-title="Nouvelle section">
<form id="new-form" method="post" action="/admin/pages/<?= e($key) ?>/sections" novalidate>
  <?= Csrf::field() ?>
  <div class="field">
    <label for="new-title">Titre de la section <span class="req">(obligatoire)</span></label>
    <input id="new-title" name="title" required>
  </div>
  <div class="field">
    <label for="new-body" id="new-body-label">Texte</label>
    <textarea id="new-body" name="body" rows="6" data-rte data-rte-html=""></textarea>
  </div>
  <p class="field__hint">La section s'ajoute à la fin de la page.</p>
</form>
<div class="form-foot" data-modal-foot>
  <button class="btn btn--primary" type="submit" form="new-form"><?= Ui::icon('plus') ?>Ajouter la section</button>
  <a class="btn" href="/admin/pages/<?= e($key) ?>" data-modal-close>Annuler</a>
</div>
</div>
