<?php
/**
 * Champ de formulaire admin.
 * @var string $name @var array $f (label, type, required, hint, options, empty, step) @var mixed $value @var ?string $error
 * @var list<array<string, mixed>> $poles (type pole) @var ?int $lockedPole
 */
$id = 'f-' . $name;
$describedBy = trim((!empty($f['hint']) ? "$id-hint " : '') . ($error ? "$id-error" : ''));
$aria = ($describedBy !== '' ? ' aria-describedby="' . $describedBy . '"' : '') . ($error ? ' aria-invalid="true"' : '');
$req = !empty($f['required']) ? ' required' : '';
$type = $f['type'];
?>
<div class="field<?= $type === 'checkbox' ? ' field--check' : '' ?><?= in_array($type, ['textarea', 'markdown'], true) ? ' field--wide' : '' ?>">
  <?php if ($type === 'checkbox'): ?>
  <label class="switch" for="<?= $id ?>">
    <input type="checkbox" id="<?= $id ?>" name="<?= e($name) ?>" value="1"<?= (int) $value === 1 ? ' checked' : '' ?><?= $aria ?>>
    <span class="switch__track" aria-hidden="true"></span>
    <span><?= e($f['label']) ?></span>
  </label>
  <?php else: ?>
  <label for="<?= $id ?>"><?= e($f['label']) ?><?= !empty($f['required']) ? ' <span class="req" aria-hidden="true">*</span>' : '' ?></label>
  <?php if ($type === 'textarea' || $type === 'markdown'): ?>
  <textarea id="<?= $id ?>" name="<?= e($name) ?>" rows="<?= $type === 'markdown' ? 12 : 4 ?>"<?= $req . $aria ?><?= $type === 'markdown' ? ' class="mono"' : '' ?>><?= e((string) $value) ?></textarea>
  <?php if ($type === 'markdown'): ?><p class="field__hint">Mise en forme : <code>**gras**</code>, <code>*italique*</code>, <code>[texte](https://lien)</code>, listes avec <code>- </code>, intertitres avec <code>### </code>.</p><?php endif ?>
  <?php elseif ($type === 'select'): ?>
  <select id="<?= $id ?>" name="<?= e($name) ?>"<?= $req . $aria ?>>
    <?php foreach ($f['options'] as $optValue => $optLabel): ?>
    <option value="<?= e((string) $optValue) ?>"<?= (string) $value === (string) $optValue ? ' selected' : '' ?>><?= e($optLabel) ?></option>
    <?php endforeach ?>
  </select>
  <?php elseif ($type === 'pole'): ?>
  <?php if ($lockedPole !== null): ?>
  <input id="<?= $id ?>" value="<?= e(array_column($poles, 'name', 'id')[$lockedPole] ?? '') ?>" disabled>
  <?php else: ?>
  <select id="<?= $id ?>" name="<?= e($name) ?>"<?= $req . $aria ?>>
    <option value=""><?= e($f['empty'] ?? 'Choisir un pôle') ?></option>
    <?php foreach ($poles as $p): ?>
    <option value="<?= (int) $p['id'] ?>"<?= (int) $value === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['name']) ?></option>
    <?php endforeach ?>
  </select>
  <?php endif ?>
  <?php elseif ($type === 'pdf' || $type === 'image'): ?>
  <?php if ($value): ?>
  <p class="file-current">
    <?php if ($type === 'image'): ?><img src="<?= e((string) $value) ?>" alt="" height="48"><?php endif ?>
    <a href="<?= e((string) $value) ?>" target="_blank" rel="noopener">Fichier actuel</a> · envoyer un autre fichier le remplace.
  </p>
  <?php endif ?>
  <input type="file" id="<?= $id ?>" name="<?= e($name) ?>" accept="<?= $type === 'pdf' ? 'application/pdf' : 'image/png,image/jpeg,image/webp' ?>"<?= !$value && !empty($f['required']) ? ' required' : '' ?><?= $aria ?>>
  <?php else: ?>
  <input id="<?= $id ?>" name="<?= e($name) ?>" type="<?= e($type) ?>" value="<?= e((string) $value) ?>"<?= isset($f['step']) ? ' step="' . e($f['step']) . '"' : '' ?><?= $type === 'email' ? ' autocomplete="off"' : '' ?><?= $req . $aria ?>>
  <?php endif ?>
  <?php endif ?>
  <?php if (!empty($f['hint'])): ?><p class="field__hint" id="<?= $id ?>-hint"><?= e($f['hint']) ?></p><?php endif ?>
  <?php if ($error): ?><p class="field__error" id="<?= $id ?>-error">Erreur : <?= e($error) ?></p><?php endif ?>
</div>
