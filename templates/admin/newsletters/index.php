<?php
use App\Admin\Ui;
use App\Core\Csrf;

$labels = ['draft' => 'Brouillon', 'queued' => 'En cours d\'envoi', 'sending' => 'En cours d\'envoi', 'sent' => 'Envoyée'];
$slug = static fn (string $pole): string => strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', Ui::poleShort($pole)));
$vue = ($_GET['vue'] ?? '') === 'modeles' ? 'modeles' : 'lettres';
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Newsletters</h1>
  </div>
  <div class="page-head__actions">
    <?php if ($vue === 'modeles'): ?>
    <a class="btn btn--primary" href="/admin/newsletters/modeles/nouveau"><?= Ui::icon('plus') ?>Nouveau modèle</a>
    <?php else: ?>
    <a class="btn btn--primary" href="/admin/newsletters/campagnes/nouvelle"><?= Ui::icon('plus') ?>Nouvelle lettre</a>
    <?php endif ?>
  </div>
</header>
<?php $tab = $vue; require __DIR__ . '/tabs.php'; ?>

<?php if ($vue === 'modeles'): ?>
<section class="card card--flush" aria-label="Modèles">
  <?php if ($templates === []): ?>
  <div class="empty"><strong>Aucun modèle</strong><p>Un modèle ajoute la même introduction et la même signature à plusieurs lettres. Il est facultatif.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden">Modèles</caption>
      <thead><tr><th scope="col">Nom</th><th scope="col">Pôle</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($templates as $t): ?>
        <tr>
          <th scope="row"><a href="/admin/newsletters/modeles/<?= (int) $t['id'] ?>"><?= e($t['name']) ?></a></th>
          <td data-label="Pôle"><?= Ui::poleBadge($t['pole']) ?></td>
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
<div class="notice"><?= Ui::icon('send') ?><p>Envoi en cours : <?= (int) $pending ?> e-mail<?= $pending > 1 ? 's' : '' ?> en attente, envoyés par lots de <?= (int) $quota[2] ?> toutes les 10 minutes (<?= (int) $quota[0] ?> / <?= (int) $quota[1] ?> sur 24 h).</p>
  <form method="post" action="/admin/newsletters/lot"><?= Csrf::field() ?><button class="btn" type="submit">Envoyer un lot maintenant</button></form>
</div>
<?php endif ?>
<section class="card card--flush" data-list aria-label="Lettres">
  <?php if ($campaigns === []): ?>
  <div class="empty"><strong>Aucune lettre pour l'instant</strong><p>Rédigez une lettre, vérifiez-la, puis envoyez-la aux abonnés d'un pôle.</p><a class="btn btn--primary" href="/admin/newsletters/campagnes/nouvelle"><?= Ui::icon('plus') ?>Nouvelle lettre</a></div>
  <?php else: ?>
  <?php
    $filters = ['statut' => ['Statut', ['draft' => 'Brouillon', 'sending' => 'En cours d\'envoi', 'sent' => 'Envoyée']]];
    if (count($poles) > 1) { $filters['pole'] = ['Pôle', array_combine(array_map(static fn ($p) => $slug($p['name']), $poles), array_map(static fn ($p) => Ui::poleShort($p['name']), $poles))]; }
    $searchLabel = 'Rechercher'; $countWords = ['lettre', 'lettres']; $total = count($campaigns);
    require ROOT . '/templates/admin/partials/filters.php';
  ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden">Lettres</caption>
      <thead><tr><th scope="col">Objet</th><th scope="col">Pôle</th><th scope="col">Statut</th><th scope="col" class="num">Envoyés</th><th scope="col" class="num">Créée le</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($campaigns as $c): $st = $c['status'] === 'queued' ? 'sending' : $c['status']; ?>
        <tr data-f-statut="<?= e($st) ?>" data-f-pole="<?= e($slug($c['pole'])) ?>">
          <th scope="row"><a href="/admin/newsletters/campagnes/<?= (int) $c['id'] ?>"><?= e($c['subject']) ?></a></th>
          <td data-label="Pôle"><?= Ui::poleBadge($c['pole']) ?></td>
          <td data-label="Statut"><span class="badge<?= $st === 'sent' ? ' badge--on' : ($st === 'draft' ? ' badge--off' : ' badge--warn') ?>"><?= e($labels[$c['status']]) ?></span></td>
          <td data-label="Envoyés" class="num"><?= $c['total'] ? (int) $c['sent'] . ' / ' . (int) $c['total'] : '—' ?></td>
          <td data-label="Créée le" class="num"><?= e(Ui::date($c['created_at'])) ?></td>
          <td class="actions-col"><a class="btn" href="/admin/newsletters/campagnes/<?= (int) $c['id'] ?>"><?= $c['status'] === 'draft' ? Ui::icon('edit') . 'Continuer' : Ui::icon('eye') . 'Voir' ?><span class="visually-hidden"> <?= e($c['subject']) ?></span></a></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="empty" data-filter-empty hidden><strong>Aucun résultat</strong><p>Aucune lettre ne correspond à la recherche ou aux filtres.</p><button class="btn" type="button" data-filter-reset>Effacer les filtres</button></div>
  <?php endif ?>
</section>
<?php endif ?>
