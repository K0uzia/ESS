<?php use App\Core\Csrf; ?>
<div class="login__card">
  <h1>Mot de passe oublié</h1>
  <?php if ($sent): ?>
  <p class="notice notice--success" role="status">Si un compte correspond et possède une adresse e-mail, un lien pour choisir un nouveau mot de passe vient d'y être envoyé. Il est valable 2 heures.</p>
  <p><a class="btn btn--block" href="/admin/connexion">Retour à la connexion</a></p>
  <?php else: ?>
  <p class="login__intro">Indiquez votre adresse e-mail : vous recevrez un lien pour choisir un nouveau mot de passe.</p>
  <form method="post" action="/admin/mot-de-passe/oublie" novalidate>
    <?= Csrf::field() ?>
    <?php if ($error): ?>
    <p class="notice notice--error" id="login-error" role="alert"><strong>Erreur :</strong>&nbsp;<?= e($error) ?></p>
    <?php endif ?>
    <div class="field">
      <label for="login">E-mail</label>
      <input id="login" name="login" type="email" autocomplete="username" required value="<?= e($login) ?>"<?= $error ? ' aria-invalid="true" aria-describedby="login-error"' : '' ?>>
    </div>
    <button class="btn btn--primary btn--block" type="submit">Recevoir le lien</button>
    <p class="login__help"><a href="/admin/connexion">Retour à la connexion</a></p>
  </form>
  <?php endif ?>
</div>
