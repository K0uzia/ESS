<?php
if ($groups === []) {
    return;
}
$labels = ['groupement' => 'Équipe ESS29', 'salaries' => 'Équipe salariée', 'ca' => 'Conseil d\'administration', 'tag29' => 'Équipe TAg29'];
$filterable = count($groups) > 1;
// Carte de l'organigramme : portrait réel ou initiales, prénom puis NOM, filet or, rôle.
$card = static function (array $m, string $slug): string {
    [$first, $last] = array_pad(explode(' ', trim((string) $m['name']), 2), 2, '');
    preg_match_all('/(?:^|[\s-])(\p{L})/u', $first . ' ' . $last, $letters);
    $initials = $first === '' ? '' : $letters[1][0] . ($last !== '' ? $letters[1][1] ?? '' : '');
    $photo = $m['photo_path'] && is_file(ROOT . '/public' . $m['photo_path']) ? $m['photo_path'] : null;
    // La direction du groupement est mise en avant, comme sur l'organigramme.
    $lead = preg_match('/^Direct(rice|eur)\b/u', (string) $m['role']) === 1;
    $vacant = (int) $m['is_vacant'] === 1;
    $logo = $vacant ? null : App\Site\Components::teamLogo($slug, $lead);
    $logoSize = $logo ? getimagesize(ROOT . '/public' . $logo) : false;
    ob_start(); ?>
    <li class="person-card<?= $lead ? ' person-card--lead' : '' ?><?= $vacant ? ' person-card--vacant' : '' ?>">
      <div class="person-card__visual">
        <?php if ($vacant): ?><span class="person-card__photo person-card__initials" aria-hidden="true">?</span><?php elseif ($photo): ?><img class="person-card__photo" src="<?= e($photo) ?>" alt="" width="120" height="120" loading="lazy" decoding="async"><?php else: ?><span class="person-card__photo person-card__initials" aria-hidden="true"><?= e($initials) ?></span><?php endif ?>
        <?php if ($logoSize): ?><img class="person-card__badge" src="<?= e($logo) ?>" alt="" width="<?= $logoSize[0] ?>" height="<?= $logoSize[1] ?>" loading="lazy" decoding="async"><?php endif ?>
      </div>
      <?php if ($vacant): ?>
      <p class="person-card__name"><?= e($m['name']) ?></p>
      <?php else: ?>
      <p class="person-card__name"><?= e($first) ?><?php if ($last !== ''): ?> <span class="person-card__last"><?= e($last) ?></span><?php endif ?></p>
      <?php endif ?>
      <p class="person-card__role"><?= e($m['role']) ?></p>
      <?php if ($m['email']): ?><p><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></p><?php endif ?>
      <?php if ($m['phone']): ?><p><a href="tel:+33<?= e(substr(str_replace(' ', '', $m['phone']), 1)) ?>"><?= e($m['phone']) ?></a></p><?php endif ?>
    </li>
    <?php return (string) ob_get_clean();
};
?>
<?php if ($filterable): ?>
<div class="filter" data-filter="equipe">
  <p class="filter__label" id="filtre-equipe">Afficher :</p>
  <ul class="filter__list" role="list" aria-labelledby="filtre-equipe">
    <li><a class="filter__option" href="#equipe-tout" data-filter-value="tout">Toute l'équipe</a></li>
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
  <h<?= $h ?> class="team-group__name"><?= e($g['label']) ?></h<?= $h ?>>
  <?php endif ?>
  <?php foreach (array_reduce($g['members'], function ($c, $m) { $c[$m['team']][] = $m; return $c; }, []) as $team => $people): ?>
  <h<?= $h + ($filterable ? 1 : 0) ?> class="team-group__title"><?= e($labels[$team]) ?></h<?= $h + ($filterable ? 1 : 0) ?>>
  <?php if ($team === 'ca'): ?>
  <ul class="people people--compact" role="list">
    <?php foreach ($people as $m): ?>
    <li class="person">
      <p class="person__name"><?= e($m['name']) ?></p>
      <p class="person__role"><?= e($m['role']) ?></p>
      <?php if ($m['structure']): ?><p class="person__structure"><?= e($m['structure']) ?></p><?php endif ?>
    </li>
    <?php endforeach ?>
  </ul>
  <?php else: ?>
  <ul class="people people--cards" role="list">
    <?php foreach ($people as $m): ?><?= $card($m, $g['slug']) ?><?php endforeach ?>
  </ul>
  <?php endif ?>
  <?php endforeach ?>
</div>
<?php endforeach ?>
</div>
