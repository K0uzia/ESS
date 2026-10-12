<?php use App\Core\Csrf; ?>
<div class="page-title">
  <p class="page-title__kicker">Contenus</p>
  <h1>Médiathèque</h1>
</div>
<p class="lead">Photos réelles fournies par les pôles (événements, locaux, équipes). Elles sont converties en WebP et redimensionnées automatiquement. Les photos de la charte restent toujours disponibles dans l'éditeur de pages.</p>

<form class="panel upload" method="post" action="/admin/medias" enctype="multipart/form-data">
  <?= Csrf::field() ?>
  <div class="form-grid">
    <div class="field">
      <label for="photo">Photo (JPG, PNG ou WebP, 15 Mo au plus)</label>
      <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" required>
    </div>
    <div class="field">
      <label for="alt">Description courte (pour la retrouver)</label>
      <input id="alt" name="alt" maxlength="120">
    </div>
    <div class="field field--check field--wide">
      <label class="switch" for="rights">
        <input type="checkbox" id="rights" name="rights" value="1" required>
        <span class="switch__track" aria-hidden="true"></span>
        <span>Cette photo est réelle, et j'ai l'accord des personnes reconnaissables et du photographe pour la publier.</span>
      </label>
    </div>
  </div>
  <div class="form-actions"><button class="button" type="submit">Ajouter la photo</button></div>
</form>

<?php if ($media === []): ?>
<div class="empty"><p>Aucune photo ajoutée pour l'instant.</p></div>
<?php else: ?>
<ul class="media-grid" role="list">
  <?php foreach ($media as $m): ?>
  <li class="media-card">
    <img src="<?= e($m['path']) ?>" alt="" width="<?= (int) $m['width'] ?>" height="<?= (int) $m['height'] ?>" loading="lazy">
    <form method="post" action="/admin/medias/<?= (int) $m['id'] ?>">
      <?= Csrf::field() ?>
      <label class="visually-hidden" for="alt-<?= (int) $m['id'] ?>">Description</label>
      <input id="alt-<?= (int) $m['id'] ?>" name="alt" value="<?= e($m['alt']) ?>" placeholder="Description">
      <button class="button button--secondary button--small" type="submit">Enregistrer</button>
    </form>
    <p class="media-card__meta"><?= isset($used['media-' . $m['id']]) ? 'Utilisée dans ' . (int) $used['media-' . $m['id']] . ' section(s)' : 'Non utilisée' ?></p>
    <form method="post" action="/admin/medias/<?= (int) $m['id'] ?>/suppression" data-confirm="Supprimer cette photo ?">
      <?= Csrf::field() ?>
      <button class="button--link button--danger" type="submit">Supprimer</button>
    </form>
  </li>
  <?php endforeach ?>
</ul>
<?php endif ?>
