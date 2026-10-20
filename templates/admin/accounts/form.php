<?php
use App\Admin\Ui;
use App\Core\Csrf;

$aria = static function (string $name, string $hint = '') use ($errors): string {
    $ids = trim($hint . (isset($errors[$name]) ? " $name-error" : ''));
    return ($ids !== '' ? ' aria-describedby="' . $ids . '"' : '') . (isset($errors[$name]) ? ' aria-invalid="true"' : '');
};
// Rôle, puis organisme d'un éditeur (ESS29, TAg29 ou un pôle) ; vide par défaut, grisé pour un administrateur.
$role = ($values['role'] ?? 'admin') === 'editor' ? 'editor' : 'admin';
$organisme = $values['organisme'] ?? ($role === 'editor' ? (!empty($values['pole_id']) ? 'pole:' . (int) $values['pole_id'] : (string) ($values['org'] ?? '')) : '');
$error = static fn (string $name): string => isset($errors[$name]) ? '<p class="field__error" id="' . $name . '-error">Erreur : ' . e($errors[$name]) . '</p>' : '';
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/comptes"><?= Ui::icon('back') ?>Comptes</a>
    <h1><?= $account ? e($account['name'] ?: $account['username']) . ($own ? ' (vous)' : '') : 'Nouveau compte' ?></h1>
  </div>
</header>
<div data-modal-content data-modal-title="<?= $account ? e($account['name'] ?: $account['username']) : 'Nouveau compte' ?>">
<?php if ($errors !== []): ?>
<div class="notice notice--error" role="alert"><?= Ui::icon('alert') ?><p><strong>Erreur :</strong> rien n'a été enregistré. Corrigez les champs signalés.</p></div>
<?php endif ?>
<form class="card" method="post" action="/admin/comptes<?= $account ? '/' . (int) $account['id'] : '' ?>" novalidate>
  <?= Csrf::field() ?>
  <h2 class="card__title">La personne</h2>
  <div class="form-grid form-grid--2">
    <div class="field">
      <label for="name">Prénom et nom <span class="req">(obligatoire)</span></label>
      <input id="name" name="name" autocomplete="off" required maxlength="80" value="<?= e($values['name'] ?? '') ?>"<?= $aria('name') ?>>
      <?= $error('name') ?>
    </div>
    <div class="field">
      <label for="email">E-mail <span class="req">(obligatoire)</span></label>
      <input id="email" name="email" type="email" autocomplete="off" required value="<?= e($values['email'] ?? '') ?>"<?= $aria('email', 'email-hint') ?>>
      <p class="field__hint" id="email-hint"><?= $account ? 'Sert à se connecter et à recevoir les liens de changement de mot de passe.' : 'Une invitation y est envoyée : la personne choisit elle-même son mot de passe, puis se connecte avec cette adresse.' ?></p>
      <?= $error('email') ?>
    </div>
    <div class="field">
      <label for="role">Rôle</label>
      <select id="role" name="role" data-role-select<?= $aria('role', 'role-hint') ?>>
        <option value="admin"<?= $role === 'admin' ? ' selected' : '' ?>>Administrateur</option>
        <option value="editor"<?= $role === 'editor' ? ' selected' : '' ?>>Éditeur</option>
      </select>
      <p class="field__hint" id="role-hint">Administrateur : tout le site, les comptes et les e-mails. Éditeur : le contenu de son organisme, plus les partenaires, la médiathèque et les modèles de newsletters.</p>
      <?= $error('role') ?>
    </div>
    <div class="field">
      <label for="organisme">Organisme de l'éditeur</label>
      <select id="organisme" name="organisme" data-role-org<?= $role === 'admin' ? ' disabled' : '' ?><?= $aria('organisme', 'organisme-hint') ?>>
        <option value="">Choisissez</option>
        <option value="ess29"<?= $organisme === 'ess29' ? ' selected' : '' ?>>ESS29</option>
        <option value="tag29"<?= $organisme === 'tag29' ? ' selected' : '' ?>>TAg29</option>
        <?php foreach ($poles as $p): ?>
        <option value="pole:<?= (int) $p['id'] ?>"<?= $organisme === 'pole:' . (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
        <?php endforeach ?>
      </select>
      <p class="field__hint" id="organisme-hint">Seulement pour un éditeur. ESS29 : pages du groupement, équipe ESS29, offres et documents communs. TAg29 : page et équipe TAg29. Pôle : fiches, page et newsletters du pôle.</p>
      <?= $error('organisme') ?>
    </div>
  </div>

  <?php if ($account): ?>
  <h2 class="card__title form-section" id="mot-de-passe">Mot de passe</h2>
  <?php if (empty($account['email'])): ?>
  <p class="card__intro">Ajoutez une adresse e-mail et enregistrez : un lien de changement de mot de passe pourra ensuite y être envoyé.</p>
  <?php else: ?>
  <p class="card__intro">Le mot de passe n'est jamais saisi ici : <?= $own ? 'vous recevez' : 'la personne reçoit' ?> un lien à <?= e($account['email']) ?> pour choisir le nouveau. L'actuel reste valable en attendant.</p>
  <p><button class="btn" type="submit" form="lien-mot-de-passe"><?= Ui::icon('send') ?><?= $account['last_login_at'] === null ? 'Renvoyer l\'invitation' : 'Envoyer le lien de changement' ?></button></p>
  <?php endif ?>
  <?php endif ?>
  <div class="form-foot">
    <button class="btn btn--primary" type="submit"><?= $account ? 'Enregistrer' : 'Créer et envoyer l\'invitation' ?></button>
    <a class="btn" href="/admin/comptes" data-modal-close>Annuler</a>
  </div>
</form>
<?php if ($account && !empty($account['email'])): ?>
<form id="lien-mot-de-passe" method="post" action="/admin/comptes/<?= (int) $account['id'] ?>/mot-de-passe" hidden><?= Csrf::field() ?></form>
<?php endif ?>
</div>
