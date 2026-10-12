<?php use App\Core\Csrf; ?>
<div class="login__card">
  <h1>Connexion</h1>
  <p class="login__intro">Accédez à la gestion des contenus, de l'équipe et des lettres d'information.</p>
  <form method="post" action="/admin/connexion" novalidate>
    <?= Csrf::field() ?>
    <?php if ($error): ?>
    <p class="alert alert--error" id="login-error" role="alert">Erreur : <?= e($error) ?></p>
    <?php endif ?>
    <div class="field">
      <label for="username">Identifiant</label>
      <input id="username" name="username" autocomplete="username" required value="<?= e($username ?? '') ?>"<?= $error ? ' aria-invalid="true" aria-describedby="login-error"' : '' ?>>
    </div>
    <div class="field">
      <label for="password">Mot de passe</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required<?= $error ? ' aria-invalid="true" aria-describedby="login-error"' : '' ?>>
    </div>
    <button class="button button--block" type="submit">Se connecter</button>
  </form>
</div>
