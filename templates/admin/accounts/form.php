<?php
use App\Core\Auth;
use App\Core\Csrf;

$field = static function (string $name) use ($errors): string {
    return isset($errors[$name]) ? ' aria-invalid="true" aria-describedby="' . $name . '-error"' : '';
};
$error = static function (string $name) use ($errors): string {
    return isset($errors[$name]) ? '<p class="field__error" id="' . $name . '-error">Erreur : ' . e($errors[$name]) . '</p>' : '';
};
?>
<h1><?= e($title) ?></h1>
<form class="panel" method="post" action="/admin/comptes<?= $account ? '/' . (int) $account['id'] : '' ?>" novalidate>
  <?= Csrf::field() ?>
  <div class="field">
    <label for="username">Identifiant</label>
    <input id="username" name="username" autocomplete="off" required value="<?= e($values['username']) ?>"<?= $field('username') ?>>
    <?= $error('username') ?>
  </div>
  <div class="field">
    <label for="email">E-mail (facultatif)</label>
    <input id="email" name="email" type="email" autocomplete="off" value="<?= e($values['email'] ?? '') ?>"<?= $field('email') ?>>
    <?= $error('email') ?>
  </div>
  <div class="field">
    <label for="role">Rôle</label>
    <select id="role" name="role" aria-describedby="role-hint"<?= isset($errors['role']) ? ' aria-invalid="true"' : '' ?>>
      <option value="admin"<?= $values['role'] === 'admin' ? ' selected' : '' ?>>Administrateur (tout le site, comptes)</option>
      <option value="editor"<?= $values['role'] === 'editor' ? ' selected' : '' ?>>Éditeur (contenus de son pôle)</option>
    </select>
    <p class="field__hint" id="role-hint">Un éditeur doit être rattaché à un pôle.</p>
    <?= $error('role') ?>
  </div>
  <div class="field">
    <label for="pole_id">Pôle</label>
    <select id="pole_id" name="pole_id"<?= $field('pole_id') ?>>
      <option value="">Aucun</option>
      <?php foreach ($poles as $p): ?>
      <option value="<?= (int) $p['id'] ?>"<?= (int) ($values['pole_id'] ?? 0) === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
      <?php endforeach ?>
    </select>
    <?= $error('pole_id') ?>
  </div>
  <?php if ($account === null): ?>
  <div class="field">
    <label for="password">Mot de passe</label>
    <input id="password" name="password" type="password" autocomplete="new-password" required minlength="<?= Auth::MIN_PASSWORD_LENGTH ?>" aria-describedby="password-hint<?= isset($errors['password']) ? ' password-error' : '' ?>"<?= isset($errors['password']) ? ' aria-invalid="true"' : '' ?>>
    <p class="field__hint" id="password-hint">Au moins <?= Auth::MIN_PASSWORD_LENGTH ?> caractères.</p>
    <?= $error('password') ?>
  </div>
  <?php endif ?>
  <div class="actions">
    <button class="button" type="submit">Enregistrer</button>
    <a href="/admin/comptes">Annuler</a>
  </div>
</form>
