<?php
use App\Admin\Ui;
use App\Core\Csrf;

$labels = ['active' => 'Actif', 'pending' => 'En attente', 'unsubscribed' => 'Désinscrit'];
$listKeys = array_combine(array_map(static fn (array $l): string => 'l' . $l['id'], $lists), array_column($lists, 'label'));
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
    if (count($lists) > 1) { $filters = ['liste' => ['Liste', $listKeys]] + $filters; }
    $searchLabel = 'Rechercher une adresse'; $countWords = ['abonné', 'abonnés']; $total = $n;
    require ROOT . '/templates/admin/partials/filters.php';
  ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden">Abonnés</caption>
      <thead><tr><th scope="col">Adresse</th><th scope="col">Liste</th><th scope="col">Statut</th><th scope="col">Accord donné</th><th scope="col" class="num">Depuis le</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
        <tr data-f-liste="l<?= (int) $r['list_id'] ?>" data-f-etat="<?= e($r['status']) ?>">
          <th scope="row"><?= e($r['email']) ?></th>
          <td data-label="Liste"><?= e($r['list']) ?></td>
          <td data-label="Statut"><span class="badge<?= $r['status'] === 'active' ? ' badge--on' : ($r['status'] === 'unsubscribed' ? ' badge--off' : ' badge--warn') ?>"><?= $labels[$r['status']] ?></span></td>
          <td data-label="Accord donné" class="excerpt"><?= e($r['source']) ?></td>
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
      <div class="field"><label for="a-liste">Liste</label><select id="a-liste" name="liste"><?php foreach ($lists as $l): ?><option value="<?= (int) $l['id'] ?>"><?= e($l['label']) ?></option><?php endforeach ?></select></div>
      <div class="field"><label for="a-source">Comment a-t-elle donné son accord ? <span class="req">(obligatoire)</span></label><input id="a-source" name="source" required aria-describedby="a-source-hint"><p class="field__hint" id="a-source-hint">Par exemple : bulletin d'adhésion 2026, salon de mai. Gardé comme preuve de l'accord.</p></div>
      <button class="btn btn--primary" type="submit">Ajouter l'abonné</button>
    </form>
  </details>

  <details class="card advanced-card">
    <summary class="card__title"><?= Ui::icon('upload') ?>Importer une liste d'adresses</summary>
    <form method="post" action="/admin/newsletters/abonnes/import" enctype="multipart/form-data" novalidate>
      <?= Csrf::field() ?>
      <p class="muted">Un fichier tableur enregistré au format CSV, avec une colonne « email ». Une colonne « date » est reconnue si elle existe ; une colonne « pole » (Brest, Morlaix, Cornouaille, ESS29, TAg29) range chaque ligne dans la liste du même public de cet organisme. Une adresse déjà inscrite n'est jamais modifiée.</p>
      <div class="field"><label for="i-csv">Fichier (.csv) <span class="req">(obligatoire)</span></label><input id="i-csv" name="csv" type="file" accept=".csv,text/csv" required></div>
      <div class="field"><label for="i-liste">Liste</label><select id="i-liste" name="liste"><?php foreach ($lists as $l): ?><option value="<?= (int) $l['id'] ?>"><?= e($l['label']) ?></option><?php endforeach ?></select></div>
      <div class="field"><label for="i-source">D'où viennent ces adresses ? <span class="req">(obligatoire)</span></label><input id="i-source" name="source" required aria-describedby="i-source-hint"><p class="field__hint" id="i-source-hint">Par exemple : inscrits de l'ancien site. Gardé comme preuve de l'accord.</p></div>
      <div class="field">
        <label class="switch" for="i-consent">
          <input type="checkbox" id="i-consent" name="consent" value="1">
          <span class="switch__track" aria-hidden="true"></span>
          <span class="switch__text">Ces personnes se sont inscrites elles-mêmes. Sinon, elles ne recevront rien tant qu'elles n'auront pas confirmé.</span>
        </label>
      </div>
      <button class="btn btn--primary" type="submit"><?= Ui::icon('upload') ?>Importer</button>
    </form>
  </details>
</div>
