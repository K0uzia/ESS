<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path, int $status = 303): never
{
    header('Location: ' . $path, true, $status);
    exit;
}

function setting(string $key): string
{
    static $settings = null;
    $settings ??= App\Core\Db::pdo()->query('SELECT key, value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
    return $settings[$key] ?? '';
}

/** Longueur en caractères UTF-8, sans dépendre de l'extension mbstring. */
function text_length(string $value): int
{
    return (int) preg_match_all('/./su', $value);
}

/** Premiers caractères UTF-8, sans dépendre de l'extension mbstring. */
function mb_substr_safe(string $value, int $length): string
{
    preg_match('/^.{0,' . $length . '}/su', $value, $m);
    return $m[0] ?? '';
}

/** Vrai si la chaîne est de l'UTF-8 valide, sans dépendre de l'extension mbstring. */
function mb_check_encoding_safe(string $value): bool
{
    return preg_match('//u', $value) === 1;
}
