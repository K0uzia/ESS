<?php
use App\Admin\Ui;
use App\Core\Auth;
use App\Core\Csrf;

$me = Auth::user();
$temp = Auth::defaultAccountActive();
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Comptes</h1>
    <p class="page-head__sub">Les personnes qui peuvent se connecter au tableau de bord. Ouvrez un compte pour changer son e-mail ou son mot de passe.</p>
  </div>
  <div class="page-head__actions"><a class="btn btn--primary" href="/admin/comptes/nouveau" data-modal><?= Ui::icon('plus') ?>Nouveau compte</a></div>
</header>
<?php if ($temp): ?>
<div class="notice notice--warning"><?= Ui::icon('alert') ?><p>Le compte temporaire « lacapsule » a encore son mot de passe d'origine : changez-le ou supprimez ce compte avant la mise en ligne.</p></div>
<?php endif ?>
<section class="card card--flush" aria-label="Liste des comptes">
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden">Comptes d'accès au tableau de bord</caption>
      <thead><tr><th scope="col">Identifiant</th><th scope="col">E-mail</th><th scope="col">Rôle</th><th scope="col" class="num">Dernière connexion</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($accounts as $a): $self = (int) $a['id'] === (int) $me['id']; ?>
        <tr>
          <th scope="row"><a href="/admin/comptes/<?= (int) $a['id'] ?>" data-modal><?= e($a['username']) ?></a><?= $self ? ' <span class="badge">Vous</span>' : '' ?></th>
          <td data-label="E-mail"><?= $a['email'] ? e($a['email']) : '<span class="muted">—</span>' ?></td>
          <td data-label="Rôle"><?= $a['role'] === 'admin' ? 'Administrateur' : 'Éditeur · ' . e(Ui::poleShort($a['pole'])) ?></td>
          <td data-label="Connexion" class="num"><?= $a['last_login_at'] ? e(Ui::date($a['last_login_at'], true)) : '<span class="muted">Jamais</span>' ?></td>
          <td class="actions-col">
            <div class="button-row">
              <a class="btn" href="/admin/comptes/<?= (int) $a['id'] ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> <?= e($a['username']) ?></span></a>
              <?php if (!$self): ?>
              <form method="post" action="/admin/comptes/<?= (int) $a['id'] ?>/suppression" data-confirm="Supprimer le compte « <?= e($a['username']) ?> » ? Cette personne ne pourra plus se connecter.">
                <?= Csrf::field() ?>
                <button class="btn btn--danger" type="submit"><?= Ui::icon('trash') ?>Supprimer<span class="visually-hidden"> <?= e($a['username']) ?></span></button>
              </form>
              <?php endif ?>
            </div>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</section>
