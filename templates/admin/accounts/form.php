<?php
use App\Admin\Ui;
use App\Core\Auth;
use App\Core\Csrf;

$aria = static function (string $name, string $hint = '') use ($errors): string {
    $ids = trim($hint . (isset($errors[$name]) ? " $name-error" : ''));
    return ($ids !== '' ? ' aria-describedby="' . $ids . '"' : '') . (isset($errors[$name]) ? ' aria-invalid="true"' : '');
};
$error = static fn (string $name): string => isset($errors[$name]) ? '<p class="field__error" id="' . $name . '-error">Erreur : ' . e($errors[$name]) . '</p>' : '';
$min = Auth::MIN_PASSWORD_LENGTH;
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/comptes"><?= Ui::icon('back') ?>Comptes</a>
    <h1><?= $account ? e($account['username']) . ($own ? ' (vous)' : '') : 'Nouveau compte' ?></h1>
  </div>
</header>
<div data-modal-content data-modal-title="<?= $account ? e($account['username']) : 'Nouveau compte' ?>">
<?php if ($errors !== []): ?>
<div class="notice notice--error" role="alert"><?= Ui::icon('alert') ?><p><strong>Erreur :</strong> rien n'a été enregistré. Corrigez les champs signalés.</p></div>
<?php endif ?>
<form class="card" method="post" action="/admin/comptes<?= $account ? '/' . (int) $account['id'] : '' ?>" novalidate>
  <?= Csrf::field() ?>
  <h2 class="card__title">Identité et accès</h2>
  <div class="form-grid form-grid--2">
    <div class="field">
      <label for="username">Identifiant de connexion <span class="req">(obligatoire)</span></label>
      <input id="username" name="username" autocomplete="username" required value="<?= e($values['username']) ?>"<?= $aria('username') ?>>
      <?= $error('username') ?>
    </div>
    <div class="field">
      <label for="email">E-mail</label>
      <input id="email" name="email" type="email" autocomplete="email" value="<?= e($values['email'] ?? '') ?>"<?= $aria('email') ?>>
      <?= $error('email') ?>
    </div>
    <div class="field">
      <label for="role">Rôle</label>
      <select id="role" name="role"<?= $aria('role', 'role-hint') ?>>
        <option value="admin"<?= $values['role'] === 'admin' ? ' selected' : '' ?>>Administrateur : tout le site</option>
        <option value="editor"<?= $values['role'] === 'editor' ? ' selected' : '' ?>>Éditeur : son pôle seulement</option>
      </select>
      <p class="field__hint" id="role-hint">Un éditeur doit être rattaché à un pôle.</p>
      <?= $error('role') ?>
    </div>
    <div class="field">
      <label for="pole_id">Pôle</label>
      <select id="pole_id" name="pole_id"<?= $aria('pole_id') ?>>
        <option value="">Aucun</option>
        <?php foreach ($poles as $p): ?>
        <option value="<?= (int) $p['id'] ?>"<?= (int) ($values['pole_id'] ?? 0) === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach ?>
      </select>
      <?= $error('pole_id') ?>
    </div>
  </div>

  <h2 class="card__title form-section" id="mot-de-passe"><?= $account ? 'Changer le mot de passe' : 'Mot de passe' ?></h2>
  <?php if ($account): ?><p class="card__intro">Laissez vide pour garder le mot de passe actuel.</p><?php endif ?>
  <div class="form-grid form-grid--2">
    <?php if ($own): ?>
    <div class="field field--alone">
      <label for="current_password">Mot de passe actuel</label>
      <input id="current_password" name="current_password" type="password" autocomplete="current-password"<?= $aria('current_password') ?>>
      <?= $error('current_password') ?>
    </div>
    <?php endif ?>
    <div class="field">
      <label for="password"><?= $account ? 'Nouveau mot de passe' : 'Mot de passe' ?><?= $account ? '' : ' <span class="req">(obligatoire)</span>' ?></label>
      <input id="password" name="password" type="password" autocomplete="new-password" minlength="<?= $min ?>"<?= $account ? '' : ' required' ?><?= $aria('password', 'password-hint') ?>>
      <p class="field__hint" id="password-hint">Au moins <?= $min ?> caractères.</p>
      <?= $error('password') ?>
    </div>
    <?php if ($account): ?>
    <div class="field">
      <label for="password_confirm">Confirmer le nouveau mot de passe</label>
      <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password"<?= $aria('password_confirm') ?>>
      <?= $error('password_confirm') ?>
    </div>
    <?php endif ?>
  </div>
  <div class="form-foot">
    <button class="btn btn--primary" type="submit">Enregistrer</button>
    <a class="btn" href="/admin/comptes" data-modal-close>Annuler</a>
  </div>
</form>
</div>
