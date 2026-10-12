<?php
use App\Admin\ContentController;
use App\Core\Csrf;

$meta = $page['meta'];
$publicPath = array_search($key, App\Site\SiteController::PAGES, true);
$viewUrl = $publicPath !== false ? $publicPath : '/les-poles/#' . $key;
?>
<div class="page-title">
  <p class="page-title__kicker"><a href="/admin/pages">Pages</a></p>
  <h1><?= e(preg_replace('/\s*\([^)]*\)$/', '', $label)) ?></h1>
  <a class="button button--secondary" href="<?= e($viewUrl) ?>" target="_blank" rel="noopener">Voir la page</a>
</div>

<form method="post" action="/admin/pages/<?= e($key) ?>" novalidate>
  <?= Csrf::field() ?>
  <?php if (!$isFragment): ?>
  <section class="panel" aria-labelledby="meta-titre">
    <h2 id="meta-titre">En-tête et référencement</h2>
    <div class="form-grid">
      <div class="field">
        <label for="meta-h1">Titre affiché (h1)</label>
        <input id="meta-h1" name="meta[h1]" value="<?= e($meta['h1'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="meta-title">Titre pour Google (moins de 60 caractères)</label>
        <input id="meta-title" name="meta[title]" maxlength="70" value="<?= e($meta['title'] ?? '') ?>" data-count="60">
      </div>
      <div class="field field--wide">
        <label for="meta-intro">Chapeau sous le titre</label>
        <textarea id="meta-intro" name="meta[intro]" rows="2"><?= e($meta['intro'] ?? '') ?></textarea>
      </div>
      <div class="field field--wide">
        <label for="meta-description">Description pour Google (140 à 160 caractères)</label>
        <textarea id="meta-description" name="meta[description]" rows="2" maxlength="200" data-count="160"><?= e($meta['description'] ?? '') ?></textarea>
      </div>
    </div>
  </section>
  <?php endif ?>

  <h2 class="block-title">Sections</h2>
  <?php foreach ($page['sections'] as $i => $s): ?>
  <?php
    $variants = array_filter(explode(' ', $s['variant']));
    $layout = (string) (array_values(array_intersect($variants, array_keys(ContentController::LAYOUTS)))[0] ?? '');
    $tone = (string) (array_values(array_intersect($variants, ['fond', 'sombre']))[0] ?? '');
    $k = e($s['key']);
    $isComponent = (bool) preg_match('/^@[a-z_-]+(:[a-z0-9_-]+)?$/m', $s['body']);
  ?>
  <details class="panel section-edit" id="section-<?= $k ?>"<?= $i === 0 ? ' open' : '' ?>>
    <summary>
      <span class="section-edit__pos"><?= $i + 1 ?></span>
      <span class="section-edit__title"><?= e($s['title'] !== '' ? $s['title'] : '(sans titre)') ?></span>
      <span class="badge"><?= e(ContentController::LAYOUTS[$layout] ?? 'Texte simple') ?></span>
      <?php if ($isComponent): ?><span class="badge badge--on">Bloc dynamique</span><?php endif ?>
    </summary>
    <div class="form-grid">
      <div class="field">
        <label for="s-<?= $k ?>-title">Titre de la section</label>
        <input id="s-<?= $k ?>-title" name="sections[<?= $k ?>][title]" value="<?= e($s['title']) ?>">
      </div>
      <div class="field">
        <label for="s-<?= $k ?>-position">Position</label>
        <input id="s-<?= $k ?>-position" name="sections[<?= $k ?>][position]" type="number" min="1" value="<?= $i + 1 ?>">
      </div>
      <div class="field">
        <label for="s-<?= $k ?>-layout">Mise en page</label>
        <select id="s-<?= $k ?>-layout" name="sections[<?= $k ?>][layout]">
          <?php foreach (ContentController::LAYOUTS as $v => $l): ?>
          <option value="<?= e($v) ?>"<?= $layout === $v ? ' selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="field">
        <label for="s-<?= $k ?>-tone">Fond</label>
        <select id="s-<?= $k ?>-tone" name="sections[<?= $k ?>][tone]">
          <?php foreach (ContentController::TONES as $v => $l): ?>
          <option value="<?= e($v) ?>"<?= $tone === $v ? ' selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="field">
        <label for="s-<?= $k ?>-image">Photo (mise en page « Texte et photo »)</label>
        <select id="s-<?= $k ?>-image" name="sections[<?= $k ?>][image]">
          <?php foreach ($photos as $v => $l): ?>
          <option value="<?= e($v) ?>"<?= $s['image'] === $v ? ' selected' : '' ?>><?= e($l) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="field field--check">
        <label class="switch" for="s-<?= $k ?>-inverse">
          <input type="checkbox" id="s-<?= $k ?>-inverse" name="sections[<?= $k ?>][inverse]" value="1"<?= in_array('inverse', $variants, true) ? ' checked' : '' ?>>
          <span class="switch__track" aria-hidden="true"></span>
          <span>Photo à gauche</span>
        </label>
      </div>
      <div class="field field--wide">
        <label for="s-<?= $k ?>-body">Texte</label>
        <textarea class="mono" id="s-<?= $k ?>-body" name="sections[<?= $k ?>][body]" rows="<?= max(6, min(28, substr_count($s['body'], "\n") + 3)) ?>" aria-describedby="aide-markdown"><?= e($s['body']) ?></textarea>
      </div>
      <div class="field field--check field--wide">
        <label class="switch switch--danger" for="s-<?= $k ?>-delete">
          <input type="checkbox" id="s-<?= $k ?>-delete" name="sections[<?= $k ?>][delete]" value="1">
          <span class="switch__track" aria-hidden="true"></span>
          <span>Supprimer cette section à l'enregistrement</span>
        </label>
      </div>
    </div>
  </details>
  <?php endforeach ?>

  <div class="sticky-actions">
    <button class="button" type="submit">Enregistrer la page</button>
    <a class="button button--secondary" href="<?= e($viewUrl) ?>" target="_blank" rel="noopener">Voir la page</a>
  </div>
</form>

<form class="panel inline-form" method="post" action="/admin/pages/<?= e($key) ?>/sections">
  <?= Csrf::field() ?>
  <div class="field">
    <label for="new-section">Ajouter une section</label>
    <input id="new-section" name="title" placeholder="Titre de la nouvelle section" required>
  </div>
  <button class="button button--secondary" type="submit">Ajouter</button>
</form>

<aside class="panel help" id="aide-markdown" aria-labelledby="aide-titre">
  <h2 id="aide-titre">Aide à la mise en forme</h2>
  <dl>
    <dt><code>**gras**</code>, <code>*italique*</code></dt><dd>Mise en valeur.</dd>
    <dt><code>[texte du lien](https://…)</code></dt><dd>Lien. Pour une autre page du site : <code>[Agir](/agir/)</code> ou une section : <code>[Emploi](/agir/#emploi)</code>.</dd>
    <dt><code>→ [Texte](lien)</code></dt><dd>Bouton, seul sur sa ligne.</dd>
    <dt><code>- élément</code> / <code>1. élément</code></dt><dd>Liste à puces ou numérotée. Une liste dont chaque élément commence par un nombre s'affiche en chiffres clés.</dd>
    <dt><code>### Intertitre</code></dt><dd>Sous-partie. En mise en page « Blocs », chaque sous-partie devient une carte ; <code>### !ampoule Titre</code> ajoute un pictogramme.</dd>
    <dt><code>&gt; « Citation » (Prénom)</code></dt><dd>Témoignage avec les guillemets de la charte.</dd>
    <dt><code>@equipe</code>, <code>@documents:fiche_pratique</code>…</dt><dd>Bloc dynamique tiré des modules (à ne pas modifier sans raison).</dd>
  </dl>
  <p>Pictogrammes : ampoule, avionenpapier, bulles, calendrier, camembert, cible, client, engrenage, enveloppe, fleche1, fleche2, horloge, liste, localisation, loupe, megaphone, reseau.</p>
</aside>
