<?php $tab ??= ''; ?>
<nav aria-label="Rubriques des newsletters">
  <ul class="tabs" role="list">
    <li><a href="/admin/newsletters"<?= $tab === 'lettres' ? ' aria-current="page"' : '' ?>>Lettres</a></li>
    <li><a href="/admin/newsletters/abonnes"<?= $tab === 'abonnes' ? ' aria-current="page"' : '' ?>>Abonnés</a></li>
    <li><a href="/admin/newsletters?vue=modeles"<?= $tab === 'modeles' ? ' aria-current="page"' : '' ?>>Modèles</a></li>
  </ul>
</nav>
