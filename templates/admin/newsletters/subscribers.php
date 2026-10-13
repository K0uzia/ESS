<?php
use App\Admin\Ui;
use App\Core\Csrf;

$labels = ['active' => 'Actif', 'pending' => 'En attente', 'unsubscribed' => 'Désinscrit'];
$slug = static fn (string $pole): string => strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', Ui::poleShort($pole)));
$n = count($rows);
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Newsletters</h1>
  </div>
  <div class="page-head__actions">
    <a class="btn" href="/admin/newsletters/abonnes/export"><?= Ui::icon('download') ?>Exporter en CSV</a>
  </div>
</header>
<?php $tab = 'abonnes'; require __DIR__ . '/tabs.php'; ?>

<section class="card card--flush" data-list aria-label="Abonnés">
  <?php if ($rows === []): ?>
  <div class="empty"><strong>Aucun abonné pour l'instant</strong><p>Les inscriptions du site apparaîtront ici. Vous pouvez aussi ajouter une adresse ou importer un fichier ci-dessous.</p></div>
  <?php else: ?>
  <?php
    $filters = ['etat' => ['Statut', ['active' => 'Actif', 'pending' => 'En attente', 'unsubscribed' => 'Désinscrit']]];
    if (count($poles) > 1) { $filters = ['pole' => ['Pôle', array_combine(array_map(static fn ($p) => $slug($p['name']), $poles), array_map(static fn ($p) => Ui::poleShort($p['name']), $poles))]] + $filters; }
    $searchLabel = 'Rechercher une adresse'; $countWords = ['abonné', 'abonnés']; $total = $n;
    require ROOT . '/templates/admin/partials/filters.php';
  ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden">Abonnés</caption>
      <thead><tr><th scope="col">Adresse</th><th scope="col">Pôle</th><th scope="col">Statut</th><th scope="col">Origine</th><th scope="col" class="num">Depuis le</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr data-f-pole="<?= e($slug($r['pole'])) ?>" data-f-etat="<?= e($r['status']) ?>">
          <th scope="row"><?= e($r['email']) ?></th>
          <td data-label="Pôle"><?= Ui::poleBadge($r['pole']) ?></td>
          <td data-label="Statut"><span class="badge<?= $r['status'] === 'active' ? ' badge--on' : ($r['status'] === 'unsubscribed' ? ' badge--off' : ' badge--warn') ?>"><?= $labels[$r['status']] ?></span></td>
          <td data-label="Origine" class="excerpt"><?= e($r['source']) ?></td>
          <td data-label="Depuis le" class="num"><?= e(Ui::date($r['consent_at'])) ?></td>
          <td class="actions-col">
            <form method="post" action="/admin/newsletters/abonnes/<?= (int) $r['id'] ?>/suppression" data-confirm="Supprimer définitivement <?= e($r['email']) ?> ?">
              <?= Csrf::field() ?><button class="btn btn--danger" type="submit"><?= Ui::icon('trash') ?>Supprimer<span class="visually-hidden"> <?= e($r['email']) ?></span></button>
            </form>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="empty" data-filter-empty hidden><strong>Aucun résultat</strong><p>Aucun abonné ne correspond à la recherche ou aux filtres.</p><button class="btn" type="button" data-filter-reset>Effacer les filtres</button></div>
  <?php endif ?>
</section>

<div class="grid">
  <details class="card advanced-card">
    <summary class="card__title"><?= Ui::icon('plus') ?>Ajouter un abonné</summary>
    <form method="post" action="/admin/newsletters/abonnes" novalidate>
      <?= Csrf::field() ?>
      <p class="muted">Seulement pour une personne qui a donné son accord (bulletin papier, salon…).</p>
      <div class="field"><label for="a-email">Adresse e-mail <span class="req">(obligatoire)</span></label><input id="a-email" name="email" type="email" required autocomplete="off"></div>
      <?php if (count($poles) > 1): ?>
      <div class="field"><label for="a-pole">Lettre du pôle</label><select id="a-pole" name="pole"><?php foreach ($poles as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach ?></select></div>
      <?php endif ?>
      <div class="field"><label for="a-source">Origine de l'accord <span class="req">(obligatoire)</span></label><input id="a-source" name="source" required placeholder="Ex. : bulletin d'adhésion 2026"></div>
      <button class="btn btn--primary" type="submit">Ajouter l'abonné</button>
    </form>
  </details>

  <details class="card advanced-card">
    <summary class="card__title"><?= Ui::icon('upload') ?>Importer un fichier CSV</summary>
    <form method="post" action="/admin/newsletters/abonnes/import" enctype="multipart/form-data" novalidate>
      <?= Csrf::field() ?>
      <p class="muted">Une colonne <code>email</code> obligatoire ; <code>pole</code> et <code>date</code> facultatives. Une adresse déjà connue n'est jamais modifiée.</p>
      <div class="field"><label for="i-csv">Fichier CSV <span class="req">(obligatoire)</span></label><input id="i-csv" name="csv" type="file" accept=".csv,text/csv" required></div>
      <?php if (count($poles) > 1): ?>
      <div class="field"><label for="i-pole">Pôle si la colonne « pole » manque</label><select id="i-pole" name="pole"><option value="">Aucun</option><?php foreach ($poles as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach ?></select></div>
      <?php endif ?>
      <div class="field"><label for="i-source">Origine des contacts <span class="req">(obligatoire)</span></label><input id="i-source" name="source" required placeholder="Ex. : inscrits de l'ancien site"></div>
      <div class="field">
        <label class="switch" for="i-consent">
          <input type="checkbox" id="i-consent" name="consent" value="1">
          <span class="switch__track" aria-hidden="true"></span>
          <span class="switch__text">Ils se sont inscrits eux-mêmes (sinon : importés « en attente », sans envoi).</span>
        </label>
      </div>
      <button class="btn btn--primary" type="submit"><?= Ui::icon('upload') ?>Importer</button>
    </form>
  </details>
</div>
