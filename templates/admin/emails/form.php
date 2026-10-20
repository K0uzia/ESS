<?php
use App\Admin\Ui;
use App\Core\Csrf;
use App\Site\Orgs;

$action = match ($kind) {
    'sender' => '/admin/emails/boites' . (isset($row) ? '/' . (int) $row['id'] : ''),
    'site' => '/admin/emails/site',
    default => '/admin/emails/organismes/' . e($org['key']),
};
$field = static function (string $name, string $hint = '') use ($errors): array {
    $ids = trim(($hint !== '' ? "$name-hint" : '') . (isset($errors[$name]) ? " $name-error" : ''));
    return [
        ($ids !== '' ? ' aria-describedby="' . $ids . '"' : '') . (isset($errors[$name]) ? ' aria-invalid="true"' : ''),
        ($hint !== '' ? '<p class="field__hint" id="' . $name . '-hint">' . e($hint) . '</p>' : '')
            . (isset($errors[$name]) ? '<p class="field__error" id="' . $name . '-error">Erreur : ' . e($errors[$name]) . '</p>' : ''),
    ];
};
$email = static function (string $name, string $label, string $hint, bool $required = false) use ($field, $values): string {
    [$aria, $after] = $field($name, $hint);
    return '<div class="field"><label for="' . $name . '">' . e($label) . ($required ? ' <span class="req">(obligatoire)</span>' : '') . '</label>'
        . '<input id="' . $name . '" name="' . $name . '" type="email" autocomplete="off"' . ($required ? ' required' : '') . ' value="' . e((string) ($values[$name] ?? '')) . '"' . $aria . '>' . $after . '</div>';
};
// Liste des adresses qui peuvent envoyer : on choisit, on ne tape pas (une adresse non reliée ne pourrait rien envoyer).
$senderSelect = static function (string $name, string $label, string $hint, string $empty) use ($field, $values, $senders): string {
    [$aria, $after] = $field($name, $hint);
    $html = '<div class="field"><label for="' . $name . '">' . e($label) . '</label><select id="' . $name . '" name="' . $name . '"' . $aria . '><option value="">' . e($empty) . '</option>';
    // Adresse déjà enregistrée mais reliée à aucune boîte : gardée dans la liste pour ne pas l'effacer en enregistrant.
    $current = (string) ($values[$name] ?? '');
    if ($current !== '' && !in_array(strtolower($current), array_map('strtolower', array_column($senders, 'email')), true)) {
        $html .= '<option value="' . e($current) . '" selected>' . e($current) . ' (non reliée à une boîte : rien ne part)</option>';
    }
    foreach ($senders as $s) {
        $html .= '<option value="' . e($s['email']) . '"' . (strcasecmp((string) ($values[$name] ?? ''), $s['email']) === 0 ? ' selected' : '') . '>' . e($s['email']) . ($s['box'] !== '' ? ' (alias)' : '') . '</option>';
    }
    return $html . '</select>' . $after . '</div>';
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
<form id="email-form" method="post" action="<?= $action ?>" novalidate>
  <?= Csrf::field() ?>
  <?php if ($kind === 'sender'): ?>
  <?php $boxes = array_filter($senders, static fn (array $s): bool => $s['box'] === '' && strcasecmp($s['email'], (string) ($row['email'] ?? '')) !== 0); ?>
  <?= $email('email', 'Adresse e-mail', 'Par exemple mail@ess29.com ou adherents-paysdebrest@ess29.com.', true) ?>
  <?php [$aria, $after] = $field('kind'); ?>
  <div class="field">
    <label for="kind">Type d'adresse</label>
    <select id="kind" name="kind"<?= $aria ?>>
      <option value="boite"<?= $values['kind'] === 'boite' ? ' selected' : '' ?>>Une boîte mail Hostinger (je connais son mot de passe)</option>
      <option value="alias"<?= $values['kind'] === 'alias' ? ' selected' : '' ?><?= $boxes === [] ? ' disabled' : '' ?>>Un alias d'une autre boîte</option>
    </select>
    <?= $after ?>
  </div>
  <?php $saved = ($row['password'] ?? '') !== ''; [$aria, $after] = $field('password', 'Si c\'est une boîte. ' . ($saved ? 'Déjà enregistré : laissez vide pour le garder.' : 'Gardé chiffré, jamais réaffiché.') . ' À l\'enregistrement, le site se connecte une fois à Hostinger pour le vérifier.'); ?>
  <div class="field">
    <label for="password">Mot de passe de la boîte</label>
    <?= Ui::password('password', 'password', 'new-password', $aria) ?>
    <?= $after ?>
  </div>
  <?php [$aria, $after] = $field('box', 'Si c\'est un alias : la boîte qui l\'envoie. L\'alias doit exister chez Hostinger sur cette boîte.'); ?>
  <div class="field">
    <label for="box">Alias de la boîte</label>
    <select id="box" name="box"<?= $aria ?>>
      <option value="">—</option>
      <?php foreach ($boxes as $b): ?>
      <option value="<?= e($b['email']) ?>"<?= strcasecmp($values['box'], $b['email']) === 0 ? ' selected' : '' ?>><?= e($b['email']) ?></option>
      <?php endforeach ?>
    </select>
    <?= $after ?>
  </div>

  <?php elseif ($kind === 'site'): ?>
  <?php if ($senders === []): ?><div class="notice notice--warning"><?= Ui::icon('alert') ?><p>Ajoutez d'abord une boîte d'envoi dans la partie 1.</p></div><?php endif ?>
  <?= $senderSelect('email_envoi', 'E-mails automatiques envoyés depuis', 'Messages du formulaire de contact transmis à l\'organisme choisi, confirmations d\'inscription aux newsletters.', 'Choisissez') ?>
  <?= $senderSelect('email_comptes', 'Comptes du tableau de bord envoyés depuis', 'Invitations des nouveaux comptes et liens de changement de mot de passe.', 'Choisissez') ?>

  <?php else: ?>
  <?= $email('contact', 'Reçoit le formulaire de contact', 'Les messages des visiteurs qui choisissent ' . $org['name'] . ' arrivent ici. Vide : ' . $org['name'] . ' n\'apparaît pas dans le formulaire.') ?>
  <?= $email('reponse', 'Adresse de réponse', 'Quand un abonné répond à une newsletter de ' . $org['name'] . ', sa réponse arrive ici.') ?>
  <?php if ($org['key'] !== 'tag29'): ?>
  <?= $email('public', 'Adresse affichée sur le site', $org['pole_id'] !== null ? 'Coordonnées du pôle sur le site et dans les newsletters.' : 'Adresse de contact générale : pied de page, mentions légales.', true) ?>
  <?php endif ?>
  <?php foreach (Orgs::AUDIENCES as $a => $label): ?>
  <fieldset class="field">
    <legend>Newsletter · <?= e($label) ?></legend>
    <label class="switch" for="open_<?= $a ?>">
      <input type="checkbox" id="open_<?= $a ?>" name="open_<?= $a ?>" value="1"<?= !empty($values['open_' . $a]) ? ' checked' : '' ?>>
      <span class="switch__track" aria-hidden="true"></span>
      <span class="switch__text"><?= $a === 'inscrits' ? 'Ouverte : proposée à l\'inscription sur le site' : 'Ouverte : liste des adhérents gérée dans le tableau de bord' ?></span>
    </label>
    <?php [$aria, $after] = $field('mode_' . $a); ?>
    <div class="field">
      <label for="mode_<?= $a ?>">Mode d'envoi</label>
      <select id="mode_<?= $a ?>" name="mode_<?= $a ?>"<?= $aria ?>>
        <option value="sympa"<?= ($values['mode_' . $a] ?? 'site') === 'sympa' ? ' selected' : '' ?>>Liste de diffusion Sympa : la lettre part une fois à la liste, Sympa gère les abonnés</option>
        <option value="site"<?= ($values['mode_' . $a] ?? 'site') === 'site' ? ' selected' : '' ?>>Abonnés gérés par le site : une lettre par abonné</option>
      </select>
      <?= $after ?>
    </div>
    <?= $email('sympa_' . $a, 'Adresse de la liste Sympa', 'Seulement en mode Sympa. Par exemple adherents-paysdebrest@ess29.com.') ?>
    <?= $senderSelect('sender_' . $a, 'Envoyée depuis', 'Une des boîtes ou alias du réglage technique. En mode Sympa, cette adresse doit être autorisée à écrire sur la liste, sinon Sympa met la lettre en modération.', 'Pas encore choisie') ?>
  </fieldset>
  <?php endforeach ?>
  <?php endif ?>
</form>
<div class="form-foot" data-modal-foot>
  <button class="btn btn--primary" type="submit" form="email-form">Enregistrer</button>
  <a class="btn" href="/admin/emails" data-modal-close>Annuler</a>
</div>
</div>
