<dl class="identity">
  <dt>Raison sociale</dt><dd><?= e(setting('raison_sociale')) ?></dd>
  <dt>SIRET</dt><dd><?= e(setting('siret')) ?></dd>
  <?php if ($head): ?>
  <dt>Siège social</dt><dd><address><?= App\Site\Components::address($head) ?></address></dd>
  <?php endif ?>
  <dt>Contact</dt><dd><a href="mailto:<?= e(setting('email_contact')) ?>"><?= e(setting('email_contact')) ?></a></dd>
  <dt>Directeur ou directrice de la publication</dt><dd><?= e(setting('directeur_publication')) ?></dd>
  <dt>Hébergeur</dt><dd><?= nl2br(e(setting('hebergeur'))) ?></dd>
</dl>
