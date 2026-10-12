<div class="page-title">
  <p class="page-title__kicker">Contact</p>
  <h1>Messages reçus</h1>
</div>
<p class="lead">Messages envoyés depuis le formulaire de contact. Ils sont transmis par e-mail au pôle choisi et conservés ici 12 mois au plus.</p>
<?php if ($messages === []): ?>
<div class="empty"><p>Aucun message pour l'instant.</p></div>
<?php else: ?>
<div class="table-wrap">
<table>
  <caption class="visually-hidden">Messages reçus</caption>
  <thead><tr><th scope="col">Reçu le</th><th scope="col">De</th><th scope="col">Pôle</th><th scope="col">Message</th><th scope="col">E-mail transmis</th></tr></thead>
  <tbody>
    <?php foreach ($messages as $m): ?>
    <tr>
      <td><?= e(date('d/m/Y H:i', strtotime($m['created_at'] . ' UTC'))) ?></td>
      <th scope="row"><a href="/admin/messages/<?= (int) $m['id'] ?>"><?= e($m['name']) ?></a></th>
      <td><?= e($m['pole'] ?? 'Groupement') ?></td>
      <td class="clip"><?= e(mb_substr_safe($m['message'], 90)) ?>…</td>
      <td><?= $m['mail_sent'] ? '<span class="badge badge--on">Oui</span>' : '<span class="badge badge--off">Échec</span>' ?></td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>
