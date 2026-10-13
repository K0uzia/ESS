<?php
use App\Admin\TextFields;
use App\Admin\Ui;
use App\Core\Csrf;

$action = '/admin/pages/' . e($key) . '/sections/' . e($s['key']);
$headings = count(array_filter($units, static fn (array $u): bool => $u['type'] === 'heading'));
$groupName = ['frise' => 'Étape', 'blocs' => 'Bloc'][$kind] ?? 'Sous-partie';
$n = ['heading' => 0, 'card' => 0, 'quote' => 0, 'text' => 0, 'button' => 0];
$inGroup = false;
$todo = static fn (string $text): string => str_contains($text, '__À_REMPLIR__') ? ' field--todo' : '';
$current = $library[$s['image']] ?? null;
?>
<header class="page-head">
  <div class="page-head__text">
    <a class="back" href="/admin/pages/<?= e($key) ?>"><?= Ui::icon('back') ?><?= e($label) ?></a>
    <h1><?= e($title) ?></h1>
  </div>
</header>
<div class="card" data-modal-content data-modal-title="<?= e($title) ?>">
<form id="section-form" method="post" action="<?= $action ?>" enctype="multipart/form-data" novalidate>
  <?= Csrf::field() ?>
  <input type="hidden" name="sig" value="<?= e($sig) ?>">
  <div class="field<?= $todo($s['title']) ?>">
    <label for="s-title">Titre de la section <span class="req">(obligatoire)</span></label>
    <input id="s-title" name="title" value="<?= e($s['title']) ?>" required<?= isset($errors['title']) ? ' aria-invalid="true" aria-describedby="s-title-error"' : '' ?>>
    <?php if (isset($errors['title'])): ?><p class="field__error" id="s-title-error"><?= e($errors['title']) ?></p><?php endif ?>
  </div>

  <?php foreach ($units as $i => $u): $id = "u$i"; $name = "units[$i]"; ?>
  <?php if ($inGroup && !in_array($u['type'], ['text', 'button', 'table'], true)): $inGroup = false; ?></fieldset><?php endif ?>
  <?php switch ($u['type']):
    case 'heading': $n['heading']++; $inGroup = true; ?>
  <fieldset class="unit-group">
    <legend><?= e($groupName) ?> <?= $n['heading'] ?></legend>
    <div class="field<?= $todo($u['text']) ?>">
      <label for="<?= $id ?>">Titre</label>
      <input id="<?= $id ?>" name="<?= $name ?>[text]" value="<?= e($u['text']) ?>">
    </div>
    <?php break;
    case 'text': $n['text']++; ?>
    <div class="field<?= $todo($u['md']) ?>">
      <label for="<?= $id ?>" id="<?= $id ?>-label"><?= $inGroup ? 'Texte' : ($headings > 0 && $n['heading'] === 0 ? 'Introduction' : ($n['text'] > 1 ? 'Texte (suite)' : 'Texte')) ?></label>
      <textarea id="<?= $id ?>" name="<?= $name ?>[md]" rows="<?= max(4, min(14, substr_count($u['md'], "\n") + 2)) ?>" data-rte data-rte-html="<?= e(TextFields::editorHtml($u['md'])) ?>"><?= e($u['md']) ?></textarea>
      <?php if ($todo($u['md'])): ?><p class="field__hint">Remplacez __À_REMPLIR__ par la vraie information avant la mise en ligne.</p><?php endif ?>
    </div>
    <?php break;
    case 'button': $n['button']++; ?>
  <fieldset class="unit-group">
    <legend>Bouton</legend>
    <div class="form-grid form-grid--2">
      <div class="field">
        <label for="<?= $id ?>">Texte du bouton</label>
        <input id="<?= $id ?>" name="<?= $name ?>[text]" value="<?= e($u['text']) ?>">
      </div>
      <?php $lid = "$id-link"; $lname = "{$name}[link]"; $url = $u['url']; $llabel = 'Redirection'; $lbase = App\Admin\ContentController::publicUrl($key); require ROOT . '/templates/admin/partials/link.php'; ?>
    </div>
  </fieldset>
    <?php break;
    case 'card': $n['card']++; ?>
  <fieldset class="unit-group">
    <legend>Carte <?= $n['card'] ?></legend>
    <div class="field">
      <label for="<?= $id ?>-title">Titre</label>
      <input id="<?= $id ?>-title" name="<?= $name ?>[title]" value="<?= e($u['title']) ?>">
    </div>
    <div class="field">
      <label for="<?= $id ?>" id="<?= $id ?>-label">Texte</label>
      <textarea id="<?= $id ?>" name="<?= $name ?>[text]" rows="2" data-rte="inline" data-rte-html="<?= e(TextFields::editorHtml($u['text'])) ?>"><?= e($u['text']) ?></textarea>
    </div>
    <?php if ($u['url'] !== ''): $lid = "$id-link"; $lname = "{$name}[link]"; $url = $u['url']; $llabel = 'La carte mène vers'; $lbase = App\Admin\ContentController::publicUrl($key); require ROOT . '/templates/admin/partials/link.php'; endif ?>
  </fieldset>
    <?php break;
    case 'quote': $n['quote']++; ?>
  <fieldset class="unit-group">
    <legend>Témoignage <?= $n['quote'] ?></legend>
    <div class="field">
      <label for="<?= $id ?>" id="<?= $id ?>-label">Citation</label>
      <textarea id="<?= $id ?>" name="<?= $name ?>[text]" rows="3" data-rte="inline" data-rte-html="<?= e(TextFields::editorHtml($u['text'])) ?>"><?= e($u['text']) ?></textarea>
    </div>
    <div class="field">
      <label for="<?= $id ?>-author">Auteur</label>
      <input id="<?= $id ?>-author" name="<?= $name ?>[author]" value="<?= e($u['author']) ?>" aria-describedby="<?= $id ?>-author-hint">
      <p class="field__hint" id="<?= $id ?>-author-hint">Exemple : Marie, 34 ans</p>
    </div>
  </fieldset>
    <?php break;
    case 'table': $cols = count((array) $u['rows'][0]); ?>
    <div class="field">
      <p class="field__label" id="<?= $id ?>-label">Tableau</p>
      <div class="table-wrap">
        <table class="table-edit" aria-labelledby="<?= $id ?>-label">
          <?php foreach ($u['rows'] as $r => $row): if ($row === null) { continue; } ?>
          <tr>
            <?php foreach ($row as $c => $cell): ?>
            <td><input name="<?= $name ?>[cells][<?= $r ?>][<?= $c ?>]" value="<?= e($cell) ?>" aria-label="<?= $r === 0 ? 'En-tête' : 'Ligne ' . $r ?>, colonne <?= $c + 1 ?>"<?= $r === 0 ? ' class="table-edit__head"' : '' ?>></td>
            <?php endforeach ?>
          </tr>
          <?php endforeach ?>
        </table>
      </div>
    </div>
    <?php break;
    case 'component': $comp = Ui::component($u['raw']); ?>
  <div class="module-link">
    <?= Ui::icon('link') ?>
    <p>Ici s'affiche automatiquement <?= e($comp[0]) ?>.<?= $comp[1] ? ' Pour le modifier : « ' . e($comp[2]) . ' ».' : '' ?></p>
    <?php if ($comp[1]): ?><a class="btn" href="<?= e($comp[1]) ?>">Ouvrir « <?= e($comp[2]) ?> »<?= Ui::icon('arrow') ?></a><?php endif ?>
  </div>
    <?php break;
  endswitch ?>
  <?php endforeach ?>
  <?php if ($inGroup): ?></fieldset><?php endif ?>

  <?php if ($hasPhoto): ?>
  <fieldset class="unit-group photo-pick" data-photo-pick>
    <legend>Photo</legend>
    <div class="photo-pick__current">
      <img src="<?= e($current['src'] ?? '') ?>" alt="" width="<?= (int) ($current['width'] ?? 600) ?>" height="<?= (int) ($current['height'] ?? 400) ?>" data-photo-preview<?= $current === null ? ' hidden' : '' ?>>
      <p data-photo-label><?= e($current['label'] ?? 'Aucune photo') ?></p>
    </div>
    <details class="photo-pick__more">
      <summary class="btn"><?= Ui::icon('image') ?>Changer la photo</summary>
      <p class="field__hint">Une photo ne sert qu'à un seul endroit du site : celles déjà utilisées ailleurs sont grisées.</p>
      <div class="photo-grid">
        <?php foreach ($library as $value => $item): if ($item['kind'] !== 'photo') { continue; } ?>
        <?php
          $elsewhere = array_filter($item['uses'], static fn (array $use): bool => ($use['page'] ?? '') !== $key || ($use['section'] ?? '') !== $s['key']);
          $pid = 'p-' . preg_replace('/[^a-z0-9-]/', '-', (string) $value);
        ?>
        <label class="photo-choice" for="<?= $pid ?>">
          <input type="radio" id="<?= $pid ?>" name="image" value="<?= e((string) $value) ?>"<?= $s['image'] === $value ? ' checked' : '' ?><?= $elsewhere !== [] ? ' disabled' : '' ?> data-src="<?= e($item['src']) ?>" data-label="<?= e($item['label']) ?>">
          <img src="<?= e($item['src']) ?>" alt="" width="<?= $item['width'] ?>" height="<?= $item['height'] ?>" loading="lazy" decoding="async">
          <span class="photo-choice__name"><?= e($item['label']) ?></span>
          <?php if ($elsewhere !== []): ?><span class="photo-choice__use">Utilisée : <?= e(array_values($elsewhere)[0]['label']) ?></span><?php endif ?>
        </label>
        <?php endforeach ?>
        <label class="photo-choice photo-choice--none" for="p-none">
          <input type="radio" id="p-none" name="image" value=""<?= $s['image'] === '' ? ' checked' : '' ?> data-src="" data-label="Aucune photo">
          <span class="photo-choice__empty" aria-hidden="true"><?= Ui::icon('image') ?></span>
          <span class="photo-choice__name">Sans photo</span>
        </label>
      </div>
    </details>
    <details class="photo-pick__more photo-pick__upload"<?= isset($errors['photo']) ? ' open' : '' ?>>
      <summary class="btn btn--ghost"><?= Ui::icon('upload') ?>Envoyer une nouvelle photo</summary>
      <div class="field">
        <label for="s-photo">Photo (JPG, PNG ou WebP, 15 Mo au plus)</label>
        <input type="file" id="s-photo" name="photo_upload" accept="image/jpeg,image/png,image/webp"<?= isset($errors['photo']) ? ' aria-invalid="true" aria-describedby="s-photo-error"' : '' ?>>
        <?php if (isset($errors['photo'])): ?><p class="field__error" id="s-photo-error"><?= e($errors['photo']) ?></p><?php endif ?>
      </div>
      <div class="field">
        <label class="switch" for="s-rights">
          <input type="checkbox" id="s-rights" name="rights" value="1">
          <span class="switch__track" aria-hidden="true"></span>
          <span class="switch__text">Cette photo est réelle, et j'ai l'accord des personnes reconnaissables et du photographe pour la publier.</span>
        </label>
      </div>
      <p class="field__hint">Elle remplace la photo de la section et rejoint la médiathèque.</p>
    </details>
  </fieldset>
  <?php endif ?>
</form>
<form id="section-delete" method="post" action="<?= $action ?>/suppression" hidden data-confirm="Supprimer la section « <?= e($s['title'] !== '' ? $s['title'] : 'sans titre') ?> » ? Son texte sera perdu.">
  <?= Csrf::field() ?>
</form>
<div class="form-foot" data-modal-foot>
  <button class="btn btn--primary" type="submit" form="section-form">Enregistrer</button>
  <a class="btn" href="/admin/pages/<?= e($key) ?>" data-modal-close>Annuler</a>
  <a class="btn btn--ghost" href="<?= e($viewUrl) ?>" target="_blank" rel="noopener"><?= Ui::icon('eye') ?>Voir sur le site<span class="visually-hidden"> (nouvel onglet)</span></a>
  <button class="btn btn--danger form-foot__end" type="submit" form="section-delete"><?= Ui::icon('trash') ?>Supprimer<span class="visually-hidden"> la section</span></button>
</div>
</div>
