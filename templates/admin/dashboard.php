<?php
use App\Admin\Ui;

$cards = [
    ['/admin/pages', 'Modifier une page', 'Textes et photos du site.', 'edit'],
    ['/admin/adherents/nouveau', 'Ajouter un adhérent', 'Il apparaîtra dans la liste et sur la carte.', 'pin'],
    ['/admin/offres/nouveau', 'Publier une offre', 'Offre d\'emploi visible jusqu\'à sa date limite.', 'briefcase'],
    ['/admin/newsletters/campagnes/nouvelle', 'Écrire une newsletter', 'Rédiger, vérifier, envoyer.', 'send'],
];
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Bonjour <?= e($user['username']) ?></h1>
  </div>
</header>

<ul class="actions-grid" role="list">
  <?php foreach ($cards as [$href, $label, $text, $icon]): ?>
  <li><a class="action-card" href="<?= e($href) ?>"><span class="action-card__icon"><?= Ui::icon($icon) ?></span><strong><?= e($label) ?></strong><span><?= e($text) ?></span></a></li>
  <?php endforeach ?>
</ul>

<section class="card" aria-labelledby="todo-titre">
  <h2 class="card__title" id="todo-titre">À traiter<?php if ($alerts !== []): ?> <span class="badge"><?= count($alerts) ?></span><?php endif ?></h2>
  <?php if ($alerts === []): ?>
  <div class="empty"><strong>Tout est à jour</strong><p>Aucune donnée manquante, aucun envoi en échec.</p></div>
  <?php else: ?>
  <ul class="todo" role="list">
    <?php foreach ($alerts as $a): ?>
    <li>
      <span class="todo__dot<?= $a['level'] === 'bloquant' ? ' todo__dot--bloquant' : '' ?>" aria-hidden="true"></span>
      <span class="todo__text"><?php if ($a['level'] === 'bloquant'): ?><strong>À faire avant la mise en ligne :</strong> <?php endif ?><?= e($a['text']) ?></span>
      <a class="btn" href="<?= e($a['href']) ?>"<?= preg_match('#^/admin/(pages/[^/]+/(sections/|en-tete)|poles/\d)#', $a['href']) ? ' data-modal' : '' ?>><?= e($a['action']) ?></a>
    </li>
    <?php endforeach ?>
  </ul>
  <?php endif ?>
</section>
