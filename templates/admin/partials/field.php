<?php
/**
 * Champ de formulaire admin.
 * @var string $name @var array $f (label, type, required, hint, options, empty, step) @var mixed $value @var ?string $error
 * @var list<array<string, mixed>> $poles (type pole) @var ?int $lockedPole @var ?string $formId (champ placé hors du formulaire)
 */
$id = 'f-' . $name;
$type = $f['type'];
$todo = is_string($value) && str_contains($value, '__À_REMPLIR__');
$hint = (string) ($f['hint'] ?? '');
if ($todo) {
    $hint = trim('À compléter avant la mise en ligne : remplacez __À_REMPLIR__ par la vraie valeur. ' . $hint);
}
$describedBy = trim(($type === 'address' ? "$id-geo " : '') . ($hint !== '' ? "$id-hint " : '') . ($error ? "$id-error" : ''));
$aria = ($describedBy !== '' ? ' aria-describedby="' . $describedBy . '"' : '') . ($error ? ' aria-invalid="true"' : '');
$req = !empty($f['required']) ? ' required' : '';
$form = !empty($formId) ? ' form="' . e($formId) . '"' : '';
$classes = 'field' . ($type === 'checkbox' ? ' field--check' : '') . (in_array($type, ['textarea', 'markdown', 'image', 'portrait'], true) ? ' field--wide' : '') . ($todo ? ' field--todo' : '');
?>
<div class="<?= $classes ?>">
  <?php if ($type === 'checkbox'): ?>
  <label class="switch" for="<?= $id ?>">
    <input type="checkbox" id="<?= $id ?>" name="<?= e($name) ?>" value="1"<?= (int) $value === 1 ? ' checked' : '' ?><?= $aria . $form ?>>
    <span class="switch__track" aria-hidden="true"></span>
    <span class="switch__text"><?= e($f['label']) ?></span>
  </label>
  <?php else: ?>
  <label for="<?= $id ?>" id="<?= $id ?>-label"><?= e($f['label']) ?><?= !empty($f['required']) ? ' <span class="req">(obligatoire)</span>' : '' ?></label>
  <?php if ($type === 'textarea' || $type === 'markdown'): ?>
  <textarea id="<?= $id ?>" name="<?= e($name) ?>" rows="<?= $type === 'markdown' ? 10 : 4 ?>"<?= $req . $aria . $form ?><?= $type === 'markdown' ? ' data-rte="full" data-rte-html="' . e(App\Admin\TextFields::editorHtml((string) $value)) . '"' : '' ?>><?= e((string) $value) ?></textarea>
  <?php elseif ($type === 'select'): ?>
  <select id="<?= $id ?>" name="<?= e($name) ?>"<?= $req . $aria . $form ?>>
    <?php foreach ($f['options'] as $optValue => $optLabel): ?>
    <option value="<?= e((string) $optValue) ?>"<?= (string) $value === (string) $optValue ? ' selected' : '' ?>><?= e($optLabel) ?></option>
    <?php endforeach ?>
  </select>
  <?php elseif ($type === 'pole'): ?>
  <?php if ($lockedPole !== null): ?>
  <input id="<?= $id ?>" value="<?= e(array_column($poles, 'name', 'id')[$lockedPole] ?? '') ?>" disabled>
  <?php else: ?>
  <select id="<?= $id ?>" name="<?= e($name) ?>"<?= $req . $aria . $form ?>>
    <option value=""><?= e($f['empty'] ?? 'Choisir un pôle') ?></option>
    <?php foreach ($poles as $p): ?>
    <option value="<?= (int) $p['id'] ?>"<?= (int) $value === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
    <?php endforeach ?>
  </select>
  <?php endif ?>
  <?php elseif ($type === 'image'): ?>
  <?php
    // Logos déjà présents sur le site (médiathèque), hors logos de la charte ESS ; ou envoi d'un nouveau fichier.
    $logos = array_filter(App\Admin\MediaController::library(), static fn (array $i): bool => $i['kind'] === 'logo' && !str_starts_with($i['src'], '/assets/img/logo-'));
  ?>
  <div class="photo-pick photo-pick--logo" data-photo-pick>
    <div class="photo-pick__current">
      <img src="<?= e((string) $value) ?>" alt="" width="160" height="80" data-photo-preview<?= $value ? '' : ' hidden' ?>>
      <p data-photo-label><?= $value ? 'Logo actuel' : 'Aucun logo' ?></p>
    </div>
    <details class="photo-pick__more">
      <summary class="btn"><?= App\Admin\Ui::icon('image') ?>Choisir dans la médiathèque</summary>
      <div class="photo-grid photo-grid--logos">
        <?php foreach ($logos as $logo): $pid = $id . '-' . substr(md5($logo['src']), 0, 8); ?>
        <label class="photo-choice" for="<?= $pid ?>">
          <input type="radio" id="<?= $pid ?>" name="<?= e($name) ?>_pick" value="<?= e($logo['src']) ?>"<?= $value === $logo['src'] ? ' checked' : '' ?> data-src="<?= e($logo['src']) ?>" data-label="<?= e($logo['label']) ?>"<?= $form ?>>
          <img src="<?= e($logo['src']) ?>" alt="" width="<?= $logo['width'] ?>" height="<?= $logo['height'] ?>" loading="lazy" decoding="async">
          <span class="photo-choice__name"><?= e($logo['label']) ?></span>
        </label>
        <?php endforeach ?>
      </div>
    </details>
    <p class="field__hint" id="<?= $id ?>-upload">Ou envoyez un nouveau logo (PNG, JPG ou WebP) : il rejoindra la médiathèque.</p>
    <input type="file" id="<?= $id ?>" name="<?= e($name) ?>" accept="image/png,image/jpeg,image/webp" aria-describedby="<?= $id ?>-upload"<?= $form ?>>
  </div>
  <?php elseif ($type === 'portrait'): ?>
  <div class="photo-pick" data-photo-pick>
    <div class="photo-pick__current">
      <img class="photo-pick__portrait" src="<?= e((string) $value) ?>" alt="" width="96" height="96" data-photo-preview<?= $value ? '' : ' hidden' ?>>
      <p data-photo-label><?= $value ? 'Portrait actuel' : 'Aucun portrait' ?></p>
    </div>
    <input type="file" id="<?= $id ?>" name="<?= e($name) ?>" accept="image/png,image/jpeg,image/webp"<?= $aria . $form ?>>
    <?php if ($value): ?>
    <label class="photo-pick__remove" for="<?= $id ?>-remove"><input type="checkbox" id="<?= $id ?>-remove" name="<?= e($name) ?>_remove" value="1"<?= $form ?>> Retirer le portrait</label>
    <?php endif ?>
  </div>
  <?php elseif ($type === 'address'): ?>
  <?php
    // État de la position sur la carte, d'après la fiche enregistrée ; admin.js le met à jour pendant la saisie.
    $onMap = ($values['latitude'] ?? null) !== null && ($values['latitude'] ?? '') !== '';
    [$geoClass, $geoText] = match (true) {
        $onMap => ['ok', 'Sur la carte : l\'adresse a été trouvée.'],
        (string) $value !== '' => ['off', 'Pas sur la carte : adresse introuvable. Corrigez-la en choisissant une suggestion.'],
        default => ['', 'Sans adresse, l\'adhérent figure dans la liste mais pas sur la carte.'],
    };
  ?>
  <div class="combo" data-address>
    <input id="<?= $id ?>" name="<?= e($name) ?>" type="text" value="<?= e((string) $value) ?>" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="<?= $id ?>-list" data-address-input<?= $req . $aria . $form ?>>
    <ul class="combo__list" id="<?= $id ?>-list" role="listbox" aria-label="Adresses suggérées" hidden data-address-list></ul>
  </div>
  <p class="geo-status<?= $geoClass !== '' ? ' geo-status--' . $geoClass : '' ?>" id="<?= $id ?>-geo" aria-live="polite" data-address-status><?= e($geoText) ?></p>
  <?php elseif ($type === 'pdf'): ?>
  <?php if ($value): ?>
  <p class="file-current">
    <a href="<?= e((string) $value) ?>" target="_blank" rel="noopener">Ouvrir le fichier actuel<span class="visually-hidden"> (nouvel onglet)</span></a>
    <span class="muted">Envoyer un autre fichier le remplace.</span>
  </p>
  <?php endif ?>
  <input type="file" id="<?= $id ?>" name="<?= e($name) ?>" accept="application/pdf"<?= !$value && !empty($f['required']) ? ' required' : '' ?><?= $aria . $form ?>>
  <?php else: ?>
  <input id="<?= $id ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" value="<?= e((string) $value) ?>"<?= isset($f['step']) ? ' step="' . e($f['step']) . '"' : '' ?><?= $type === 'email' ? ' autocomplete="off"' : '' ?><?= $req . $aria . $form ?>>
  <?php endif ?>
  <?php endif ?>
  <?php if ($hint !== ''): ?><p class="field__hint" id="<?= $id ?>-hint"><?= e($hint) ?></p><?php endif ?>
  <?php if ($error): ?><p class="field__error" id="<?= $id ?>-error">Erreur : <?= e($error) ?></p><?php endif ?>
</div>
