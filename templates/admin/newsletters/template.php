<?php use App\Admin\Ui; use App\Core\Csrf; ?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/newsletters?vue=modeles"><?= Ui::icon('back') ?>Modèles</a>
    <h1><?= e($id ? $tpl['name'] : $title) ?></h1>
    <p class="page-head__sub">Introduction et signature ajoutées aux lettres qui utilisent ce modèle.</p>
  </div>
</header>
<form class="card" method="post" action="/admin/newsletters/modeles<?= $id ? '/' . (int) $id : '' ?>" novalidate>
  <?= Csrf::field() ?>
  <div class="form-grid">
    <div class="field"><label for="t-name">Nom du modèle <span class="req">(obligatoire)</span></label><input id="t-name" name="name" required value="<?= e($tpl['name']) ?>"></div>
    <?php if (count($poles) > 1): ?>
    <div class="field"><label for="t-pole">Pôle</label><select id="t-pole" name="pole"><?php foreach ($poles as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $tpl['pole_id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach ?></select></div>
    <?php endif ?>
    <div class="field field--wide"><label for="t-header">Introduction (avant le texte)</label><textarea id="t-header" name="header" rows="4"><?= e($tpl['header']) ?></textarea></div>
    <div class="field field--wide"><label for="t-footer">Signature (après le texte)</label><textarea id="t-footer" name="footer" rows="4"><?= e($tpl['footer']) ?></textarea></div>
  </div>
  <div class="form-foot">
    <button class="btn btn--primary" type="submit">Enregistrer</button>
    <a class="btn" href="/admin/newsletters?vue=modeles">Annuler</a>
    <?php if ($id): ?><button class="btn btn--danger form-foot__end" type="submit" form="supprimer-modele"><?= Ui::icon('trash') ?>Supprimer le modèle</button><?php endif ?>
  </div>
</form>
<?php if ($id): ?>
<form id="supprimer-modele" method="post" action="/admin/newsletters/modeles/<?= (int) $id ?>/suppression" hidden data-confirm="Supprimer ce modèle ?"><?= Csrf::field() ?></form>
<?php endif ?>
