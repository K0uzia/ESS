<?php use App\Core\Csrf; ?>
<div class="page-title">
  <p class="page-title__kicker">Administration</p>
  <h1>Comptes</h1>
  <a class="button" href="/admin/comptes/nouveau">Nouveau compte</a>
</div>
<div class="table-wrap">
<table>
  <caption class="visually-hidden">Comptes d'accès au tableau de bord</caption>
  <thead>
    <tr><th scope="col">Identifiant</th><th scope="col">E-mail</th><th scope="col">Rôle</th><th scope="col">Dernière connexion</th><th scope="col">Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($accounts as $a): ?>
    <tr>
      <td><?= e($a['username']) ?></td>
      <td><?= e($a['email'] ?? '—') ?></td>
      <td><span class="badge"><?= $a['role'] === 'admin' ? 'Administrateur' : 'Éditeur · ' . e($a['pole']) ?></span></td>
      <td><?= $a['last_login_at'] ? e(date('d/m/Y H:i', strtotime($a['last_login_at'] . ' UTC'))) : 'Jamais' ?></td>
      <td>
        <div class="actions">
          <a href="/admin/comptes/<?= (int) $a['id'] ?>">Modifier<span class="visually-hidden"> <?= e($a['username']) ?></span></a>
          <a href="/admin/comptes/<?= (int) $a['id'] ?>/mot-de-passe">Mot de passe<span class="visually-hidden"> de <?= e($a['username']) ?></span></a>
          <form method="post" action="/admin/comptes/<?= (int) $a['id'] ?>/suppression">
            <?= Csrf::field() ?>
            <button class="button button--link" type="submit">Supprimer<span class="visually-hidden"> <?= e($a['username']) ?></span></button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>
