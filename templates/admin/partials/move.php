<?php
/**
 * Flèches Monter / Descendre d'une fiche dans son groupe.
 * @var string $key @var array $row @var bool $isFirst @var bool $isLast @var string $moveTab onglet à rouvrir
 */
use App\Admin\Ui;
use App\Core\Csrf;

$moveName = (string) ($row['name'] ?? $row['title'] ?? '');
?>
<div class="move" role="group" aria-label="Place de « <?= e($moveName) ?> »">
  <?php foreach (['haut' => ['up', 'Monter', $isFirst], 'bas' => ['down', 'Descendre', $isLast]] as $sens => [$icon, $label, $off]): ?>
  <form method="post" action="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>/deplacer">
    <?= Csrf::field() ?><input type="hidden" name="sens" value="<?= $sens ?>"><input type="hidden" name="onglet" value="<?= e($moveTab ?? '') ?>">
    <button class="btn btn--icon btn--ghost" type="submit"<?= $off ? ' disabled' : '' ?>><?= Ui::icon($icon) ?><span class="visually-hidden"><?= $label ?> « <?= e($moveName) ?> »</span></button>
  </form>
  <?php endforeach ?>
</div>
