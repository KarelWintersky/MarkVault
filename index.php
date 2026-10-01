<?php
declare(strict_types=1);

/**
 * MarkVault — точка входа.
 *
 * Вся логика живёт в app/ (неймспейс App\); здесь только подключение
 * автозагрузчика. Собранный PHAR содержит и зависимости, и app/, поэтому
 * при его наличии подключается он, иначе — vendor/ с диска.
 */

if (!defined('PHAR_PATH')) {
    define('PHAR_PATH', __DIR__ . '/markvault.phar');
}

if (is_file(PHAR_PATH)) {
    require_once PHAR_PATH;
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}

(new App\Application(__DIR__))->run();