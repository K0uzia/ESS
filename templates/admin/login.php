<?php use App\Admin\Ui; use App\Core\Csrf; ?>
<div class="login__card">
  <h1>Connexion</h1>
  <p class="login__intro">Accédez à la gestion des contenus, de l'équipe et des lettres d'information.</p>
  <form method="post" action="/admin/connexion" novalidate>
    <?= Csrf::field() ?>
    <?php if ($error): ?>
    <p class="notice notice--error" id="login-error" role="alert"><strong>Erreur :</strong>&nbsp;<?= e($error) ?></p>
    <?php endif ?>
    <div class="field">
      <label for="username">E-mail</label>
      <input id="username" name="username" type="email" autocomplete="username" required value="<?= e($username ?? '') ?>"<?= $error ? ' aria-invalid="true" aria-describedby="login-error"' : '' ?>>
    </div>
    <div class="field">
      <label for="password">Mot de passe</label>
      <?= Ui::password('password', 'password', 'current-password', ' required' . ($error ? ' aria-invalid="true" aria-describedby="login-error"' : '')) ?>
    </div>
    <button class="btn btn--primary btn--block" type="submit">Se connecter</button>
    <p class="login__help"><a href="/admin/mot-de-passe/oublie">Mot de passe oublié ?</a></p>
  </form>
</div>
