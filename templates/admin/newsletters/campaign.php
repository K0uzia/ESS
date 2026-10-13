<?php
use App\Admin\Ui;
use App\Core\Csrf;

$draft = $c['status'] === 'draft';
$steps = ['rediger' => 'Rédiger', 'verifier' => 'Vérifier', 'envoyer' => 'Envoyer'];
$step = $id && isset($steps[$_GET['etape'] ?? '']) ? (string) $_GET['etape'] : 'rediger';
$base = $id ? '/admin/newsletters/campagnes/' . (int) $id : '';
$poleName = array_column($poles, 'name', 'id')[$c['pole_id']] ?? '';
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/newsletters"><?= Ui::icon('back') ?>Newsletters</a>
    <h1><?= e($id ? $c['subject'] : 'Nouvelle lettre') ?></h1>
  </div>
  <?php if ($id): ?>
  <div class="page-head__actions"><a class="btn" href="<?= $base ?>/apercu" data-preview><?= Ui::icon('eye') ?>Aperçu</a></div>
  <?php endif ?>
</header>

<?php if ($id && !$draft): ?>
<section class="card" aria-labelledby="suivi-titre">
  <h2 class="card__title" id="suivi-titre">Lettre <?= $c['status'] === 'sent' ? 'envoyée' : 'en cours d\'envoi' ?></h2>
  <dl class="meta-list">
    <dt>Pôle</dt><dd><?= e($poleName ?: '—') ?></dd>
    <dt>Envoyés</dt><dd><?= (int) ($progress['sent'] ?? 0) ?></dd>
    <dt>En attente</dt><dd><?= (int) ($progress['pending'] ?? 0) ?></dd>
    <dt>Échecs ou désinscrits</dt><dd><?= (int) ($progress['failed'] ?? 0) ?></dd>
  </dl>
</section>
<?php else: ?>
<ol class="steps" aria-label="Étapes">
  <?php foreach ($steps as $sk => $sl): ?>
  <li>
    <?php if ($id): ?>
    <a href="<?= $base ?><?= $sk === 'rediger' ? '' : '?etape=' . $sk ?>"<?= $step === $sk ? ' aria-current="step"' : '' ?>><span class="steps__label"><?= e($sl) ?></span></a>
    <?php else: ?>
    <span<?= $sk === 'rediger' ? ' aria-current="step"' : ' aria-disabled="true"' ?>><span class="steps__label"><?= e($sl) ?></span></span>
    <?php endif ?>
  </li>
  <?php endforeach ?>
</ol>

<?php if ($step === 'rediger'): ?>
<form class="card" method="post" action="/admin/newsletters/campagnes<?= $id ? '/' . (int) $id : '' ?>" novalidate>
  <?= Csrf::field() ?>
  <div class="form-grid">
    <div class="field field--wide"><label for="c-subject">Objet de l'e-mail <span class="req">(obligatoire)</span></label><input id="c-subject" name="subject" required maxlength="140" value="<?= e($c['subject']) ?>"></div>
    <?php if (count($poles) > 1): ?>
    <div class="field"><label for="c-pole">Envoyée aux abonnés du pôle</label><select id="c-pole" name="pole"><?php foreach ($poles as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $c['pole_id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach ?></select></div>
    <?php endif ?>
    <div class="field"><label for="c-template">Modèle (facultatif)</label><select id="c-template" name="template_id"><option value="">Aucun</option><?php foreach ($templates as $t): ?><option value="<?= (int) $t['id'] ?>"<?= (int) $c['template_id'] === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?> (<?= e(Ui::poleShort($t['pole'])) ?>)</option><?php endforeach ?></select></div>
    <div class="field field--wide">
      <label for="c-body" id="c-body-label">Texte de la lettre <span class="req">(obligatoire)</span></label>
      <textarea id="c-body" name="body" rows="16" required aria-describedby="c-body-hint" data-rte="full" data-rte-html="<?= e(App\Admin\TextFields::editorHtml((string) $c['body'])) ?>"><?= e($c['body']) ?></textarea>
      <p class="field__hint" id="c-body-hint">Le logo, les coordonnées du pôle et le lien de désinscription sont ajoutés automatiquement.</p>
    </div>
  </div>
  <div class="form-foot">
    <button class="btn btn--primary" type="submit">Enregistrer et continuer<?= Ui::icon('arrow') ?></button>
    <?php if ($id): ?><button class="btn btn--danger form-foot__end" type="submit" form="supprimer-lettre"><?= Ui::icon('trash') ?>Supprimer le brouillon</button><?php endif ?>
  </div>
</form>
<?php if ($id): ?>
<form id="supprimer-lettre" method="post" action="<?= $base ?>/suppression" hidden data-confirm="Supprimer ce brouillon ?"><?= Csrf::field() ?></form>
<?php endif ?>

<?php elseif ($step === 'verifier'): ?>
<section class="card" aria-labelledby="verif-titre">
  <h2 class="card__title" id="verif-titre">Vérifier la lettre</h2>
  <p>Relisez l'aperçu, puis envoyez-vous un test pour voir la lettre dans une vraie boîte de réception.</p>
  <p><a class="btn" href="<?= $base ?>/apercu" data-preview><?= Ui::icon('eye') ?>Voir l'aperçu</a></p>
  <form class="add-section" method="post" action="<?= $base ?>/test" novalidate>
    <?= Csrf::field() ?>
    <div class="field"><label for="c-test">Envoyer un test à</label><input id="c-test" name="to" type="email" required autocomplete="email"></div>
    <button class="btn" type="submit"><?= Ui::icon('send') ?>Envoyer le test</button>
  </form>
  <div class="form-foot">
    <a class="btn" href="<?= $base ?>"><?= Ui::icon('back') ?>Modifier le texte</a>
    <a class="btn btn--primary" href="<?= $base ?>?etape=envoyer">Continuer<?= Ui::icon('arrow') ?></a>
  </div>
</section>

<?php else: ?>
<form class="card" method="post" action="<?= $base ?>/envoi" novalidate aria-labelledby="envoi-titre">
  <?= Csrf::field() ?>
  <h2 class="card__title" id="envoi-titre">Envoyer la lettre</h2>
  <p><strong><?= (int) $recipients ?></strong> abonné<?= $recipients > 1 ? 's' : '' ?> actif<?= $recipients > 1 ? 's' : '' ?> <?= $poleName ? 'du ' . e($poleName) : '' ?> <?= $recipients > 1 ? 'recevront' : 'recevra' ?> cette lettre. L'envoi part par lots et ne peut plus être annulé.</p>
  <?php if ($recipients === 0): ?>
  <div class="notice notice--warning"><?= Ui::icon('alert') ?><p>Aucun abonné actif pour ce pôle : l'envoi est impossible.</p></div>
  <?php else: ?>
  <div class="field">
    <label class="switch" for="c-confirm">
      <input type="checkbox" id="c-confirm" name="confirm" value="1" required>
      <span class="switch__track" aria-hidden="true"></span>
      <span class="switch__text">J'ai vérifié l'aperçu et le test.</span>
    </label>
  </div>
  <?php endif ?>
  <div class="form-foot">
    <a class="btn" href="<?= $base ?>?etape=verifier"><?= Ui::icon('back') ?>Retour</a>
    <button class="btn btn--primary" type="submit"<?= $recipients === 0 ? ' disabled' : '' ?>><?= Ui::icon('send') ?>Envoyer maintenant</button>
  </div>
</form>
<?php endif ?>
<?php endif ?>
