<?php
use App\Admin\ContentController;
use App\Admin\Ui;
use App\Core\Csrf;

$actions = ContentController::actions($meta['actions'] ?? '');
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/pages/<?= e($key) ?>"><?= Ui::icon('back') ?><?= e($label) ?></a>
    <h1><?= e($title) ?></h1>
  </div>
</header>
<div class="card" data-modal-content data-modal-title="En-tête de la page « <?= e($label) ?> »">
<form id="head-form" method="post" action="/admin/pages/<?= e($key) ?>/en-tete" novalidate>
  <?= Csrf::field() ?>
  <div class="field">
    <label for="meta-h1">Grand titre <span class="req">(obligatoire)</span></label>
    <input id="meta-h1" name="meta[h1]" value="<?= e($meta['h1'] ?? '') ?>" required>
  </div>
  <div class="field">
    <label for="meta-intro">Phrase d'introduction</label>
    <textarea id="meta-intro" name="meta[intro]" rows="3"><?= e($meta['intro'] ?? '') ?></textarea>
  </div>
  <?php for ($i = 0; $i < 2; $i++): [$text, $url] = $actions[$i] ?? ['', '']; ?>
  <fieldset class="unit-group">
    <legend>Bouton <?= $i + 1 ?><?= $i >= count($actions) ? ' (facultatif)' : '' ?></legend>
    <div class="form-grid form-grid--2">
      <div class="field">
        <label for="action-<?= $i ?>">Texte du bouton</label>
        <input id="action-<?= $i ?>" name="actions[<?= $i ?>][text]" value="<?= e($text) ?>">
      </div>
      <?php $lid = "action-$i-link"; $lname = "actions[$i][link]"; $llabel = 'Le bouton mène vers'; $lbase = ContentController::publicUrl($key); require ROOT . '/templates/admin/partials/link.php'; ?>
    </div>
    <?php if ($text !== ''): ?><p class="field__hint">Videz le texte pour retirer ce bouton.</p><?php endif ?>
  </fieldset>
  <?php endfor ?>
</form>
<div class="form-foot" data-modal-foot>
  <button class="btn btn--primary" type="submit" form="head-form">Enregistrer</button>
  <a class="btn" href="/admin/pages/<?= e($key) ?>" data-modal-close>Annuler</a>
</div>
</div>
