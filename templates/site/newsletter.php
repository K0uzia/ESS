<?php
use App\Site\Components;
?>
<div class="page-head">
  <div class="page-head__inner">
    <h1><?= e($page['meta']['h1']) ?></h1>
  </div>
</div>
<section class="section">
  <div class="section__inner prose">
    <?php if ($message): ?>
    <p class="alert<?= $status === 'lien-invalide' ? ' alert--error' : '' ?>" role="status"><?= e($message) ?></p>
    <?php endif ?>
    <?php if ($status === 'desinscription' && $subscriber): ?>
    <p>Vous allez vous désinscrire de la lettre <?= e($subscriber['org_name']) ?> pour l'adresse <strong><?= e($subscriber['email']) ?></strong>.</p>
    <form method="post" action="/newsletter/desinscription">
      <input type="hidden" name="jeton" value="<?= e($subscriber['token']) ?>">
      <button class="button" type="submit">Confirmer la désinscription</button>
    </form>
    <?php elseif ($status !== 'demande' && $status !== 'desinscrit'): ?>
    <h2>S'inscrire</h2>
    <?= Components::render('@newsletter', 3) ?>
    <?php endif ?>
    <p><a href="/">Retour à l'accueil</a></p>
  </div>
</section>
