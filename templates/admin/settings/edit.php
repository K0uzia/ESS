<?php
use App\Admin\SettingsController;
use App\Core\Csrf;

$poles = [];
$lockedPole = null;
?>
<div class="page-title">
  <p class="page-title__kicker">Administration</p>
  <h1>Identité du site</h1>
</div>
<p class="lead">Ces informations alimentent le pied de page, les mentions légales, les données pour Google et le fichier llms.txt. Un champ marqué <code>__À_REMPLIR__</code> s'affiche tel quel sur le site : complétez-le avant la mise en ligne.</p>
<?php if ($errors !== []): ?><p class="alert alert--error" role="alert">Erreur : rien n'a été enregistré. Corrigez les champs signalés.</p><?php endif ?>
<form class="panel form-grid" method="post" action="/admin/identite" novalidate>
  <?= Csrf::field() ?>
  <?php foreach (SettingsController::FIELDS as $name => $f): ?>
  <?php $value = $values[$name] ?? ''; $error = $errors[$name] ?? null; require ROOT . '/templates/admin/partials/field.php'; ?>
  <?php endforeach ?>
  <div class="form-actions"><button class="button" type="submit">Enregistrer</button></div>
</form>
