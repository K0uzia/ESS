<?php
use App\Core\FormGuard;

$state = $GLOBALS['form_state'] ?? ['errors' => [], 'values' => []];
$errors = isset($state['values']['lettres']) || isset($state['errors']['lettres']) ? $state['errors'] : [];
$values = $state['values'];
$current = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
?>
<p class="section__intro">Recevez la lettre d'information de votre choix.</p>
<form class="form" method="post" action="/newsletter/" id="formulaire-newsletter" novalidate>
  <?= FormGuard::fields('newsletter') ?>
  <input type="hidden" name="retour" value="<?= e($current) ?>">
  <?php if (isset($errors['form'])): ?><p class="alert alert--error" role="alert">Erreur : <?= e($errors['form']) ?></p><?php endif ?>
  <fieldset class="field"<?= isset($errors['lettres']) ? ' aria-describedby="nl-lettres-error"' : '' ?>>
    <legend>Lettres à recevoir</legend>
    <div class="checks">
    <?php foreach ($lists as $l): ?>
    <div class="check">
      <input type="checkbox" id="nl-<?= e($l['org']) ?>" name="lettres[]" value="<?= e($l['org']) ?>"<?= in_array($l['org'], $values['lettres'] ?? [], true) ? ' checked' : '' ?><?= isset($errors['lettres']) ? ' aria-invalid="true"' : '' ?>>
      <label for="nl-<?= e($l['org']) ?>"><?= e($l['org_name']) ?></label>
    </div>
    <?php endforeach ?>
    </div>
    <?php if (isset($errors['lettres'])): ?><p class="field__error" id="nl-lettres-error">Erreur : <?= e($errors['lettres']) ?></p><?php endif ?>
  </fieldset>
  <div class="field">
    <label for="nl-email">Adresse e-mail</label>
    <input id="nl-email" name="email" type="email" autocomplete="email" required value="<?= e($values['email'] ?? '') ?>" aria-describedby="nl-email-hint<?= isset($errors['email']) ? ' nl-email-error' : '' ?>"<?= isset($errors['email']) ? ' aria-invalid="true"' : '' ?>>
    <p class="field__hint" id="nl-email-hint">Vous recevrez un e-mail pour confirmer. Désinscription possible dans chaque lettre. Voir notre <a href="/confidentialite/">politique de confidentialité</a>.</p>
    <?php if (isset($errors['email'])): ?><p class="field__error" id="nl-email-error">Erreur : <?= e($errors['email']) ?></p><?php endif ?>
  </div>
  <button class="button" type="submit">S'inscrire</button>
</form>
