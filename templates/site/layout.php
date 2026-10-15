<?php
use App\Site\Components;
use App\Site\SiteController;

$meta = $page['meta'];
$siteUrl = rtrim(setting('site_url'), '/');
$shortName = setting('nom_court');
$title = $pageKey === 'accueil' ? $shortName . ' · ' . setting('baseline') : ($meta['title'] ?? $meta['h1']);
// Suffixe de marque seulement s'il tient sous 60 caractères.
if ($pageKey !== 'accueil' && text_length($title . ' · ' . $shortName) < 60) {
    $title .= ' · ' . $shortName;
}
$canonical = $siteUrl . $path;
$isTag29 = $pageKey === 'tag29';
$poles = Components::poles();
$jsonLd = [
    '@context' => 'https://schema.org',
    '@graph' => array_merge([[
        '@type' => 'Organization',
        '@id' => $siteUrl . '/#organisation',
        'name' => setting('raison_sociale'),
        'alternateName' => $shortName,
        'url' => $siteUrl . '/',
        'logo' => $siteUrl . '/assets/img/logo-ess-finistere-cartouche-glaz.png',
        'email' => setting('email_contact'),
        'taxID' => str_replace(' ', '', setting('siret')),
        'subOrganization' => array_map(fn ($p) => ['@id' => $siteUrl . '/les-poles/#' . $p['slug']], $poles),
    ]], array_map(fn ($p) => [
        '@type' => 'Organization',
        '@id' => $siteUrl . '/les-poles/#' . $p['slug'],
        'name' => $p['name'],
        'url' => $siteUrl . '/les-poles/#' . $p['slug'],
        'email' => $p['email'],
        'telephone' => explode("\n", $p['phones']),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => str_replace("\n", ', ', $p['address_lines']),
            'postalCode' => $p['postal_code'],
            'addressLocality' => $p['city'],
            'addressCountry' => 'FR',
        ],
        'parentOrganization' => ['@id' => $siteUrl . '/#organisation'],
    ], $poles)),
];
?><!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<?php if (!empty($meta['description'])): ?>
<meta name="description" content="<?= e($meta['description']) ?>">
<?php endif ?>
<?php if ($pageKey === '404'): ?>
<meta name="robots" content="noindex">
<?php else: ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="fr_FR">
<meta property="og:site_name" content="<?= e($shortName) ?>">
<meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($meta['description'] ?? '') ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($siteUrl) ?>/assets/img/og-ess-finistere.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="Logo ESS Finistère">
<meta name="twitter:card" content="summary_large_image">
<?php endif ?>
<link rel="icon" href="/favicon-32.png" type="image/png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preload" href="/assets/fonts/poppins-400.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/poppins-600.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/site.css">
<?php if ($isTag29): ?>
<link rel="stylesheet" href="/assets/css/tag29.css">
<?php endif ?>
<script src="/assets/js/site.js"></script>
<?php if ($pageKey === 'accueil'): ?>
<script type="application/ld+json"><?= json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif ?>
</head>
<body class="page-<?= e($pageKey) ?>">
<a class="skip-link" href="#contenu">Aller au contenu</a>
<header class="site-header">
  <div class="site-header__inner container">
    <a class="site-header__logo" href="/"<?= $path === '/' ? ' aria-current="page"' : '' ?>>
      <img src="/assets/img/logo-ess-finistere-couleur.webp" width="320" height="183" alt="ESS Finistère, accueil">
    </a>
    <nav class="site-nav" aria-label="Navigation principale" data-nav>
      <button class="site-nav__toggle" type="button" aria-expanded="false" aria-controls="menu-principal" hidden><span class="site-nav__toggle-bars" aria-hidden="true"></span>Menu</button>
      <ul class="site-nav__list" id="menu-principal">
        <?php foreach (SiteController::NAV as $href => $label): ?>
        <li><a<?= $href === '/#contact' ? ' class="site-nav__cta"' : '' ?> href="<?= e($href) ?>"<?= $path === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
        <?php endforeach ?>
      </ul>
    </nav>
  </div>
</header>

<main id="contenu" tabindex="-1">
<?= $content ?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="site-footer__top">
      <div class="site-footer__brand">
        <img src="/assets/img/logo-ess-finistere-blanc.webp" width="320" height="183" alt="ESS Finistère" loading="lazy" decoding="async">
        <p class="site-footer__baseline"><?= e(setting('baseline')) ?></p>
        <p><?= e(setting('raison_sociale')) ?><br><a href="mailto:<?= e(setting('email_contact')) ?>"><?= e(setting('email_contact')) ?></a></p>
      </div>
      <div>
        <h2 class="site-footer__title">Les pôles</h2>
        <?php foreach ($poles as $p): ?>
        <div class="site-footer__pole">
          <strong><a href="/les-poles/#<?= e($p['slug']) ?>"><?= e($p['name']) ?></a></strong>
          <address><?= App\Site\Components::address($p, true) ?></address>
          <?php foreach (explode("\n", $p['phones']) as $phone): ?>
          <a href="tel:+33<?= e(substr(str_replace(' ', '', $phone), 1)) ?>"><?= e($phone) ?></a>
          <?php endforeach ?>
        </div>
        <?php endforeach ?>
      </div>
      <nav aria-labelledby="footer-nav">
        <h2 class="site-footer__title" id="footer-nav">Le site</h2>
        <ul>
          <li><a href="/">Accueil</a></li>
          <?php foreach (SiteController::NAV as $href => $label): ?>
          <li><a href="<?= e($href) ?>"><?= e($label) ?></a></li>
          <?php endforeach ?>
          <li><a href="/#newsletter">Lettres d'information</a></li>
        </ul>
      </nav>
    </div>
    <div class="site-footer__bottom">
      <p>© <?= date('Y') ?> <?= e(setting('raison_sociale')) ?></p>
      <p class="site-footer__credit">Site réalisé par <a href="https://kouzia.com" rel="noopener">Kouzia</a>, au sein de <a href="https://lacapsule.org" rel="noopener">La Capsule</a></p>
      <ul aria-label="Informations légales">
        <li><a href="/plan-du-site/">Plan du site</a></li>
        <li><a href="/mentions-legales/">Mentions légales</a></li>
        <li><a href="/confidentialite/">Confidentialité</a></li>
        <li><a href="/accessibilite/">Accessibilité : non conforme</a></li>
      </ul>
    </div>
  </div>
  <img class="deux-points" src="/assets/img/deux-points-or.webp" width="240" height="97" alt="" loading="lazy" decoding="async">
</footer>
</body>
</html>
