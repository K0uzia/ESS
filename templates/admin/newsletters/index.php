<?php
use App\Admin\Ui;
use App\Core\Csrf;

$labels = ['draft' => 'Brouillon', 'queued' => 'En cours d\'envoi', 'sending' => 'En cours d\'envoi', 'sent' => 'Envoyée'];
$vue = ($_GET['vue'] ?? '') === 'modeles' ? 'modeles' : 'lettres';
// Lettres rangées par pôle : un onglet par pôle visible par l'utilisateur.
$byPole = [];
foreach ($campaigns as $c) {
    $byPole[(int) $c['pole_id']][] = $c;
}
$slugs = array_column($poles, 'slug');
$active = in_array($_GET['onglet'] ?? '', $slugs, true) ? (string) $_GET['onglet'] : ($slugs[0] ?? '');
$multi = count($poles) > 1;
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Newsletters</h1>
    <p class="page-head__sub"><?= $vue === 'modeles' ? 'Introduction et signature réutilisables, communes aux trois pôles.' : 'Chaque pôle a ses lettres et ses abonnés.' ?></p>
  </div>
  <?php if ($vue === 'modeles'): ?>
  <div class="page-head__actions">
    <a class="btn btn--primary" href="/admin/newsletters/modeles/nouveau"><?= Ui::icon('plus') ?>Nouveau modèle</a>
  </div>
  <?php endif ?>
</header>
<?php $tab = $vue; require __DIR__ . '/tabs.php'; ?>

<?php if ($vue === 'modeles'): ?>
<section class="card card--flush" aria-label="Modèles">
  <?php if ($templates === []): ?>
  <div class="empty"><strong>Aucun modèle</strong><p>Un modèle ajoute la même introduction et la même signature à plusieurs lettres, quel que soit le pôle. Il est facultatif.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden">Modèles</caption>
      <thead><tr><th scope="col">Nom</th><th scope="col" class="num">Modifié le</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($templates as $t): ?>
        <tr>
          <th scope="row"><a href="/admin/newsletters/modeles/<?= (int) $t['id'] ?>"><?= e($t['name']) ?></a></th>
          <td data-label="Modifié le" class="num"><?= e(Ui::date($t['updated_at'])) ?></td>
          <td class="actions-col"><a class="btn" href="/admin/newsletters/modeles/<?= (int) $t['id'] ?>"><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> <?= e($t['name']) ?></span></a></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php endif ?>
</section>
<?php else: ?>
<?php if ($pending > 0): ?>
<div class="notice"><?= Ui::icon('send') ?><p>Envoi en cours : <?= (int) $pending ?> e-mail<?= $pending > 1 ? 's partent' : ' part' ?> petit à petit, pour rester dans la limite d'envoi quotidienne. Rien à faire de votre côté.</p></div>
<?php endif ?>
<div class="tabset" data-tabset>
  <?php if ($multi): ?>
  <div class="tabset__list" role="tablist" aria-label="Pôles" hidden data-tabset-list>
    <?php foreach ($poles as $p): ?>
    <button class="tabset__tab" type="button" role="tab" id="tab-<?= e($p['slug']) ?>" aria-controls="panel-<?= e($p['slug']) ?>" aria-selected="<?= $p['slug'] === $active ? 'true' : 'false' ?>" tabindex="<?= $p['slug'] === $active ? '0' : '-1' ?>" data-tab="<?= e($p['slug']) ?>">
      <span class="tabset__dot pole-dot--<?= e(preg_replace('/^pays-de-/', '', $p['slug'])) ?>" aria-hidden="true"></span>
      <?= e(Ui::poleShort($p['name'])) ?> <span class="tabset__count"><?= count($byPole[(int) $p['id']] ?? []) ?></span>
    </button>
    <?php endforeach ?>
  </div>
  <?php endif ?>
  <?php foreach ($poles as $p): $list = $byPole[(int) $p['id']] ?? []; $active_n = (int) ($counts[$p['id']]['active'] ?? 0); $new = '/admin/newsletters/campagnes/nouvelle?pole=' . (int) $p['id']; ?>
  <section class="tabset__panel" id="panel-<?= e($p['slug']) ?>"<?= $multi ? ' role="tabpanel" aria-labelledby="tab-' . e($p['slug']) . '"' : '' ?> data-tab-panel="<?= e($p['slug']) ?>">
    <h2 class="tabset__heading"><?= e($p['name']) ?></h2>
    <div class="card card--flush group-card">
      <div class="group-card__head">
        <p class="group-card__title"><?= $active_n ?> abonné<?= $active_n > 1 ? 's' : '' ?> actif<?= $active_n > 1 ? 's' : '' ?> · envoi depuis <?= e(App\Newsletter\Sender::sender($p)) ?></p>
        <a class="btn btn--primary" href="<?= $new ?>"><?= Ui::icon('plus') ?>Nouvelle lettre<span class="visually-hidden"> : <?= e($p['name']) ?></span></a>
      </div>
      <?php if ($list === []): ?>
      <p class="group-card__empty">Aucune lettre pour ce pôle pour l'instant.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="table table--stack">
          <caption class="visually-hidden">Lettres du <?= e($p['name']) ?></caption>
          <thead><tr><th scope="col">Objet</th><th scope="col">Statut</th><th scope="col" class="num">Envoyés</th><th scope="col" class="num">Créée le</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
          <tbody>
            <?php foreach ($list as $c): $st = $c['status'] === 'queued' ? 'sending' : $c['status']; ?>
            <tr>
              <th scope="row"><a href="/admin/newsletters/campagnes/<?= (int) $c['id'] ?>"><?= e($c['subject']) ?></a></th>
              <td data-label="Statut"><span class="badge<?= $st === 'sent' ? ' badge--on' : ($st === 'draft' ? ' badge--off' : ' badge--warn') ?>"><?= e($labels[$c['status']]) ?></span></td>
              <td data-label="Envoyés" class="num"><?php if ($st === 'sending' && $c['total']): ?><progress class="progress" max="<?= (int) $c['total'] ?>" value="<?= (int) $c['sent'] ?>" aria-hidden="true"></progress> <?php endif ?><?= $c['total'] ? (int) $c['sent'] . ' / ' . (int) $c['total'] : '—' ?></td>
              <td data-label="Créée le" class="num"><?= e(Ui::date($c['created_at'])) ?></td>
              <td class="actions-col"><a class="btn" href="/admin/newsletters/campagnes/<?= (int) $c['id'] ?>"><?= $c['status'] === 'draft' ? Ui::icon('edit') . 'Continuer' : Ui::icon('eye') . 'Voir' ?><span class="visually-hidden"> <?= e($c['subject']) ?></span></a></td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php endif ?>
    </div>
  </section>
  <?php endforeach ?>
</div>
<?php endif ?>
