<?php
if ($certificate === null) {
    return;
}
?>
<div class="qualiopi">
  <img src="/assets/img/logos/qualiopi.webp" alt="Qualiopi, processus certifié, République française" width="346" height="160" loading="lazy" decoding="async">
  <div>
    <p><strong>Le Pôle ESS Pays de Brest est certifié Qualiopi.</strong></p>
    <?php if ($certificate['description']): ?><p><?= e($certificate['description']) ?></p><?php endif ?>
    <p><a href="<?= e($certificate['file_path']) ?>"><?= e($certificate['title']) ?></a> <span class="document__meta">PDF</span></p>
  </div>
</div>
