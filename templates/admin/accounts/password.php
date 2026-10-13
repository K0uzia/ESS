<?php
use App\Admin\Ui;
use App\Core\Auth;
use App\Core\Csrf;

$invalid = static fn (string $n): string => isset($errors[$n]) ? ' aria-invalid="true"' : '';
$error = static fn (string $n): string => isset($errors[$n]) ? '<p class="field__error" id="' . $n . '-error">Erreur : ' . e($errors[$n]) . '</p>' : '';
?>
<header class="page-head">
  <div class="page-head__text">
    <h1><?= $own ? 'Mon mot de passe' : 'Mot de passe de « ' . e($account['username']) . ' »' ?></h1>
    <p class="page-head__sub"><?= $own ? 'Saisissez votre mot de passe actuel, puis le nouveau deux fois.' : 'En tant qu\'administrateur, vous définissez un nouveau mot de passe pour ce compte.' ?></p>
  </div>
</header>
<?php if ($errors !== []): ?>
<div class="notice notice--error" role="alert"><?= Ui::icon('alert') ?><p><strong>Erreur :</strong> le mot de passe n'a pas été changé. Corrigez les champs signalés.</p></div>
<?php endif ?>
<form class="card" method="post" action="/admin/comptes/<?= (int) $account['id'] ?>/mot-de-passe" novalidate>
  <?= Csrf::field() ?>
  <input type="hidden" name="username" value="<?= e($account['username']) ?>" autocomplete="username">
  <div class="form-grid">
    <?php if ($own): ?>
    <div class="field field--wide">
      <label for="current_password">Mot de passe actuel</label>
      <input id="current_password" name="current_password" type="password" autocomplete="current-password" required<?= $invalid('current_password') ?><?= isset($errors['current_password']) ? ' aria-describedby="current_password-error"' : '' ?>>
      <?= $error('current_password') ?>
    </div>
    <?php endif ?>
    <div class="field">
      <label for="password">Nouveau mot de passe</label>
      <input id="password" name="password" type="password" autocomplete="new-password" required minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>" aria-describedby="password-hint<?= isset($errors['password']) ? ' password-error' : '' ?>"<?= $invalid('password') ?>>
      <p class="field__hint" id="password-hint">Au moins <?= Auth::MIN_PASSWORD_LENGTH ?> caractères. Une phrase de plusieurs mots est plus sûre et plus facile à retenir.</p>
      <?= $error('password') ?>
    </div>
    <div class="field">
      <label for="password_confirm">Confirmer le nouveau mot de passe</label>
      <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required<?= $invalid('password_confirm') ?><?= isset($errors['password_confirm']) ? ' aria-describedby="password_confirm-error"' : '' ?>>
      <?= $error('password_confirm') ?>
    </div>
  </div>
  <div class="form-foot">
    <button class="btn btn--primary" type="submit">Changer le mot de passe</button>
    <a class="btn" href="/admin/">Annuler</a>
  </div>
</form>
