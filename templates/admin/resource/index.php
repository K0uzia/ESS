<?php
use App\Core\Csrf;

$poleNames = array_column($poles, 'name', 'id');
$display = static function (string $col, array $row) use ($res, $poleNames): string {
    $f = $res['fields'][$col] ?? ['type' => 'text'];
    $v = $row[$col] ?? null;
    return match (true) {
        $f['type'] === 'checkbox' => (int) $v === 1 ? '<span class="badge badge--on">Oui</span>' : '<span class="badge badge--off">Non</span>',
        $f['type'] === 'pole' => e($poleNames[$v] ?? ($f['empty'] ?? '—')),
        $f['type'] === 'select' => e($f['options'][$v] ?? (string) $v),
        $f['type'] === 'image' => $v ? '<img class="thumb" src="' . e($v) . '" alt="" height="32">' : '—',
        $f['type'] === 'date' => $v ? e(date('d/m/Y', strtotime($v))) : '—',
        default => $v === null || $v === '' ? '—' : e((string) $v),
    };
};
$first = array_key_first($res['columns']);
?>
<div class="page-title">
  <p class="page-title__kicker">Gestion</p>
  <h1><?= e($res['label']) ?></h1>
  <?php if (empty($res['fixed'])): ?><a class="button" href="/admin/<?= e($key) ?>/nouveau">Ajouter</a><?php endif ?>
</div>
<p class="lead"><?= e($res['intro']) ?></p>

<?php if ($canFilter): ?>
<form class="toolbar" method="get" action="/admin/<?= e($key) ?>">
  <label for="filtre-pole">Pôle</label>
  <select id="filtre-pole" name="pole">
    <option value="">Tous</option>
    <?php foreach ($poles as $p): ?>
    <option value="<?= (int) $p['id'] ?>"<?= $poleFilter === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
    <?php endforeach ?>
  </select>
  <button class="button button--secondary button--small" type="submit">Filtrer</button>
  <span class="toolbar__count"><?= count($rows) ?> fiche<?= count($rows) > 1 ? 's' : '' ?></span>
</form>
<?php endif ?>

<?php if ($rows === []): ?>
<div class="empty">
  <p>Aucune fiche pour l'instant.</p>
  <?php if (empty($res['fixed'])): ?><a class="button" href="/admin/<?= e($key) ?>/nouveau">Ajouter la première</a><?php endif ?>
</div>
<?php else: ?>
<div class="table-wrap">
<table>
  <caption class="visually-hidden"><?= e($res['label']) ?></caption>
  <thead>
    <tr>
      <?php foreach ($res['columns'] as $label): ?><th scope="col"><?= e($label) ?></th><?php endforeach ?>
      <th scope="col"><span class="visually-hidden">Actions</span></th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $row): ?>
    <tr>
      <?php foreach (array_keys($res['columns']) as $col): ?>
      <?php if ($col === $first): ?>
      <th scope="row"><a href="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>"><?= $display($col, $row) ?></a></th>
      <?php else: ?>
      <td><?= $display($col, $row) ?></td>
      <?php endif ?>
      <?php endforeach ?>
      <td class="row-actions">
        <a href="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>">Modifier</a>
        <?php if (empty($res['fixed'])): ?>
        <form method="post" action="/admin/<?= e($key) ?>/<?= (int) $row['id'] ?>/suppression" data-confirm="Supprimer cette fiche ? Cette action est définitive.">
          <?= Csrf::field() ?>
          <button class="button--link button--danger" type="submit">Supprimer</button>
        </form>
        <?php endif ?>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>
