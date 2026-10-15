<?php
use App\Core\FormGuard;

$state = $GLOBALS['form_state'] ?? ['errors' => [], 'values' => []];
$errors = isset($state['values']['poles']) || isset($state['errors']['poles']) ? $state['errors'] : [];
$values = $state['values'];
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<p class="section__intro">Recevez la lettre d'information du ou des pôles de votre choix.</p>
<form class="form" method="post" action="/newsletter/" id="formulaire-newsletter" novalidate>
  <?= FormGuard::fields('newsletter') ?>
  <input type="hidden" name="retour" value="<?= e($current) ?>">
  <?php if (isset($errors['form'])): ?><p class="alert alert--error" role="alert">Erreur : <?= e($errors['form']) ?></p><?php endif ?>
  <fieldset class="field"<?= isset($errors['poles']) ? ' aria-describedby="nl-poles-error"' : '' ?>>
    <legend>Lettres à recevoir</legend>
    <div class="checks">
    <?php foreach ($poles as $p): ?>
    <div class="check">
      <input type="checkbox" id="nl-<?= e($p['slug']) ?>" name="poles[]" value="<?= e($p['slug']) ?>"<?= in_array($p['slug'], $values['poles'] ?? [], true) ? ' checked' : '' ?><?= isset($errors['poles']) ? ' aria-invalid="true"' : '' ?>>
      <label for="nl-<?= e($p['slug']) ?>"><?= e($p['name']) ?></label>
    </div>
    <?php endforeach ?>
    </div>
    <?php if (isset($errors['poles'])): ?><p class="field__error" id="nl-poles-error">Erreur : <?= e($errors['poles']) ?></p><?php endif ?>
  </fieldset>
  <div class="field">
    <label for="nl-email">Adresse e-mail</label>
    <input id="nl-email" name="email" type="email" autocomplete="email" required value="<?= e($values['email'] ?? '') ?>" aria-describedby="nl-email-hint<?= isset($errors['email']) ? ' nl-email-error' : '' ?>"<?= isset($errors['email']) ? ' aria-invalid="true"' : '' ?>>
    <p class="field__hint" id="nl-email-hint">Vous recevrez un e-mail pour confirmer. Désinscription possible dans chaque lettre. Voir notre <a href="/confidentialite/">politique de confidentialité</a>.</p>
    <?php if (isset($errors['email'])): ?><p class="field__error" id="nl-email-error">Erreur : <?= e($errors['email']) ?></p><?php endif ?>
  </div>
  <button class="button" type="submit">S'inscrire</button>
</form>
