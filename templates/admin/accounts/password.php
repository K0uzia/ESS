<?php
use App\Core\Auth;
use App\Core\Csrf;

$invalid = static fn (string $n): string => isset($errors[$n]) ? ' aria-invalid="true"' : '';
$error = static fn (string $n): string => isset($errors[$n]) ? '<p class="field__error" id="' . $n . '-error">Erreur : ' . e($errors[$n]) . '</p>' : '';
?>
<h1>Mot de passe · <?= e($account['username']) ?></h1>
<form class="panel" method="post" action="/admin/comptes/<?= (int) $account['id'] ?>/mot-de-passe" novalidate>
  <?= Csrf::field() ?>
  <input type="hidden" name="username" value="<?= e($account['username']) ?>" autocomplete="username">
  <?php if ($own): ?>
  <div class="field">
    <label for="current_password">Mot de passe actuel</label>
    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required<?= $invalid('current_password') ?><?= isset($errors['current_password']) ? ' aria-describedby="current_password-error"' : '' ?>>
    <?= $error('current_password') ?>
  </div>
  <?php endif ?>
  <div class="field">
    <label for="password">Nouveau mot de passe</label>
    <input id="password" name="password" type="password" autocomplete="new-password" required minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>" aria-describedby="password-hint<?= isset($errors['password']) ? ' password-error' : '' ?>"<?= $invalid('password') ?>>
    <p class="field__hint" id="password-hint">Au moins <?= Auth::MIN_PASSWORD_LENGTH ?> caractères.</p>
    <?= $error('password') ?>
  </div>
  <div class="field">
    <label for="password_confirm">Confirmer le nouveau mot de passe</label>
    <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" required<?= $invalid('password_confirm') ?><?= isset($errors['password_confirm']) ? ' aria-describedby="password_confirm-error"' : '' ?>>
    <?= $error('password_confirm') ?>
  </div>
  <div class="actions">
    <button class="button" type="submit">Changer le mot de passe</button>
  </div>
</form>
