<?php
use App\Admin\Ui;

$labels = ['draft' => 'Brouillon', 'queued' => 'Envoi en cours', 'sending' => 'Envoi en cours', 'sent' => 'Envoyée'];
?>
<?php
$intro = [
    'tag29' => 'Gérez la page Tag29 et l\'équipe TAg29 du site.',
    'ess29' => 'Gérez les pages du groupement, l\'équipe ESS29, les offres d\'emploi et les documents communs.',
][$scope] ?? 'Gérez l\'équipe, les adhérents, les offres d\'emploi, les documents et les newsletters du site.';
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Bonjour <?= e(explode(' ', $user['name'] ?: $user['username'])[0]) ?></h1>
    <p class="page-head__sub"><?= e($intro) ?></p>
  </div>
  <div class="page-head__actions">
    <?php if ($scope === 'tag29'): ?>
    <a class="btn btn--primary" href="/admin/pages/tag29"><?= Ui::icon('edit') ?>Modifier la page Tag29</a>
    <a class="btn" href="/admin/equipe"><?= Ui::icon('users') ?>Équipe TAg29</a>
    <?php else: ?>
    <a class="btn btn--primary" href="/admin/offres/nouveau" data-modal><?= Ui::icon('plus') ?>Publier une offre</a>
    <?php if ($scope === 'ess29'): ?><a class="btn" href="/admin/pages"><?= Ui::icon('page') ?>Modifier les pages</a><?php endif ?>
    <?php endif ?>
    <?php if ($hasNewsletters): ?><a class="btn" href="/admin/newsletters/campagnes/nouvelle"><?= Ui::icon('send') ?>Écrire une newsletter</a><?php endif ?>
  </div>
</header>

<?php if ($tempAccount): ?>
<div class="notice notice--warning"><?= Ui::icon('alert') ?><p>Le compte provisoire « lacapsule » a encore son mot de passe d'origine. <a href="/admin/comptes">Ouvrir les comptes</a></p></div>
<?php endif ?>

<ul class="stats" role="list">
  <?php foreach ($stats as [$href, $label, $n]): ?>
  <li><a class="stat" href="<?= e($href) ?>"><strong class="stat__n"><?= (int) $n ?></strong><span><?= e($label) ?></span></a></li>
  <?php endforeach ?>
</ul>

<div class="grid">
  <?php if ($scope !== 'tag29'): ?>
  <section class="card card--flush" aria-labelledby="offres-titre">
    <div class="group-card__head"><h2 class="group-card__title" id="offres-titre">Offres d'emploi en ligne</h2><a class="btn btn--ghost" href="/admin/offres">Toutes les offres<?= Ui::icon('arrow') ?></a></div>
    <?php if ($offers === []): ?>
    <p class="group-card__empty">Aucune offre en ligne. <a href="/admin/offres/nouveau" data-modal>Publier une offre</a></p>
    <?php else: ?>
    <ul class="rows" role="list">
      <?php foreach ($offers as $o): ?>
      <li class="rows__item"><a class="rows__main" href="/admin/offres/<?= (int) $o['id'] ?>" data-modal><span class="rows__name"><?= e($o['title']) ?></span><span class="rows__detail"><?= $o['closes_on'] ? 'Jusqu\'au ' . e(date('d/m/Y', (int) strtotime($o['closes_on']))) : 'Sans date limite' ?></span></a></li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>
  </section>
  <?php endif ?>

  <?php if ($hasNewsletters): ?>
  <section class="card card--flush" aria-labelledby="lettres-titre">
    <div class="group-card__head"><h2 class="group-card__title" id="lettres-titre">Dernières newsletters</h2><a class="btn btn--ghost" href="/admin/newsletters">Toutes les lettres<?= Ui::icon('arrow') ?></a></div>
    <?php if ($letters === []): ?>
    <p class="group-card__empty">Aucune lettre pour l'instant. <a href="/admin/newsletters/campagnes/nouvelle">Écrire la première</a></p>
    <?php else: ?>
    <ul class="rows" role="list">
      <?php foreach ($letters as $l): $sending = in_array($l['status'], ['queued', 'sending'], true); ?>
      <li class="rows__item">
        <a class="rows__main" href="/admin/newsletters/campagnes/<?= (int) $l['id'] ?>">
          <span class="rows__name"><?= e($l['subject']) ?></span>
          <span class="rows__detail"><?= Ui::poleBadge($l['org']) ?> <?= e($l['audience']) ?> · <?= e($labels[$l['status']]) ?><?= $l['status'] === 'sent' && $l['sent_at'] ? ' le ' . e(Ui::date($l['sent_at'])) : '' ?></span>
        </a>
        <?php if ($sending && $l['total'] > 0): ?>
        <progress class="progress" max="<?= (int) $l['total'] ?>" value="<?= (int) $l['sent'] ?>" aria-label="Envoi de « <?= e($l['subject']) ?> »"><?= (int) $l['sent'] ?> / <?= (int) $l['total'] ?></progress>
        <?php endif ?>
      </li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>
  </section>
  <?php endif ?>
</div>
