<?php
/**
 * Choix d'une destination : page ou section du site dans une liste, ou adresse extérieure / e-mail.
 * @var string $lid préfixe d'identifiant @var string $lname préfixe du nom (« units[3][link] ») @var string $url adresse actuelle
 * @var string $llabel libellé du champ @var ?string $lbase adresse de la page, pour les liens « #ancre »
 */
use App\Admin\Links;

[$target, $free] = $url === '' ? ['', ''] : Links::split($url, $lbase ?? '');
$isFree = in_array($target, [Links::EXTERNAL, Links::MAIL], true);
?>
<div class="link-field" data-link-field>
  <div class="field">
    <label for="<?= e($lid) ?>"><?= e($llabel) ?></label>
    <select id="<?= e($lid) ?>"<?= $lname !== '' ? ' name="' . e($lname) . '[target]"' : '' ?> data-link-target>
      <?php if ($url === ''): ?><option value="">Choisir une page…</option><?php endif ?>
      <?php foreach (Links::targets() as $group => $options): ?>
      <optgroup label="<?= e($group) ?>">
        <?php foreach ($options as $value => $text): ?>
        <option value="<?= e($value) ?>"<?= $target === $value ? ' selected' : '' ?>><?= e($text) ?></option>
        <?php endforeach ?>
      </optgroup>
      <?php endforeach ?>
      <optgroup label="Ailleurs">
        <option value="<?= Links::EXTERNAL ?>"<?= $target === Links::EXTERNAL ? ' selected' : '' ?>>Un autre site internet</option>
        <option value="<?= Links::MAIL ?>"<?= $target === Links::MAIL ? ' selected' : '' ?>>Une adresse e-mail</option>
      </optgroup>
    </select>
  </div>
  <div class="field link-field__free" data-link-free<?= $isFree ? '' : ' data-off' ?>>
    <label for="<?= e($lid) ?>-free" data-link-free-label><?= $target === Links::MAIL ? 'Adresse e-mail' : 'Adresse du site' ?></label>
    <input id="<?= e($lid) ?>-free"<?= $lname !== '' ? ' name="' . e($lname) . '[free]"' : '' ?> value="<?= e($free) ?>" autocomplete="off" placeholder="<?= $target === Links::MAIL ? 'nom@exemple.fr' : 'www.exemple.fr' ?>" data-link-free-input>
  </div>
</div>
