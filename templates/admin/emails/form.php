<?php
use App\Admin\Ui;
use App\Core\Csrf;

$action = ['boite' => '/admin/emails/boite', 'contact' => '/admin/emails/contact', 'pole' => '/admin/emails/poles/' . (int) ($pole['id'] ?? 0)][$kind];
$field = static function (string $name, string $hint = '') use ($errors): array {
    $ids = trim(($hint !== '' ? "$name-hint" : '') . (isset($errors[$name]) ? " $name-error" : ''));
    return [
        ($ids !== '' ? ' aria-describedby="' . $ids . '"' : '') . (isset($errors[$name]) ? ' aria-invalid="true"' : ''),
        ($hint !== '' ? '<p class="field__hint" id="' . $name . '-hint">' . e($hint) . '</p>' : '')
            . (isset($errors[$name]) ? '<p class="field__error" id="' . $name . '-error">Erreur : ' . e($errors[$name]) . '</p>' : ''),
    ];
};
$email = static function (string $name, string $label, string $hint) use ($field, $values): string {
    [$aria, $after] = $field($name, $hint);
    return '<div class="field"><label for="' . $name . '">' . e($label) . '</label>'
        . '<input id="' . $name . '" name="' . $name . '" type="email" autocomplete="off" required value="' . e($values[$name] ?? '') . '"' . $aria . '>' . $after . '</div>';
};
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/emails"><?= Ui::icon('back') ?>E-mails</a>
    <h1><?= e($title) ?></h1>
  </div>
</header>
<div class="card" data-modal-content data-modal-title="<?= e($title) ?>">
<?php if ($errors !== []): ?>
<div class="notice notice--error" role="alert"><?= Ui::icon('alert') ?><p><strong>Erreur :</strong> rien n'a été enregistré. Corrigez <?= count($errors) > 1 ? 'les champs signalés' : 'le champ signalé' ?>.</p></div>
<?php endif ?>
<form id="email-form" method="post" action="<?= e($action) ?>" novalidate>
  <?= Csrf::field() ?>
  <?php if ($kind === 'boite'): ?>
  <p>La boîte mail qui envoie tous les e-mails du site. Elle doit exister chez l'hébergeur des e-mails (Hostinger).</p>
  <?= $email('smtp_user', 'Adresse de la boîte mail', 'Par exemple bonjour@ess29.org.') ?>
  <?php $saved = setting('smtp_password') !== ''; [$aria, $after] = $field('smtp_password', $saved ? 'Déjà enregistré. Laissez vide pour le garder.' : 'Gardé chiffré, jamais réaffiché.'); ?>
  <div class="field">
    <label for="smtp_password">Mot de passe de cette boîte</label>
    <?= Ui::password('smtp_password', 'smtp_password', 'new-password', ($saved ? '' : ' required') . $aria) ?>
    <?= $after ?>
  </div>
  <?php elseif ($kind === 'contact'): ?>
  <?= $email('email_contact', 'Adresse qui reçoit les messages', 'C\'est aussi l\'adresse de contact affichée sur le site (pied de page, mentions légales).') ?>
  <?php else: ?>
  <?= $email('newsletter_email', 'Newsletters envoyées depuis', 'Doit être un alias de la boîte d\'envoi, sinon les newsletters ne partent pas.') ?>
  <?= $email('email', 'Adresse du pôle', 'Reçoit les réponses aux newsletters. C\'est aussi l\'adresse du pôle affichée sur le site.') ?>
  <?php endif ?>
</form>
<div class="form-foot" data-modal-foot>
  <button class="btn btn--primary" type="submit" form="email-form">Enregistrer</button>
  <a class="btn" href="/admin/emails" data-modal-close>Annuler</a>
</div>
</div>
