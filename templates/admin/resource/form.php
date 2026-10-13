<?php
use App\Admin\Ui;
use App\Core\Csrf;

$formId = 'fiche';
$main = array_filter($res['fields'], static fn (array $f): bool => $f['type'] !== 'checkbox');
$side = array_filter($res['fields'], static fn (array $f): bool => $f['type'] === 'checkbox');
$recordName = $row ? (string) ($row['name'] ?? $row['title'] ?? '') : '';
$fixed = !empty($res['fixed']);
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/<?= e($key) ?>"><?= Ui::icon('back') ?><?= e($res['label']) ?></a>
    <h1><?= e($recordName !== '' ? $recordName : $title) ?></h1>
  </div>
</header>
<div data-modal-content data-modal-title="<?= e($recordName !== '' ? $recordName : $title) ?>">
<?php if ($errors !== []): ?>
<div class="notice notice--error" role="alert"><?= Ui::icon('alert') ?><p><strong>Erreur :</strong> la fiche n'a pas été enregistrée. Corrigez <?= count($errors) > 1 ? 'les ' . count($errors) . ' champs signalés' : 'le champ signalé' ?>.</p></div>
<?php endif ?>
<div class="form-layout">
  <form class="form-layout__main" id="<?= $formId ?>" method="post" action="/admin/<?= e($key) ?><?= $row ? '/' . (int) $row['id'] : '' ?>" enctype="multipart/form-data" novalidate>
    <?= Csrf::field() ?>
    <section class="card" aria-labelledby="infos-titre">
      <h2 class="card__title" id="infos-titre">Informations</h2>
      <div class="form-grid">
        <?php foreach ($main as $name => $f): ?>
        <?php
          $value = $values[$name] ?? '';
          $error = $errors[$name] ?? null;
          require ROOT . '/templates/admin/partials/field.php';
        ?>
        <?php endforeach ?>
      </div>
    </section>
  </form>
  <aside class="form-layout__side" aria-label="Enregistrement">
    <section class="card" aria-labelledby="publi-titre">
      <h2 class="card__title" id="publi-titre"><?= isset($side['is_published']) ? 'Publication' : 'Enregistrement' ?></h2>
      <?php foreach ($side as $name => $f): ?>
      <?php
        $value = $values[$name] ?? '';
        $error = $errors[$name] ?? null;
        require ROOT . '/templates/admin/partials/field.php';
      ?>
      <?php endforeach ?>
      <div class="button-row">
        <button class="btn btn--primary" type="submit" form="<?= $formId ?>">Enregistrer</button>
        <a class="btn" href="/admin/<?= e($key) ?>" data-modal-close>Annuler</a>
      </div>
    </section>
    <?php if ($row && !$fixed): ?>
    <form class="card danger-zone" method="post" action="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>/suppression" data-confirm="Supprimer « <?= e($recordName) ?> » ? Cette action est définitive.">
      <?= Csrf::field() ?>
      <?php if (isset($side['is_published'])): ?><p>Pour retirer la fiche du site sans la perdre, désactivez plutôt « <?= e($side['is_published']['label']) ?> ».</p><?php endif ?>
      <button class="btn btn--danger" type="submit"><?= Ui::icon('trash') ?>Supprimer définitivement</button>
    </form>
    <?php endif ?>
  </aside>
</div>
</div>
