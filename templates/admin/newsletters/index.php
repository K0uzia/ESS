<?php
use App\Admin\Ui;
use App\Core\Csrf;

$labels = ['draft' => 'Brouillon', 'queued' => 'En cours d\'envoi', 'sending' => 'En cours d\'envoi', 'sent' => 'Envoyée'];
$vue = ($_GET['vue'] ?? '') === 'modeles' ? 'modeles' : 'lettres';
// Lettres rangées par organisme (un onglet chacun), puis par liste : adhérents, inscrits via le site.
$keys = array_keys($orgs);
$active = in_array($_GET['onglet'] ?? '', $keys, true) ? (string) $_GET['onglet'] : ($keys[0] ?? '');
$multi = count($orgs) > 1;
?>
<header class="page-head">
  <div class="page-head__text">
    <h1>Newsletters</h1>
    <p class="page-head__sub"><?= $vue === 'modeles' ? 'Introduction et signature réutilisables, communes à tous les organismes.' : 'Chaque organisme a deux listes : ses adhérents et les personnes inscrites via le site. Chaque liste a ses abonnés et ses lettres.' ?></p>
  </div>
  <?php if ($vue === 'modeles'): ?>
  <div class="page-head__actions">
    <a class="btn btn--primary" href="/admin/newsletters/modeles/nouveau"><?= Ui::icon('plus') ?>Nouveau modèle</a>
  </div>
  <?php endif ?>
</header>
<?php $tab = $vue; require __DIR__ . '/tabs.php'; ?>

<?php if ($vue === 'modeles'): ?>
<section class="card card--flush" aria-label="Modèles">
  <?php if ($templates === []): ?>
  <div class="empty"><strong>Aucun modèle</strong><p>Un modèle ajoute la même introduction et la même signature à plusieurs lettres, quel que soit l'organisme. Il est facultatif.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table table--stack">
      <caption class="visually-hidden">Modèles</caption>
      <thead><tr><th scope="col">Nom</th><th scope="col" class="num">Modifié le</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
      <tbody>
        <?php foreach ($templates as $t): ?>
        <tr>
          <th scope="row"><a href="/admin/newsletters/modeles/<?= (int) $t['id'] ?>"><?= e($t['name']) ?></a></th>
          <td data-label="Modifié le" class="num"><?= e(Ui::date($t['updated_at'])) ?></td>
          <td class="actions-col"><a class="btn" href="/admin/newsletters/modeles/<?= (int) $t['id'] ?>"><?= Ui::icon('edit') ?>Modifier<span class="visually-hidden"> <?= e($t['name']) ?></span></a></td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php endif ?>
</section>
<?php else: ?>
<?php if ($pending > 0): ?>
<div class="notice"><?= Ui::icon('send') ?><p>Envoi en cours : <?= (int) $pending ?> e-mail<?= $pending > 1 ? 's partent' : ' part' ?> petit à petit, pour rester dans la limite d'envoi quotidienne. Rien à faire de votre côté.</p></div>
<?php endif ?>
<?php if ($orgs === []): ?>
<div class="empty"><strong>Aucune liste ouverte</strong><p>Les listes se règlent dans E-mails.</p></div>
<?php endif ?>
<div class="tabset" data-tabset>
  <?php if ($multi): ?>
  <div class="tabset__list" role="tablist" aria-label="Organismes" hidden data-tabset-list>
    <?php foreach ($orgs as $key => $o): ?>
    <button class="tabset__tab" type="button" role="tab" id="tab-<?= e($key) ?>" aria-controls="panel-<?= e($key) ?>" aria-selected="<?= $key === $active ? 'true' : 'false' ?>" tabindex="<?= $key === $active ? '0' : '-1' ?>" data-tab="<?= e($key) ?>">
      <?php if (str_starts_with($key, 'pays-de-')): ?><span class="tabset__dot pole-dot--<?= e(preg_replace('/^pays-de-/', '', $key)) ?>" aria-hidden="true"></span><?php endif ?>
      <?= e(Ui::poleShort($o['name'])) ?> <span class="tabset__count"><?= array_sum(array_map(static fn (array $l): int => count($l['campaigns']), $o['lists'])) ?></span>
    </button>
    <?php endforeach ?>
  </div>
  <?php endif ?>
  <?php foreach ($orgs as $key => $o): ?>
  <section class="tabset__panel" id="panel-<?= e($key) ?>"<?= $multi ? ' role="tabpanel" aria-labelledby="tab-' . e($key) . '"' : '' ?> data-tab-panel="<?= e($key) ?>">
    <h2 class="tabset__heading"><?= e($o['name']) ?></h2>
    <?php foreach ($o['lists'] as $l): $n = $l['active']; $new = '/admin/newsletters/campagnes/nouvelle?liste=' . (int) $l['id']; $audience = App\Site\Orgs::AUDIENCES[$l['audience']]; ?>
    <div class="card card--flush group-card">
      <div class="group-card__head">
        <div>
          <h3 class="group-card__title"><?= e($audience) ?></h3>
          <p class="muted"><?= $l['sympa_address'] !== '' ? 'Liste Sympa ' . e($l['sympa_address']) . ' (abonnés gérés dans Sympa)' : $n . ' abonné' . ($n > 1 ? 's' : '') . ' actif' . ($n > 1 ? 's' : '') ?> · <?= $l['sender_email'] !== '' ? 'envoi depuis ' . e($l['sender_email']) : '<strong>adresse d\'expédition à régler dans E-mails</strong>' ?></p>
        </div>
        <a class="btn btn--primary" href="<?= $new ?>"><?= Ui::icon('plus') ?>Nouvelle lettre<span class="visually-hidden"> : <?= e($o['name'] . ', ' . $audience) ?></span></a>
      </div>
      <?php if ($l['campaigns'] === []): ?>
      <p class="group-card__empty">Aucune lettre pour cette liste pour l'instant.</p>
      <?php else: ?>
      <div class="table-wrap">
        <table class="table table--stack">
          <caption class="visually-hidden">Lettres : <?= e($o['name'] . ', ' . $audience) ?></caption>
          <thead><tr><th scope="col">Objet</th><th scope="col">Statut</th><th scope="col" class="num">Envoyés</th><th scope="col" class="num">Créée le</th><th scope="col" class="actions-col"><span class="visually-hidden">Actions</span></th></tr></thead>
          <tbody>
            <?php foreach ($l['campaigns'] as $c): $st = $c['status'] === 'queued' ? 'sending' : $c['status']; ?>
            <tr>
              <th scope="row"><a href="/admin/newsletters/campagnes/<?= (int) $c['id'] ?>"><?= e($c['subject']) ?></a></th>
              <td data-label="Statut"><span class="badge<?= $st === 'sent' ? ' badge--on' : ($st === 'draft' ? ' badge--off' : ' badge--warn') ?>"><?= e($labels[$c['status']]) ?></span></td>
              <td data-label="Envoyés" class="num"><?php if ($st === 'sending' && $c['total']): ?><progress class="progress" max="<?= (int) $c['total'] ?>" value="<?= (int) $c['sent'] ?>" aria-hidden="true"></progress> <?php endif ?><?= $c['total'] ? (int) $c['sent'] . ' / ' . (int) $c['total'] : '—' ?></td>
              <td data-label="Créée le" class="num"><?= e(Ui::date($c['created_at'])) ?></td>
              <td class="actions-col"><a class="btn" href="/admin/newsletters/campagnes/<?= (int) $c['id'] ?>"><?= $c['status'] === 'draft' ? Ui::icon('edit') . 'Continuer' : Ui::icon('eye') . 'Voir' ?><span class="visually-hidden"> <?= e($c['subject']) ?></span></a></td>
            </tr>
            <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php endif ?>
    </div>
    <?php endforeach ?>
  </section>
  <?php endforeach ?>
</div>
<?php endif ?>
