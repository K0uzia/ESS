<?php
use App\Admin\Ui;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

$user = Auth::user();
$flashes = Session::takeFlashes();
$current = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isAdmin = $user !== null && $user['role'] === 'admin';
// Menu sans groupes, comme Ulamir : chemin => [libellé, icône]. Comptes est réservé aux administrateurs.
$nav = ['' => [
    '/admin/' => ['Tableau de bord', 'home'],
    '/admin/pages' => ['Pages', 'page'],
    '/admin/equipe' => ['Équipe', 'users'],
    '/admin/adherents' => ['Adhérents', 'pin'],
    '/admin/offres' => ['Offres d\'emploi', 'briefcase'],
    '/admin/documents' => ['Documents', 'folder'],
    '/admin/partenaires' => ['Partenaires', 'link'],
    '/admin/medias' => ['Médiathèque', 'image'],
    '/admin/newsletters' => ['Newsletters', 'mail'],
]];
// E-mails du site (boîte d'envoi, contact, newsletters) : administrateurs seulement.
if ($isAdmin) {
    $nav['']['/admin/emails'] = ['E-mails', 'send'];
}
// Éditeur TAg29 : sa page, son équipe, et ce qui est commun à tous (partenaires, médiathèque).
if ($user !== null && Auth::scope($user) === 'tag29') {
    $nav[''] = array_intersect_key($nav[''], array_flip(['/admin/', '/admin/pages', '/admin/equipe', '/admin/partenaires', '/admin/medias']));
}
$isCurrent = static fn (string $href): bool => $current === $href || ($href !== '/admin/' && str_starts_with($current, $href));
$isLogin = $user === null;
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · Administration ESS Finistère</title>
<link rel="icon" href="/favicon-32.png" type="image/png">
<link rel="preload" href="/assets/fonts/poppins-400.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/admin.css">
<script src="/assets/js/admin.js"></script>
</head>
<body class="<?= $isLogin ? 'admin-login' : 'admin' ?>">
<a class="skip-link" href="#contenu">Aller au contenu</a>
<?php if ($isLogin): ?>
<div class="login">
  <aside class="login__brand">
    <img class="login__logo" src="/assets/img/logo-ess-finistere-blanc.webp" width="320" height="183" alt="ESS Finistère">
    <div class="login__tagline">
      <p class="login__promesse"><?= e(setting('promesse')) ?></p>
      <p class="login__baseline">Espace d'administration du site ess29.org</p>
    </div>
    <img class="login__points" src="/assets/img/deux-points-or.webp" width="240" height="97" alt="">
  </aside>
  <main id="contenu" class="login__main" tabindex="-1">
    <?php foreach ($flashes as $flash): ?>
    <p class="notice<?= $flash['type'] === 'error' ? ' notice--error' : ' notice--success' ?>" role="status"><?= e($flash['message']) ?></p>
    <?php endforeach ?>
    <?= $content ?>
    <p class="login__back"><a href="/">Retour au site</a></p>
  </main>
</div>
<?php else: ?>
<header class="app__bar">
  <a class="app__brand" href="/admin/"><img src="/assets/img/logo-ess-finistere-blanc.webp" width="320" height="183" alt="ESS Finistère, tableau de bord"></a>
  <button class="app__toggle" type="button" aria-expanded="false" aria-controls="admin-side" hidden><?= Ui::icon('menu') ?>Menu</button>
</header>
<div class="app__side" id="admin-side">
  <a class="app__brand" href="/admin/"><img src="/assets/img/logo-ess-finistere-blanc.webp" width="320" height="183" alt="ESS Finistère, tableau de bord"><span>Administration</span></a>
  <nav class="side-nav" aria-label="Administration">
    <?php foreach ($nav as $group => $links): $gid = 'nav-' . preg_replace('/[^a-z]/', '', strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $group))); ?>
    <div>
      <?php if ($group !== ''): ?><p class="side-nav__group" id="<?= $gid ?>"><?= e($group) ?></p><?php endif ?>
      <ul<?= $group !== '' ? ' aria-labelledby="' . $gid . '"' : '' ?>>
        <?php foreach ($links as $href => [$label, $icon]): ?>
        <li><a class="side-link" href="<?= e($href) ?>"<?= $isCurrent($href) ? ' aria-current="page"' : '' ?>><?= Ui::icon($icon) ?><span><?= e($label) ?></span></a></li>
        <?php endforeach ?>
      </ul>
    </div>
    <?php endforeach ?>
  </nav>
  <div class="app__user">
    <p class="app__who"><span class="app__avatar" aria-hidden="true"><?= e(strtoupper(mb_substr_safe($user['name'] ?: $user['username'], 1))) ?></span><span><strong><?= e($user['name'] ?: $user['username']) ?></strong><small><?= $isAdmin ? 'Administrateur' : 'Éditeur' ?></small></span></p>
    <?php if ($isAdmin): ?>
    <a class="side-link side-link--quiet" href="/admin/comptes"<?= $isCurrent('/admin/comptes') ? ' aria-current="page"' : '' ?>><?= Ui::icon('key') ?>Comptes</a>
    <?php endif ?>
    <a class="side-link side-link--quiet" href="/" target="_blank" rel="noopener"><?= Ui::icon('external') ?>Voir le site<span class="visually-hidden"> (nouvel onglet)</span></a>
    <form method="post" action="/admin/deconnexion">
      <?= Csrf::field() ?>
      <button class="side-link side-link--quiet" type="submit"><?= Ui::icon('logout') ?>Se déconnecter</button>
    </form>
  </div>
</div>
<main id="contenu" class="app__main" tabindex="-1">
  <?php foreach ($flashes as $flash): $err = $flash['type'] === 'error'; ?>
  <div class="notice <?= $err ? 'notice--error' : 'notice--success' ?>" role="<?= $err ? 'alert' : 'status' ?>"><?= Ui::icon($err ? 'alert' : 'check') ?><p><?= $err ? '<strong>Erreur :</strong> ' : '' ?><?= e($flash['message']) ?></p></div>
  <?php endforeach ?>
  <?= $content ?>
</main>
<p class="visually-hidden" id="admin-live" role="status" aria-live="polite"></p>
<dialog class="modal" id="modal" aria-labelledby="modal-title">
  <div class="modal__head">
    <h2 class="modal__title" id="modal-title"></h2>
    <button class="btn btn--icon btn--ghost" type="button" data-modal-close><?= Ui::icon('close') ?><span class="visually-hidden">Fermer</span></button>
  </div>
  <div class="modal__body" data-modal-body tabindex="-1"></div>
  <div class="modal__foot" data-modal-foot-slot hidden></div>
</dialog>
<dialog class="modal modal--preview" id="preview-dialog" aria-labelledby="preview-title">
  <div class="modal__head">
    <h2 class="modal__title" id="preview-title">Aperçu de la newsletter</h2>
    <div class="segmented" role="group" aria-label="Largeur de l'aperçu">
      <button class="segmented__option" type="button" data-preview-width="desktop" aria-pressed="true"><?= Ui::icon('monitor') ?>Ordinateur</button>
      <button class="segmented__option" type="button" data-preview-width="mobile" aria-pressed="false"><?= Ui::icon('phone') ?>Mobile</button>
    </div>
    <button class="btn btn--icon btn--ghost" type="button" data-preview-close><?= Ui::icon('close') ?><span class="visually-hidden">Fermer l'aperçu</span></button>
  </div>
  <div class="modal__body preview-stage">
    <iframe class="preview-frame" title="Aperçu de l'e-mail" sandbox="" data-preview-frame></iframe>
  </div>
</dialog>
<dialog class="confirm link-dialog" id="link-dialog" aria-labelledby="link-dialog-title">
  <form method="dialog" novalidate>
    <h2 class="modal__title" id="link-dialog-title">Lien</h2>
    <?php $lid = 'link-target'; $lname = ''; $url = ''; $llabel = 'Le lien mène vers'; require ROOT . '/templates/admin/partials/link.php'; ?>
    <p class="field__error" id="link-error" role="alert" hidden>Choisissez une page, ou saisissez une adresse valable.</p>
    <div class="field" data-link-text>
      <label for="link-text">Texte du lien</label>
      <input id="link-text" type="text" autocomplete="off">
    </div>
    <div class="button-row">
      <button class="btn btn--danger" type="button" data-link-remove hidden><?= Ui::icon('trash') ?>Retirer le lien</button>
      <span class="button-row__spacer"></span>
      <button class="btn" type="button" data-link-cancel>Annuler</button>
      <button class="btn btn--primary" type="submit">Valider</button>
    </div>
  </form>
</dialog>
<template id="rte-toolbar">
  <div class="rte__toolbar" role="toolbar" aria-label="Mise en forme">
    <button class="rte__tool" type="button" data-rte-tool="bold" aria-pressed="false" title="Gras (Ctrl+B)"><?= Ui::icon('bold') ?><span>Gras</span></button>
    <button class="rte__tool" type="button" data-rte-tool="italic" aria-pressed="false" title="Italique (Ctrl+I)"><?= Ui::icon('italic') ?><span>Italique</span></button>
    <button class="rte__tool" type="button" data-rte-tool="link"><?= Ui::icon('link') ?><span>Lien</span></button>
    <button class="rte__tool" type="button" data-rte-tool="list" aria-pressed="false"><?= Ui::icon('list') ?><span>Liste</span></button>
    <button class="rte__tool" type="button" data-rte-tool="olist" aria-pressed="false"><?= Ui::icon('olist') ?><span>Liste numérotée</span></button>
    <button class="rte__tool" type="button" data-rte-tool="heading" aria-pressed="false"><?= Ui::icon('heading') ?><span>Intertitre</span></button>
    <button class="rte__tool" type="button" data-rte-tool="button"><?= Ui::icon('button') ?><span>Bouton</span></button>
  </div>
</template>
<dialog class="confirm" id="confirm-dialog" aria-labelledby="confirm-text">
  <p id="confirm-text"></p>
  <div class="button-row button-row--end">
    <button class="btn" type="button" data-confirm-cancel>Annuler</button>
    <button class="btn btn--primary" type="button" data-confirm-ok>Confirmer</button>
  </div>
</dialog>
<?php endif ?>
</body>
</html>
