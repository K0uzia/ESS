<?php
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;

$user = Auth::user();
$flashes = Session::takeFlashes();
$current = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$isAdmin = $user !== null && $user['role'] === 'admin';
$nav = [
    'Contenus' => [
        '/admin/' => 'Tableau de bord',
        '/admin/pages' => 'Pages',
        '/admin/medias' => 'Médiathèque',
        '/admin/documents' => 'Documents',
    ],
    'Réseau' => [
        '/admin/equipe' => 'Équipe',
        '/admin/adherents' => 'Adhérents',
        '/admin/offres' => 'Offres d\'emploi',
    ],
    'Communication' => [
        '/admin/newsletters' => 'Newsletters',
        '/admin/messages' => 'Messages reçus',
    ],
];
if ($isAdmin) {
    $nav['Administration'] = [
        '/admin/partenaires' => 'Partenaires',
        '/admin/poles' => 'Coordonnées des pôles',
        '/admin/identite' => 'Identité du site',
        '/admin/comptes' => 'Comptes',
    ];
}
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
<script src="/assets/js/admin.js" defer></script>
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
    <p class="alert" role="status"><?= e($flash['message']) ?></p>
    <?php endforeach ?>
    <?= $content ?>
    <p class="login__back"><a href="/">← Retour au site</a></p>
  </main>
</div>
<?php else: ?>
<div class="shell">
  <header class="shell__side">
    <a class="shell__brand" href="/admin/">
      <img src="/assets/img/logo-ess-finistere-blanc.webp" width="320" height="183" alt="ESS Finistère, tableau de bord">
    </a>
    <button class="shell__toggle" type="button" aria-expanded="false" aria-controls="admin-nav" hidden>Menu</button>
    <nav class="shell__nav" id="admin-nav" aria-label="Administration">
      <?php foreach ($nav as $group => $links): ?>
      <p class="shell__group"><?= e($group) ?></p>
      <ul>
        <?php foreach ($links as $href => $label): ?>
        <li><a href="<?= e($href) ?>"<?= $current === $href || ($href !== '/admin/' && str_starts_with($current, $href)) ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach ?>
      </ul>
      <?php endforeach ?>
    </nav>
    <div class="shell__user">
      <p class="shell__who"><span class="shell__avatar" aria-hidden="true"><?= e(strtoupper(mb_substr_safe($user['username'], 1))) ?></span><span><strong><?= e($user['username']) ?></strong><br><?= $user['role'] === 'admin' ? 'Administrateur' : 'Éditeur' ?></span></p>
      <a href="/admin/comptes/<?= (int) $user['id'] ?>/mot-de-passe">Mon mot de passe</a>
      <a href="/" target="_blank" rel="noopener">Voir le site</a>
      <form method="post" action="/admin/deconnexion">
        <?= Csrf::field() ?>
        <button class="button button--ghost button--small" type="submit">Se déconnecter</button>
      </form>
    </div>
  </header>
  <main id="contenu" class="shell__main" tabindex="-1">
    <?php if (Auth::defaultAccountActive()): ?>
    <p class="alert alert--warning" role="status"><strong>Compte temporaire actif.</strong> Le compte « lacapsule » a encore son mot de passe d'origine. Supprimez-le ou changez son mot de passe avant la mise en ligne.</p>
    <?php endif ?>
    <?php foreach ($flashes as $flash): ?>
    <p class="alert<?= $flash['type'] === 'error' ? ' alert--error' : ' alert--success' ?>" role="status"><?= $flash['type'] === 'error' ? 'Erreur : ' : '' ?><?= e($flash['message']) ?></p>
    <?php endforeach ?>
    <?= $content ?>
  </main>
</div>
<dialog class="confirm" id="confirm-dialog" aria-labelledby="confirm-text">
  <p id="confirm-text"></p>
  <div class="form-actions">
    <button class="button" type="button" data-confirm-ok>Confirmer</button>
    <button class="button button--secondary" type="button" data-confirm-cancel>Annuler</button>
  </div>
</dialog>
<?php endif ?>
</body>
</html>
