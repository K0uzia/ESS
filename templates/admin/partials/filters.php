<?php
/**
 * Barre de filtres instantanés (admin.js, initFilters). Masquée sans JavaScript : la liste complète reste lisible.
 * @var array<string, array{0: string, 1: array<string, string>}> $filters clé => [libellé, [valeur => libellé]]
 * @var string $searchLabel @var array{0: string, 1: string} $countWords @var int $total
 */
$fid = 'flt-' . substr(md5($searchLabel . implode(',', array_keys($filters))), 0, 6);
$plural = static fn (int $n): string => $n . ' ' . ($n > 1 ? $countWords[1] : $countWords[0]);
?>
<div class="filters" data-filters hidden>
  <div class="field field--search">
    <label for="<?= $fid ?>-q"><?= e($searchLabel) ?></label>
    <div class="search"><?= App\Admin\Ui::icon('search') ?><input id="<?= $fid ?>-q" type="search" autocomplete="off" data-filter-q></div>
  </div>
  <?php foreach ($filters as $fkey => [$flabel, $foptions]): ?>
  <div class="field">
    <label for="<?= $fid ?>-<?= e($fkey) ?>"><?= e($flabel) ?></label>
    <select id="<?= $fid ?>-<?= e($fkey) ?>" data-filter="<?= e($fkey) ?>">
      <option value="">Tous</option>
      <?php foreach ($foptions as $fv => $fl): ?><option value="<?= e((string) $fv) ?>"><?= e($fl) ?></option><?php endforeach ?>
    </select>
  </div>
  <?php endforeach ?>
  <button class="btn btn--ghost" type="button" data-filter-reset hidden><?= App\Admin\Ui::icon('close') ?>Effacer<span class="visually-hidden"> les filtres</span></button>
  <p class="filters__count" data-filter-count data-one="<?= e($countWords[0]) ?>" data-many="<?= e($countWords[1]) ?>" aria-live="polite"><?= e($plural($total)) ?></p>
</div>
