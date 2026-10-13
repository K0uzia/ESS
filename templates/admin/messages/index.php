<?php
use App\Admin\Ui;

$slug = static fn (?string $pole): string => $pole ? strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', Ui::poleShort($pole))) : 'groupement';
$poleOpts = [];
foreach ($messages as $m) {
    $poleOpts[$slug($m['pole'])] = $m['pole'] ? Ui::poleShort($m['pole']) : 'Groupement';
}
$filters = [];
if (count($poleOpts) > 1) {
    $filters['pole'] = ['Pôle', $poleOpts];
}
if (count(array_unique(array_column($messages, 'mail_sent'))) > 1) {
    $filters['envoi'] = ['Transmission', ['ok' => 'Transmis', 'echec' => 'Non transmis']];
}
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Messages reçus</h1>
    <p class="page-head__sub">Envoyés depuis le formulaire de contact du site. Conservés 12 mois.</p>
  </div>
</header>
<section class="card card--flush" data-list aria-label="Messages reçus">
  <?php if ($messages === []): ?>
  <div class="empty"><strong>Aucun message pour l'instant</strong><p>Les messages du formulaire de contact apparaîtront ici.</p></div>
  <?php else: ?>
  <?php $searchLabel = 'Rechercher'; $countWords = ['message', 'messages']; $total = count($messages); require ROOT . '/templates/admin/partials/filters.php'; ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden">Messages reçus</caption>
      <thead><tr><th scope="col">De</th><th scope="col">Message</th><th scope="col">Pôle</th><th scope="col">Transmission</th><th scope="col" class="num">Reçu le</th></tr></thead>
      <tbody>
        <?php foreach ($messages as $m): ?>
        <tr data-f-pole="<?= e($slug($m['pole'])) ?>" data-f-envoi="<?= $m['mail_sent'] ? 'ok' : 'echec' ?>">
          <th scope="row"><a href="/admin/messages/<?= (int) $m['id'] ?>"><?= e($m['name']) ?></a><span class="sub"><?= e($m['email']) ?></span></th>
          <td data-label="Message" class="excerpt"><?= e(mb_substr_safe($m['message'], 80)) ?><?= text_length($m['message']) > 80 ? '…' : '' ?></td>
          <td data-label="Pôle"><?= Ui::poleBadge($m['pole'], 'Groupement') ?></td>
          <td data-label="Transmission"><?= $m['mail_sent'] ? '<span class="badge badge--on">Transmis</span>' : '<span class="badge badge--warn">Non transmis</span>' ?></td>
          <td data-label="Reçu le" class="num"><?= e(Ui::date($m['created_at'], true)) ?></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <div class="empty" data-filter-empty hidden><strong>Aucun résultat</strong><p>Aucun message ne correspond à la recherche ou aux filtres.</p><button class="btn" type="button" data-filter-reset>Effacer les filtres</button></div>
  <?php endif ?>
</section>
