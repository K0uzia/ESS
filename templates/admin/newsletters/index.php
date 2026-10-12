<?php use App\Core\Csrf;
$labels = ['draft' => 'Brouillon', 'queued' => 'En file', 'sending' => 'En cours d\'envoi', 'sent' => 'Envoyée'];
?>
<div class="page-title">
  <p class="page-title__kicker">Communication</p>
  <h1>Newsletters</h1>
  <a class="button" href="/admin/newsletters/campagnes/nouvelle">Rédiger une lettre</a>
</div>

<ul class="kpis" role="list">
  <?php foreach ($poles as $p): $c = $counts[$p['id']] ?? []; ?>
  <li class="kpi">
    <span class="kpi__label"><strong><?= e($p['name']) ?></strong></span>
    <span class="kpi__value"><?= (int) ($c['active'] ?? 0) ?></span>
    <span class="kpi__label">abonnés actifs · <?= (int) ($c['pending'] ?? 0) ?> en attente · <?= (int) ($c['unsubscribed'] ?? 0) ?> désinscrits</span>
    <span class="kpi__label">Expéditeur : <?= e($p['email']) ?></span>
    <a class="kpi__link" href="/admin/newsletters/abonnes?pole=<?= (int) $p['id'] ?>">Voir les abonnés</a>
  </li>
  <?php endforeach ?>
</ul>

<section class="panel" aria-labelledby="envoi-titre">
  <h2 id="envoi-titre">Envoi</h2>
  <p>Les lettres partent par lots de <?= (int) $quota[2] ?> toutes les 10 minutes (tâche planifiée <code>bin/newsletter-send</code>), dans la limite de <?= (int) $quota[1] ?> e-mails par 24 heures pour respecter le forfait Hostinger. Envoyés ces dernières 24 h : <strong><?= (int) $quota[0] ?></strong>. En attente : <strong><?= (int) $pending ?></strong>.</p>
  <?php if ($pending > 0): ?>
  <form method="post" action="/admin/newsletters/lot"><?= Csrf::field() ?><button class="button button--secondary" type="submit">Envoyer un lot maintenant</button></form>
  <?php endif ?>
</section>

<h2 class="block-title">Lettres</h2>
<?php if ($campaigns === []): ?>
<div class="empty"><p>Aucune lettre rédigée pour l'instant.</p><a class="button" href="/admin/newsletters/campagnes/nouvelle">Rédiger la première</a></div>
<?php else: ?>
<div class="table-wrap">
<table>
  <caption class="visually-hidden">Lettres</caption>
  <thead><tr><th scope="col">Objet</th><th scope="col">Pôle</th><th scope="col">Statut</th><th scope="col">Envoi</th><th scope="col">Créée le</th></tr></thead>
  <tbody>
    <?php foreach ($campaigns as $c): ?>
    <tr>
      <th scope="row"><a href="/admin/newsletters/campagnes/<?= (int) $c['id'] ?>"><?= e($c['subject']) ?></a></th>
      <td><?= e($c['pole']) ?></td>
      <td><span class="badge<?= $c['status'] === 'sent' ? ' badge--on' : '' ?>"><?= e($labels[$c['status']]) ?></span></td>
      <td><?= $c['total'] ? (int) $c['sent'] . ' / ' . (int) $c['total'] : '—' ?></td>
      <td><?= e(date('d/m/Y', strtotime($c['created_at'] . ' UTC'))) ?></td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>

<div class="page-title page-title--sub">
  <h2>Modèles</h2>
  <a class="button button--secondary" href="/admin/newsletters/modeles/nouveau">Nouveau modèle</a>
</div>
<p class="lead">Un modèle ajoute un texte d'introduction et une signature communs à plusieurs lettres d'un pôle. Le logo et les coordonnées du pôle sont toujours ajoutés.</p>
<?php if ($templates === []): ?>
<div class="empty"><p>Aucun modèle. Les lettres peuvent aussi être envoyées sans modèle.</p></div>
<?php else: ?>
<ul class="tiles" role="list">
  <?php foreach ($templates as $t): ?>
  <li class="tile"><a class="tile__link" href="/admin/newsletters/modeles/<?= (int) $t['id'] ?>"><?= e($t['name']) ?></a><span class="tile__meta"><?= e($t['pole']) ?></span></li>
  <?php endforeach ?>
</ul>
<?php endif ?>
