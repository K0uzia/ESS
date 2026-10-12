<?php use App\Core\Csrf; ?>
<div class="page-title">
  <p class="page-title__kicker"><a href="/admin/newsletters">Newsletters</a></p>
  <h1><?= e($title) ?></h1>
</div>
<form class="panel form-grid" method="post" action="/admin/newsletters/modeles<?= $id ? '/' . (int) $id : '' ?>" novalidate>
  <?= Csrf::field() ?>
  <div class="field"><label for="t-name">Nom du modèle</label><input id="t-name" name="name" required value="<?= e($tpl['name']) ?>"></div>
  <?php if (count($poles) > 1): ?>
  <div class="field"><label for="t-pole">Pôle</label><select id="t-pole" name="pole"><?php foreach ($poles as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $tpl['pole_id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach ?></select></div>
  <?php endif ?>
  <div class="field field--wide"><label for="t-header">Introduction (avant le texte de chaque lettre)</label><textarea class="mono" id="t-header" name="header" rows="5"><?= e($tpl['header']) ?></textarea></div>
  <div class="field field--wide"><label for="t-footer">Signature (après le texte)</label><textarea class="mono" id="t-footer" name="footer" rows="5"><?= e($tpl['footer']) ?></textarea></div>
  <div class="form-actions"><button class="button" type="submit">Enregistrer</button><a class="button button--secondary" href="/admin/newsletters">Annuler</a></div>
</form>
<?php if ($id): ?>
<form method="post" action="/admin/newsletters/modeles/<?= (int) $id ?>/suppression" data-confirm="Supprimer ce modèle ?"><?= Csrf::field() ?><button class="button button--secondary" type="submit">Supprimer le modèle</button></form>
<?php endif ?>
