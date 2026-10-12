<?php
use App\Core\Db;

$counts = [
    ['Personnes de l\'équipe', (int) Db::run('SELECT COUNT(*) FROM team_members WHERE is_published = 1')->fetchColumn(), 'client'],
    ['Adhérents publiés', (int) Db::run('SELECT COUNT(*) FROM members WHERE is_published = 1')->fetchColumn(), 'reseau'],
    ['Documents', (int) Db::run('SELECT COUNT(*) FROM documents')->fetchColumn(), 'liste'],
    ['Abonnés actifs', (int) Db::run("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'")->fetchColumn(), 'enveloppe'],
    ['Messages reçus (12 mois)', (int) Db::run('SELECT COUNT(*) FROM contact_messages')->fetchColumn(), 'bulles'],
];
?>
<div class="page-title">
  <p class="page-title__kicker">Tableau de bord</p>
  <h1>Bonjour <?= e($user['username']) ?></h1>
</div>
<ul class="kpis" role="list">
  <?php foreach ($counts as [$label, $value, $picto]): ?>
  <li class="kpi">
    <img src="/assets/img/pictos/<?= $picto ?>.webp" alt="" width="48" height="48">
    <span class="kpi__value"><?= $value ?></span>
    <span class="kpi__label"><?= e($label) ?></span>
  </li>
  <?php endforeach ?>
</ul>
