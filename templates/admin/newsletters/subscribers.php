<?php use App\Core\Csrf;
$labels = ['active' => 'Actif', 'pending' => 'En attente', 'unsubscribed' => 'Désinscrit'];
?>
<div class="page-title">
  <p class="page-title__kicker"><a href="/admin/newsletters">Newsletters</a></p>
  <h1>Abonnés</h1>
  <a class="button button--secondary" href="/admin/newsletters/abonnes/export<?= $pole ? '?pole=' . (int) $pole : '' ?>">Exporter en CSV</a>
</div>

<form class="toolbar" method="get" action="/admin/newsletters/abonnes">
  <?php if (count($poles) > 1): ?>
  <label for="f-pole">Pôle</label>
  <select id="f-pole" name="pole"><option value="">Tous</option><?php foreach ($poles as $p): ?><option value="<?= (int) $p['id'] ?>"<?= $pole === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach ?></select>
  <?php endif ?>
  <label for="f-statut">Statut</label>
  <select id="f-statut" name="statut"><option value="">Tous</option><?php foreach ($labels as $v => $l): ?><option value="<?= $v ?>"<?= $status === $v ? ' selected' : '' ?>><?= $l ?></option><?php endforeach ?></select>
  <label for="f-q">Adresse</label>
  <input id="f-q" name="q" value="<?= e($q) ?>" type="search">
  <button class="button button--secondary button--small" type="submit">Filtrer</button>
  <span class="toolbar__count"><?= count($rows) ?><?= count($rows) === 500 ? '+' : '' ?> abonné<?= count($rows) > 1 ? 's' : '' ?></span>
</form>

<?php if ($rows === []): ?>
<div class="empty"><p>Aucun abonné ne correspond.</p></div>
<?php else: ?>
<div class="table-wrap">
<table>
  <caption class="visually-hidden">Abonnés</caption>
  <thead><tr><th scope="col">Adresse</th><th scope="col">Pôle</th><th scope="col">Statut</th><th scope="col">Origine</th><th scope="col">Consentement</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <th scope="row"><?= e($r['email']) ?></th>
      <td><?= e($r['pole']) ?></td>
      <td><span class="badge<?= $r['status'] === 'active' ? ' badge--on' : ($r['status'] === 'unsubscribed' ? ' badge--off' : '') ?>"><?= $labels[$r['status']] ?></span></td>
      <td class="clip"><?= e($r['source']) ?></td>
      <td><?= e(date('d/m/Y', strtotime($r['consent_at'] . ' UTC'))) ?></td>
      <td class="row-actions">
        <form method="post" action="/admin/newsletters/abonnes/<?= (int) $r['id'] ?>/suppression" data-confirm="Supprimer définitivement cette adresse ?">
          <?= Csrf::field() ?><button class="button--link button--danger" type="submit">Supprimer<span class="visually-hidden"> <?= e($r['email']) ?></span></button>
        </form>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>

<div class="columns">
  <form class="panel" method="post" action="/admin/newsletters/abonnes" novalidate>
    <?= Csrf::field() ?>
    <h2>Ajouter un abonné</h2>
    <p class="field__hint">Seulement pour une personne qui a donné son accord (bulletin papier, salon…). Sinon, invitez-la à s'inscrire sur le site.</p>
    <div class="field"><label for="a-email">Adresse e-mail</label><input id="a-email" name="email" type="email" required autocomplete="off"></div>
    <?php if (count($poles) > 1): ?>
    <div class="field"><label for="a-pole">Lettre du pôle</label><select id="a-pole" name="pole"><?php foreach ($poles as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach ?></select></div>
    <?php endif ?>
    <div class="field"><label for="a-source">Origine du consentement</label><input id="a-source" name="source" required placeholder="Ex. : bulletin d'adhésion 2026"></div>
    <button class="button" type="submit">Ajouter</button>
  </form>

  <form class="panel" method="post" action="/admin/newsletters/abonnes/import" enctype="multipart/form-data" novalidate>
    <?= Csrf::field() ?>
    <h2>Importer un fichier CSV</h2>
    <p class="field__hint">Première ligne : noms des colonnes. Colonne <code>email</code> obligatoire ; <code>pole</code> (Brest, Morlaix, Cornouaille) et <code>date</code> d'inscription facultatives. Séparateur virgule ou point-virgule. Une adresse déjà inscrite ou désinscrite n'est jamais modifiée.</p>
    <div class="field"><label for="i-csv">Fichier CSV</label><input id="i-csv" name="csv" type="file" accept=".csv,text/csv" required></div>
    <?php if (count($poles) > 1): ?>
    <div class="field"><label for="i-pole">Pôle si la colonne « pole » est absente</label><select id="i-pole" name="pole"><option value="">Aucun</option><?php foreach ($poles as $p): ?><option value="<?= (int) $p['id'] ?>"><?= e($p['name']) ?></option><?php endforeach ?></select></div>
    <?php endif ?>
    <div class="field"><label for="i-source">Origine des contacts</label><input id="i-source" name="source" required placeholder="Ex. : inscrits à la lettre de l'ancien site ess29.org"></div>
    <div class="field field--check">
      <label class="switch" for="i-consent">
        <input type="checkbox" id="i-consent" name="consent" value="1">
        <span class="switch__track" aria-hidden="true"></span>
        <span>Ces personnes se sont inscrites elles-mêmes à la lettre (consentement prouvable). Sinon, elles sont importées « en attente » et ne reçoivent rien.</span>
      </label>
    </div>
    <button class="button" type="submit">Importer</button>
  </form>
</div>
