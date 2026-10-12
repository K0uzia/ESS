<?php use App\Core\Csrf; ?>
<div class="page-title">
  <p class="page-title__kicker"><a href="/admin/messages">Messages reçus</a></p>
  <h1><?= e($m['name']) ?></h1>
  <a class="button" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re : votre message sur ess29.org') ?>">Répondre</a>
</div>
<div class="panel">
  <dl class="meta-list">
    <dt>E-mail</dt><dd><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></dd>
    <dt>Pôle</dt><dd><?= e($m['pole'] ?? 'Groupement (bonjour@)') ?></dd>
    <dt>Reçu le</dt><dd><?= e(date('d/m/Y à H:i', strtotime($m['created_at'] . ' UTC'))) ?></dd>
    <dt>Transmis par e-mail</dt><dd><?= $m['mail_sent'] ? 'Oui' : 'Non : l\'envoi a échoué, répondez depuis cette page.' ?></dd>
  </dl>
  <div class="message-body"><?= nl2br(e($m['message'])) ?></div>
</div>
<form method="post" action="/admin/messages/<?= (int) $m['id'] ?>/suppression" data-confirm="Supprimer ce message ?">
  <?= Csrf::field() ?>
  <button class="button button--secondary" type="submit">Supprimer le message</button>
</form>
