<?php use App\Core\Csrf;
$draft = $c['status'] === 'draft';
$labels = ['draft' => 'Brouillon', 'queued' => 'En file', 'sending' => 'En cours d\'envoi', 'sent' => 'Envoyée'];
?>
<div class="page-title">
  <p class="page-title__kicker"><a href="/admin/newsletters">Newsletters</a></p>
  <h1><?= e($id ? $c['subject'] : 'Nouvelle lettre') ?></h1>
  <?php if ($id): ?><span class="badge<?= $c['status'] === 'sent' ? ' badge--on' : '' ?>"><?= e($labels[$c['status']]) ?></span><?php endif ?>
</div>

<?php if ($id && !$draft): ?>
<div class="panel">
  <h2>Suivi de l'envoi</h2>
  <ul class="kpis kpis--compact" role="list">
    <li class="kpi"><span class="kpi__value"><?= (int) ($progress['sent'] ?? 0) ?></span><span class="kpi__label">envoyés</span></li>
    <li class="kpi"><span class="kpi__value"><?= (int) ($progress['pending'] ?? 0) ?></span><span class="kpi__label">en attente</span></li>
    <li class="kpi"><span class="kpi__value"><?= (int) ($progress['failed'] ?? 0) ?></span><span class="kpi__label">échecs ou désinscrits</span></li>
  </ul>
  <p><a href="/admin/newsletters/campagnes/<?= (int) $id ?>/apercu" target="_blank" rel="noopener">Voir la lettre envoyée</a></p>
</div>
<?php else: ?>
<form class="panel form-grid" method="post" action="/admin/newsletters/campagnes<?= $id ? '/' . (int) $id : '' ?>" novalidate>
  <?= Csrf::field() ?>
  <div class="field field--wide"><label for="c-subject">Objet de l'e-mail</label><input id="c-subject" name="subject" required maxlength="140" value="<?= e($c['subject']) ?>"></div>
  <?php if (count($poles) > 1): ?>
  <div class="field"><label for="c-pole">Lettre du pôle (destinataires et expéditeur)</label><select id="c-pole" name="pole"><?php foreach ($poles as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $c['pole_id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?> · <?= e($p['email']) ?></option><?php endforeach ?></select></div>
  <?php endif ?>
  <div class="field"><label for="c-template">Modèle</label><select id="c-template" name="template_id"><option value="">Aucun</option><?php foreach ($templates as $t): ?><option value="<?= (int) $t['id'] ?>"<?= (int) $c['template_id'] === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?> (<?= e($t['pole']) ?>)</option><?php endforeach ?></select></div>
  <div class="field field--wide">
    <label for="c-body">Texte de la lettre</label>
    <textarea class="mono" id="c-body" name="body" rows="18" required aria-describedby="c-body-hint"><?= e($c['body']) ?></textarea>
    <p class="field__hint" id="c-body-hint"><code>### Intertitre</code>, <code>**gras**</code>, <code>[lien](https://…)</code>, listes <code>- </code>, bouton <code>→ [Texte](https://…)</code>. Le logo, les coordonnées du pôle et le lien de désinscription sont ajoutés automatiquement.</p>
  </div>
  <div class="form-actions"><button class="button" type="submit">Enregistrer le brouillon</button></div>
</form>

<?php if ($id): ?>
<div class="columns">
  <div class="panel">
    <h2>1. Vérifier</h2>
    <p><a class="button button--secondary" href="/admin/newsletters/campagnes/<?= (int) $id ?>/apercu" target="_blank" rel="noopener">Ouvrir l'aperçu</a></p>
    <form method="post" action="/admin/newsletters/campagnes/<?= (int) $id ?>/test" novalidate>
      <?= Csrf::field() ?>
      <div class="field"><label for="c-test">Envoyer un test à</label><input id="c-test" name="to" type="email" required autocomplete="email"></div>
      <button class="button button--secondary" type="submit">Envoyer le test</button>
    </form>
  </div>
  <form class="panel" method="post" action="/admin/newsletters/campagnes/<?= (int) $id ?>/envoi" novalidate>
    <?= Csrf::field() ?>
    <h2>2. Envoyer</h2>
    <p><strong><?= (int) $recipients ?></strong> abonné<?= $recipients > 1 ? 's' : '' ?> actif<?= $recipients > 1 ? 's recevront' : ' recevra' ?> cette lettre. L'envoi part par lots et ne peut plus être annulé.</p>
    <div class="field field--check">
      <label class="switch" for="c-confirm">
        <input type="checkbox" id="c-confirm" name="confirm" value="1" required>
        <span class="switch__track" aria-hidden="true"></span>
        <span>J'ai vérifié l'aperçu et le test.</span>
      </label>
    </div>
    <button class="button" type="submit"<?= $recipients === 0 ? ' disabled' : '' ?>>Envoyer la lettre</button>
  </form>
</div>
<form method="post" action="/admin/newsletters/campagnes/<?= (int) $id ?>/suppression" data-confirm="Supprimer ce brouillon ?"><?= Csrf::field() ?><button class="button button--secondary" type="submit">Supprimer le brouillon</button></form>
<?php endif ?>
<?php endif ?>
