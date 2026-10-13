<?php use App\Admin\Ui; use App\Core\Csrf; ?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/messages"><?= Ui::icon('back') ?>Messages reçus</a>
    <h1>Message de <?= e($m['name']) ?></h1>
  </div>
</header>
<div class="form-layout">
  <section class="card form-layout__main" aria-label="Message">
    <dl class="meta-list">
      <dt>E-mail</dt><dd><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></dd>
      <dt>Pôle</dt><dd><?= e($m['pole'] ?? 'Groupement (bonjour@ess29.org)') ?></dd>
      <dt>Reçu le</dt><dd><?= e(Ui::date($m['created_at'], true)) ?></dd>
      <dt>Transmis par e-mail</dt><dd><?= $m['mail_sent'] ? 'Oui' : '<strong>Non :</strong> l\'envoi a échoué, répondez depuis cette page.' ?></dd>
    </dl>
    <div class="message-body"><?= e($m['message']) ?></div>
  </section>
  <aside class="form-layout__side" aria-label="Actions">
    <section class="card">
      <h2 class="card__title">Répondre</h2>
      <a class="btn btn--primary" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re : votre message sur ess29.org') ?>"><?= Ui::icon('mail') ?>Répondre par e-mail</a>
    </section>
    <form class="card danger-zone" method="post" action="/admin/messages/<?= (int) $m['id'] ?>/suppression" data-confirm="Supprimer ce message ? Cette action est définitive.">
      <?= Csrf::field() ?>
      <button class="btn btn--danger" type="submit"><?= Ui::icon('trash') ?>Supprimer le message</button>
    </form>
  </aside>
</div>
