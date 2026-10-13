<?php
declare(strict_types=1);

namespace App\Site;

/**
 * Carte des adhérents sans service tiers : contour du Finistère (content/geo/finistere.geojson, IGN Admin Express,
 * Licence Ouverte) tracé en SVG par bin/build-images.php, points placés au rendu avec la même projection.
 */
final class MembersMap
{
    // Emprise du département avec une marge, en degrés.
    private const WEST = -5.19;
    private const EAST = -3.34;
    private const NORTH = 48.79;
    private const SOUTH = 47.67;
    public const WIDTH = 800;

    /** Villes repères des trois pays : [nom, latitude, longitude]. */
    public const TOWNS = [['Brest', 48.3904, -4.4861], ['Morlaix', 48.5771, -3.8277], ['Quimper', 47.9960, -4.1024]];

    public static function height(): int
    {
        return (int) round(self::WIDTH * (self::NORTH - self::SOUTH) / ((self::EAST - self::WEST) * self::lonScale()));
    }

    /** Coordonnées dans le repère SVG, ou null hors de l'emprise (adhérent listé mais absent de la carte). */
    public static function project(float $lat, float $lon): ?array
    {
        if ($lon < self::WEST || $lon > self::EAST || $lat < self::SOUTH || $lat > self::NORTH) {
            return null;
        }
        return [
            round(($lon - self::WEST) / (self::EAST - self::WEST) * self::WIDTH, 1),
            round((self::NORTH - $lat) / (self::NORTH - self::SOUTH) * self::height(), 1),
        ];
    }

    /** Tracé SVG du contour (îles comprises), simplifié : points espacés d'au moins 2 unités, îlots de moins de 6 points écartés. */
    public static function outline(string $geojsonFile): string
    {
        $geo = json_decode((string) file_get_contents($geojsonFile), true);
        $geometry = ($geo['features'][0] ?? $geo)['geometry'];
        $polygons = $geometry['type'] === 'Polygon' ? [$geometry['coordinates']] : $geometry['coordinates'];
        $path = '';
        foreach ($polygons as $polygon) {
            $points = [];
            foreach ($polygon[0] as [$lon, $lat]) {
                $p = self::project($lat, $lon);
                if ($p !== null && ($points === [] || abs(end($points)[0] - $p[0]) + abs(end($points)[1] - $p[1]) >= 2)) {
                    $points[] = [(int) round($p[0]), (int) round($p[1])];
                }
            }
            if (count($points) >= 6) {
                $path .= 'M' . implode('L', array_map(static fn (array $p): string => $p[0] . ' ' . $p[1], $points)) . 'Z';
            }
        }
        return $path;
    }

    private static function lonScale(): float
    {
        return cos(deg2rad((self::NORTH + self::SOUTH) / 2));
    }
}
