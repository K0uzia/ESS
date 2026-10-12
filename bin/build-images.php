#!/usr/bin/env php
<?php
declare(strict_types=1);

// Déclinaisons web des visuels : Charte/ (PNG RVB, photos 5-Photos) et content/logos/ (logos partenaires publiés).
// Redimension, recadrage centré optionnel (photos seulement), WebP. Les logos ne sont jamais recadrés.
ini_set('memory_limit', '2G');

$root = dirname(__DIR__);
$charte = "$root/Charte/CHARTE GRAPHIQUE ESS BRETAGNE";
$logo = "$charte/1-Logotype/1-Logotype final/ESS-Finistere/Web-RVB/PNG";
$photos = "$charte/5-Photos";
$pictos = "$charte/4-Pictogrammes/Web-RVB/PNG";
$ingredients = "$charte/3-Ingredients-graphiques";
$out = "$root/public/assets/img";

/** @return GdImage */
function load(string $file): GdImage
{
    $img = imagecreatefromstring((string) file_get_contents($file));
    if ($img === false) {
        throw new RuntimeException("Illisible : $file");
    }
    return $img;
}

/** Redimensionne à $width ; avec $ratio (largeur/hauteur), recadre autour de $focusY (0 haut, 1 bas). */
function resize(GdImage $src, int $width, ?float $ratio = null, float $focusY = 0.5): GdImage
{
    $w = imagesx($src);
    $h = imagesy($src);
    [$sx, $sy, $sw, $sh] = [0, 0, $w, $h];
    if ($ratio !== null) {
        if ($w / $h > $ratio) {
            $sw = (int) round($h * $ratio);
            $sx = intdiv($w - $sw, 2);
        } else {
            $sh = (int) round($w / $ratio);
            $sy = (int) round(($h - $sh) * $focusY);
        }
    }
    $width = min($width, $sw);
    $height = (int) round($sh * $width / $sw);
    $dst = imagecreatetruecolor($width, $height);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
    imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $width, $height, $sw, $sh);
    return $dst;
}

/** Recadre aux pixels visibles : ni (presque) transparents, ni (presque) blancs. */
function trim_margins(GdImage $img): GdImage
{
    imagepalettetotruecolor($img);
    $w = imagesx($img);
    $h = imagesy($img);
    [$x0, $y0, $x1, $y1] = [$w, $h, -1, -1];
    for ($y = 0; $y < $h; $y++) {
        for ($x = 0; $x < $w; $x++) {
            $c = imagecolorat($img, $x, $y);
            $alpha = ($c >> 24) & 0x7F;
            $light = min(($c >> 16) & 0xFF, ($c >> 8) & 0xFF, $c & 0xFF);
            if ($alpha < 100 && $light < 235) {
                [$x0, $y0, $x1, $y1] = [min($x0, $x), min($y0, $y), max($x1, $x), max($y1, $y)];
            }
        }
    }
    if ($x1 < 0) {
        return $img;
    }
    $pad = (int) round(max($x1 - $x0, $y1 - $y0) * 0.04);
    $x0 = max(0, $x0 - $pad);
    $y0 = max(0, $y0 - $pad);
    return imagecrop($img, ['x' => $x0, 'y' => $y0, 'width' => min($w, $x1 + $pad + 1) - $x0, 'height' => min($h, $y1 + $pad + 1) - $y0]) ?: $img;
}

function save(GdImage $img, string $file, int $quality): void
{
    is_dir(dirname($file)) || mkdir(dirname($file), 0775, true);
    imagewebp($img, $file, $quality);
    printf("%-55s %4dx%-4d %6.1f Ko\n", basename(dirname($file)) . '/' . basename($file), imagesx($img), imagesy($img), filesize($file) / 1024);
}

// Logos de la marque et des pôles (fond transparent).
$brand = [
    ["$logo/ESS-Finistere_Couleur.png", 'logo-ess-finistere-couleur', 320],
    ["$logo/ESS-Finistere_Blanc.png", 'logo-ess-finistere-blanc', 320],
    ["$logo/ESS-Finistere_CartoucheGlaz.png", 'logo-ess-finistere-cartouche-glaz', 512],
    ["$root/Charte/logoPoleESS/PaysdeMorlaix/Logo-Simple/Logo-PoleESS-PaysdeMorlaix.png", 'logo-pole-pays-de-morlaix', 204],
    ["$root/Charte/logoPoleESS/PoleEssBrest/PaysdeBrest/Logo-Simple/Logo-PoleESS-PaysdeBrest.png", 'logo-pole-pays-de-brest', 217],
    ["$root/Charte/tag29/3. LOGOS TAG/29/bureautique/tag29-rvb-noir.png", 'logo-tag29-noir', 240],
    ["$ingredients/Deux-points/Web-RVB/PNG/ESS-Bretagne_Deux-points_Couleur.png", 'deux-points-or', 240],
    ["$ingredients/Deux-points/Web-RVB/PNG/ESS-Bretagne_Deux-points_Blanc.png", 'deux-points-blanc', 240],
    ["$ingredients/Guillemets/Web-RVB/PNG/ESS-Bretagne_Guillemets_Glaz.png", 'guillemets-glaz', 160],
    ["$ingredients/Guillemets/Web-RVB/PNG/ESS-Bretagne_Guillemets_Or.png", 'guillemets-or', 160],
];
foreach ($brand as [$src, $name, $width]) {
    save(resize(load($src), $width), "$out/$name.webp", 90);
}

// Pictogrammes de marque, boîte de 192 px de large.
foreach (glob("$pictos/*.png") ?: [] as $file) {
    $name = strtolower(pathinfo($file, PATHINFO_FILENAME));
    save(resize(load($file), 192), "$out/pictos/$name.webp", 90);
}

// Photos : [fichier, nom, ratio, focusY, largeurs].
$jobs = [
    ['AdobeStock_1879193392.jpeg', 'accueil', 16 / 9, 0.5, [1920, 960]],
    ['AdobeStock_1342779117.jpeg', 'reseau', 16 / 9, 0.35, [1600, 800]],
    ['AdobeStock_519172833.jpeg', 'equipe', 16 / 9, 0.3, [1600, 800]],
    ['AdobeStock_659243949.jpeg', 'decouvrir', 16 / 9, 0.3, [1600, 800]],
    ['AdobeStock_600680452.jpeg', 'agir', 16 / 9, 0.3, [1600, 800]],
    ['AdobeStock_2063079841.jpeg', 'former', 16 / 9, 0.4, [1600, 800]],
    ['AdobeStock_339694831.jpeg', 'contact', 16 / 9, 0.35, [1600, 800]],
    ['AdobeStock_1367700974.jpeg', 'adherer', 4 / 5, 0.55, [900, 600]],
    ['AdobeStock_209847380.jpeg', 'atelier', 4 / 5, 0.4, [900, 600]],
    ['AdobeStock_1708024907.jpeg', 'collectif', 4 / 5, 0.6, [900, 600]],
];
foreach ($jobs as [$file, $name, $ratio, $focus, $widths]) {
    $src = load("$photos/$file");
    foreach ($widths as $w) {
        save(resize($src, $w, $ratio, $focus), "$out/photos/$name-$w.webp", $w > 1000 ? 60 : 66);
    }
    imagedestroy($src);
}

// Logos partenaires publiés (content/logos/), hauteur utile 2× l'affichage.
foreach (glob("$root/content/logos/*.{png,jpg}", GLOB_BRACE) ?: [] as $file) {
    $img = load($file);
    // Marges blanches ou transparentes du fichier source retirées (le logo lui-même n'est pas touché).
    $img = trim_margins($img);
    $targetW = (int) round(imagesx($img) * min(1, 160 / imagesy($img)));
    save(resize($img, max(1, $targetW)), "$out/logos/" . pathinfo($file, PATHINFO_FILENAME) . '.webp', 88);
}

// Logos des e-mails en PNG (les messageries ne lisent pas toutes le WebP), 128 px de haut pour un affichage à 64 px.
foreach ([
    ["$logo/ESS-Finistere_Couleur.png", 'logo-ess-finistere'],
    ["$root/Charte/logoPoleESS/PaysdeMorlaix/Logo-Simple/Logo-PoleESS-PaysdeMorlaix.png", 'logo-pole-pays-de-morlaix'],
    ["$root/Charte/logoPoleESS/PoleEssBrest/PaysdeBrest/Logo-Simple/Logo-PoleESS-PaysdeBrest.png", 'logo-pole-pays-de-brest'],
] as [$src, $name]) {
    $img = load($src);
    $png = resize($img, (int) round(imagesx($img) * min(1, 128 / imagesy($img))));
    is_dir("$out/email") || mkdir("$out/email", 0775, true);
    imagepng($png, "$out/email/$name.png", 9);
    echo "email/$name.png\n";
}

// Favicon et image de partage (1200×630) : cartouche Glaz posé tel quel, sans recomposition, sur fond blanc.
$cartouche = load("$logo/ESS-Finistere_CartoucheGlaz.png");
foreach ([[32, 'favicon-32.png'], [180, 'apple-touch-icon.png']] as [$size, $file]) {
    imagepng(resize($cartouche, $size), "$root/public/$file", 9);
}
$og = imagecreatetruecolor(1200, 630);
imagefill($og, 0, 0, imagecolorallocate($og, 255, 255, 255));
imagecopyresampled($og, $cartouche, 335, 50, 0, 0, 530, 530, imagesx($cartouche), imagesy($cartouche));
imagepng($og, "$out/og-ess-finistere.png", 9);
echo "favicon, apple-touch-icon, og-ess-finistere\n";
