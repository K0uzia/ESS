<?php
if ($groups === []) {
    return;
}
$labels = ['salaries' => 'Équipe salariée', 'ca' => 'Conseil d\'administration', 'tag29' => 'Équipe TAg29'];
$filterable = count($groups) > 1;
?>
<?php if ($filterable): ?>
<div class="filter" data-filter="equipe">
  <p class="filter__label" id="filtre-equipe">Afficher :</p>
  <ul class="filter__list" role="list" aria-labelledby="filtre-equipe">
    <li><a class="filter__option" href="#equipe-tout" data-filter-value="tout">Tous les pôles</a></li>
    <?php foreach ($groups as $g): ?>
    <li><a class="filter__option pole--<?= e($g['slug']) ?>" href="#equipe-<?= e($g['slug']) ?>" data-filter-value="<?= e($g['slug']) ?>"><span class="filter__dot" aria-hidden="true"></span><?= e($g['label']) ?></a></li>
    <?php endforeach ?>
  </ul>
  <p class="visually-hidden" role="status" data-filter-status></p>
</div>
<?php endif ?>
<div id="equipe-tout">
<?php foreach ($groups as $g): ?>
<div class="team-group pole--<?= e($g['slug']) ?>" id="equipe-<?= e($g['slug']) ?>" data-filter-group="<?= e($g['slug']) ?>">
  <?php if ($filterable): ?>
  <div class="team-group__pole">
    <?php if ($logo = App\Site\Components::poleLogo($g['slug'])): ?><img src="<?= e($logo) ?>" alt="" width="217" height="125" loading="lazy" decoding="async"><?php endif ?>
    <h<?= $h ?> class="team-group__name"><?= e($g['label']) ?></h<?= $h ?>>
  </div>
  <?php endif ?>
  <?php foreach (array_reduce($g['members'], function ($c, $m) { $c[$m['team']][] = $m; return $c; }, []) as $team => $people): ?>
  <h<?= $h + ($filterable ? 1 : 0) ?> class="team-group__title"><?= e($labels[$team]) ?></h<?= $h + ($filterable ? 1 : 0) ?>>
  <ul class="people<?= $team === 'ca' ? ' people--compact' : '' ?>" role="list">
    <?php foreach ($people as $m): ?>
    <li class="person">
      <p class="person__name"><?= e($m['name']) ?></p>
      <p class="person__role"><?= e($m['role']) ?></p>
      <?php if ($m['structure']): ?><p class="person__structure"><?= e($m['structure']) ?></p><?php endif ?>
      <?php if ($m['email']): ?><p><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></p><?php endif ?>
      <?php if ($m['phone']): ?><p><a href="tel:+33<?= e(substr(str_replace(' ', '', $m['phone']), 1)) ?>"><?= e($m['phone']) ?></a></p><?php endif ?>
    </li>
    <?php endforeach ?>
  </ul>
  <?php endforeach ?>
</div>
<?php endforeach ?>
</div>
