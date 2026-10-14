<?php
use App\Core\FormGuard;

$state = $GLOBALS['form_state'] ?? ['errors' => [], 'values' => []];
$errors = isset($state['values']['message']) ? $state['errors'] : [];
$values = isset($state['values']['message']) ? $state['values'] : [];
$sent = isset($_GET['envoye']);
$f = static fn (string $n): string => isset($errors[$n]) ? ' aria-invalid="true"' : '';
$err = static fn (string $n): string => isset($errors[$n]) ? '<p class="field__error" id="c-' . $n . '-error">Erreur : ' . e($errors[$n]) . '</p>' : '';
$desc = static fn (string $n, string $extra = ''): string => trim($extra . (isset($errors[$n]) ? " c-$n-error" : '')) !== '' ? ' aria-describedby="' . trim($extra . (isset($errors[$n]) ? " c-$n-error" : '')) . '"' : '';
?>
<?php if ($sent): ?>
<p class="alert" role="status" id="formulaire-contact" tabindex="-1">Merci, votre message a bien été envoyé. Nous vous répondrons par e-mail.</p>
<?php else: ?>
<form class="form" method="post" action="/contact/" id="formulaire-contact" novalidate>
  <?= FormGuard::fields() ?>
  <?php if ($errors !== []): ?>
  <div class="alert alert--error" role="alert">
    <?php $fields = count(array_diff_key($errors, ['form' => 1])); ?>
    <p>Erreur : le message n'a pas été envoyé.<?= $fields > 0 ? ' Corrigez ' . ($fields > 1 ? 'les champs signalés' : 'le champ signalé') . '.' : '' ?></p>
    <?php if (isset($errors['form'])): ?><p><?= e($errors['form']) ?></p><?php endif ?>
  </div>
  <?php endif ?>
  <p class="field__hint">Tous les champs sont obligatoires, sauf le pôle.</p>
  <div class="field">
    <label for="c-nom">Nom</label>
    <input id="c-nom" name="nom" autocomplete="name" required maxlength="120" value="<?= e($values['nom'] ?? '') ?>"<?= $f('nom') . $desc('nom') ?>>
    <?= $err('nom') ?>
  </div>
  <div class="field">
    <label for="c-email">Adresse e-mail</label>
    <input id="c-email" name="email" type="email" autocomplete="email" required value="<?= e($values['email'] ?? '') ?>"<?= $f('email') . $desc('email') ?>>
    <?= $err('email') ?>
  </div>
  <div class="field">
    <label for="c-pole">Pôle concerné (facultatif)</label>
    <select id="c-pole" name="pole"<?= $f('pole') . $desc('pole') ?>>
      <option value="">Je ne sais pas</option>
      <?php foreach ($poles as $p): ?>
      <option value="<?= e($p['slug']) ?>"<?= ($values['pole'] ?? '') === $p['slug'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
      <?php endforeach ?>
    </select>
    <?= $err('pole') ?>
  </div>
  <div class="field">
    <label for="c-message">Message</label>
    <textarea id="c-message" name="message" rows="7" required maxlength="5000"<?= $f('message') . $desc('message') ?>><?= e($values['message'] ?? '') ?></textarea>
    <?= $err('message') ?>
  </div>
  <p class="field__hint">Vos données servent uniquement à répondre à votre message et sont conservées 12 mois au plus. Voir notre <a href="/confidentialite/">politique de confidentialité</a>.</p>
  <button class="button" type="submit">Envoyer le message</button>
</form>
<?php endif ?>
