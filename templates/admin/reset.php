<?php
use App\Admin\Ui;
use App\Core\Auth;
use App\Core\Csrf;

$min = Auth::MIN_PASSWORD_LENGTH;
$aria = static function (string $name, string $hint = '') use ($errors): string {
    $ids = trim($hint . (isset($errors[$name]) ? " $name-error" : ''));
    return ($ids !== '' ? ' aria-describedby="' . $ids . '"' : '') . (isset($errors[$name]) ? ' aria-invalid="true"' : '');
};
$error = static fn (string $name): string => isset($errors[$name]) ? '<p class="field__error" id="' . $name . '-error">Erreur : ' . e($errors[$name]) . '</p>' : '';
?>
<div class="login__card">
  <?php if ($link === null): ?>
  <h1>Lien expiré</h1>
  <p class="login__intro">Ce lien n'est plus valable : il a déjà servi, ou il a expiré. Demandez-en un nouveau.</p>
  <p><a class="btn btn--primary btn--block" href="/admin/mot-de-passe/oublie">Recevoir un nouveau lien</a></p>
  <p class="login__help"><a href="/admin/connexion">Retour à la connexion</a></p>
  <?php else: ?>
  <h1><?= e($title) ?></h1>
  <p class="login__intro">Compte de <?= e($link['name'] ?: $link['username']) ?>. Une phrase de plusieurs mots est plus sûre et plus facile à retenir.</p>
  <form method="post" action="/admin/mot-de-passe" novalidate>
    <?= Csrf::field() ?>
    <input type="hidden" name="jeton" value="<?= e($token) ?>">
    <input type="hidden" name="username" value="<?= e($link['email'] ?: $link['username']) ?>" autocomplete="username">
    <?php if ($errors !== []): ?>
    <p class="notice notice--error" role="alert"><strong>Erreur :</strong>&nbsp;le mot de passe n'a pas été enregistré. Corrigez les champs signalés.</p>
    <?php endif ?>
    <div class="field">
      <label for="password">Nouveau mot de passe</label>
      <?= Ui::password('password', 'password', 'new-password', ' required minlength="' . $min . '"' . $aria('password', 'password-hint')) ?>
      <p class="field__hint" id="password-hint">Au moins <?= $min ?> caractères.</p>
      <?= $error('password') ?>
    </div>
    <div class="field">
      <label for="password_confirm">Confirmer le mot de passe</label>
      <?= Ui::password('password_confirm', 'password_confirm', 'new-password', ' required' . $aria('password_confirm')) ?>
      <?= $error('password_confirm') ?>
    </div>
    <button class="btn btn--primary btn--block" type="submit"><?= $link['purpose'] === 'invitation' ? 'Activer mon compte' : 'Enregistrer le mot de passe' ?></button>
  </form>
  <?php endif ?>
</div>
