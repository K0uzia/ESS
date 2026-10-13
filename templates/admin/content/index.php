<?php
use App\Admin\ContentController;
use App\Admin\Ui;
use App\Site\SiteController;

$main = array_filter($pages, static fn (string $k): bool => in_array($k, SiteController::PAGES, true), ARRAY_FILTER_USE_KEY);
$groups = array_filter([
    'Pages' => $main,
    'Onglets de la page « Les pôles »' => array_diff_key($pages, $main),
]);
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Pages du site</h1>
    <p class="page-head__sub">Choisissez une page pour modifier ses textes et ses photos.</p>
  </div>
</header>
<?php foreach ($groups as $group => $list): $gid = 'g-' . substr(md5($group), 0, 6); ?>
<section class="card card--flush" aria-labelledby="<?= $gid ?>">
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden" id="<?= $gid ?>"><?= e($group) ?></caption>
      <thead><tr><th scope="col"><?= e($group) ?></th><th scope="col" class="num">Modifiée le</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($list as $pk => $label): ?>
        <tr>
          <th scope="row"><a href="/admin/pages/<?= e($pk) ?>"><?= e($label) ?></a></th>
          <td data-label="Modifiée le" class="num"><?= e(Ui::date($updated[$pk]['u'] ?? null)) ?></td>
          <td class="actions-col">
            <div class="button-row">
              <a class="btn" href="<?= e(ContentController::publicUrl($pk)) ?>" target="_blank" rel="noopener"><?= Ui::icon('eye') ?>Voir<span class="visually-hidden"> <?= e($label) ?> sur le site (nouvel onglet)</span></a>
              <a class="btn btn--primary" href="/admin/pages/<?= e($pk) ?>"><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> <?= e($label) ?></span></a>
            </div>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
</section>
<?php endforeach ?>
