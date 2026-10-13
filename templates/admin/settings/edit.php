<?php
use App\Admin\SettingsController;
use App\Admin\Ui;
use App\Core\Csrf;

$poles = [];
$lockedPole = null;
$formId = 'identite';
$groups = [
    'Identité légale' => ['Mentions légales et données pour Google.', ['raison_sociale', 'siret', 'directeur_publication']],
    'Contact et adresses' => ['E-mail générique (destinataire par défaut du formulaire) et adresses publiques.', ['email_contact', 'gogocarto_url']],
    'Hébergeur' => ['Obligatoire dans les mentions légales (LCEN).', ['hebergeur']],
];
$rest = array_diff(array_keys(SettingsController::FIELDS), ...array_column($groups, 1));
if ($rest !== []) {
    $groups['Autres réglages'] = ['', array_values($rest)];
}
$todo = count(array_filter($values, static fn ($v): bool => is_string($v) && str_contains($v, '__À_REMPLIR__')));
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Identité du site</h1>
    <p class="page-head__sub">Repris dans le pied de page et les mentions légales. Les adresses des pôles se modifient dans <a href="/admin/poles">Coordonnées des pôles</a>.</p>
  </div>
</header>
<?php if ($errors !== []): ?>
<div class="notice notice--error" role="alert"><?= Ui::icon('alert') ?><p><strong>Erreur :</strong> rien n'a été enregistré. Corrigez les champs signalés.</p></div>
<?php elseif ($todo > 0): ?>
<div class="notice notice--warning"><?= Ui::icon('alert') ?><p><?= $todo ?> champ<?= $todo > 1 ? 's contiennent' : ' contient' ?> encore <code>__À_REMPLIR__</code>, affiché tel quel sur le site : complétez-le<?= $todo > 1 ? 's' : '' ?> avant la mise en ligne.</p></div>
<?php endif ?>
<div class="form-layout">
  <form class="form-layout__main" id="<?= $formId ?>" method="post" action="/admin/identite" novalidate>
    <?= Csrf::field() ?>
    <?php foreach ($groups as $group => [$intro, $names]): $gid = 'g-' . substr(md5($group), 0, 6); ?>
    <section class="card" aria-labelledby="<?= $gid ?>">
      <h2 class="card__title" id="<?= $gid ?>"><?= e($group) ?></h2>
      <div class="form-grid">
        <?php foreach ($names as $name): $f = SettingsController::FIELDS[$name]; $value = $values[$name] ?? ''; $error = $errors[$name] ?? null; require ROOT . '/templates/admin/partials/field.php'; ?>
        <?php endforeach ?>
      </div>
    </section>
    <?php endforeach ?>
  </form>
  <aside class="form-layout__side" aria-label="Enregistrement">
    <section class="card">
      <h2 class="card__title">Enregistrement</h2>
      <p class="muted">Visible sur le site dès l'enregistrement.</p>
      <button class="btn btn--primary" type="submit" form="<?= $formId ?>">Enregistrer</button>
    </section>
  </aside>
</div>
