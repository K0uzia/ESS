<?php
use App\Admin\Ui;
use App\Core\Auth;
use App\Core\Csrf;

$configured = $mail['user'] !== '' && $mail['host'] !== '';
$sender = $configured ? $mail['user'] : 'la boîte d\'envoi';
$me = Auth::user();
$auto = [
    ['Message du formulaire de contact', 'De ' . $sender . ' à ' . setting('email_contact') . ', réponse directe au visiteur'],
    ['Newsletter d\'un pôle', 'De l\'adresse d\'envoi du pôle à ses abonnés, réponses vers l\'adresse du pôle'],
    ['Confirmation d\'inscription aux newsletters', 'De ' . $sender . ' à la personne qui s\'inscrit'],
    ['Invitation à un nouveau compte', 'De ' . $sender . ', lien valable ' . intdiv(Auth::LINK_HOURS['invitation'], 24) . ' jours'],
    ['Lien de changement de mot de passe', 'De ' . $sender . ', lien valable ' . Auth::LINK_HOURS['reinitialisation'] . ' heures'],
];
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>E-mails</h1>
    <p class="page-head__sub">La boîte qui envoie les e-mails du site, et les adresses qui les reçoivent.</p>
  </div>
</header>

<?php if (!$configured): ?>
<div class="notice notice--warning"><?= Ui::icon('alert') ?><p>La boîte d'envoi n'est pas réglée : le site n'envoie aucun e-mail (invitations, formulaire de contact, newsletters).</p></div>
<?php endif ?>

<section class="card card--flush group-card" aria-labelledby="envoi-titre">
  <div class="group-card__head"><h2 class="group-card__title" id="envoi-titre"><?= Ui::icon('send') ?>Envoi</h2></div>
  <ul class="rows" role="list">
    <li class="rows__item">
      <a class="rows__main" href="/admin/emails/boite" data-modal>
        <span class="rows__name">Boîte d'envoi</span>
        <span class="rows__detail"><?= $configured ? 'Les e-mails partent de ' . e($mail['user']) : 'Pas encore réglée' ?></span>
      </a>
      <?php if ($configured): ?>
      <span class="badge badge--on">Réglée</span>
      <form method="post" action="/admin/emails/test">
        <?= Csrf::field() ?>
        <button class="btn btn--ghost" type="submit"<?= empty($me['email']) ? ' disabled' : '' ?>><?= Ui::icon('send') ?>Tester<span class="visually-hidden"> : m'envoyer un e-mail de test</span></button>
      </form>
      <?php else: ?>
      <span class="badge badge--warn">À régler</span>
      <?php endif ?>
      <a class="btn" href="/admin/emails/boite" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> la boîte d'envoi</span></a>
    </li>
  </ul>
</section>

<section class="card card--flush group-card" aria-labelledby="adresses-titre">
  <div class="group-card__head"><h2 class="group-card__title" id="adresses-titre"><?= Ui::icon('mail') ?>Adresses</h2></div>
  <ul class="rows" role="list">
    <li class="rows__item">
      <a class="rows__main" href="/admin/emails/contact" data-modal>
        <span class="rows__name">Formulaire de contact</span>
        <span class="rows__detail">Messages reçus à <?= e(setting('email_contact')) ?></span>
      </a>
      <a class="btn" href="/admin/emails/contact" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> le formulaire de contact</span></a>
    </li>
    <?php foreach ($poles as $p): ?>
    <li class="rows__item">
      <span class="tabset__dot pole-dot--<?= e(preg_replace('/^pays-de-/', '', (string) $p['slug'])) ?>" aria-hidden="true"></span>
      <a class="rows__main" href="/admin/emails/poles/<?= (int) $p['id'] ?>" data-modal>
        <span class="rows__name"><?= e($p['name']) ?></span>
        <span class="rows__detail">Newsletters depuis <?= e((string) $p['newsletter_email']) ?> · adresse du pôle <?= e((string) $p['email']) ?></span>
      </a>
      <a class="btn" href="/admin/emails/poles/<?= (int) $p['id'] ?>" data-modal><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> : <?= e($p['name']) ?></span></a>
    </li>
    <?php endforeach ?>
  </ul>
</section>

<section class="card card--flush group-card" aria-labelledby="auto-titre">
  <div class="group-card__head"><h2 class="group-card__title" id="auto-titre">Ce que le site envoie</h2></div>
  <ul class="rows" role="list">
    <?php foreach ($auto as [$name, $detail]): ?>
    <li class="rows__item"><div class="rows__main"><span class="rows__name"><?= e($name) ?></span><span class="rows__detail"><?= e($detail) ?></span></div></li>
    <?php endforeach ?>
  </ul>
</section>
