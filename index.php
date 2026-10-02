<?php
declare(strict_types=1);

/**
 * MarkVault — точка входа.
 *
 * Вся логика живёт в app/ (неймспейс App\); здесь только ключи запуска,
 * выбор конфига и подключение автозагрузчика.
 *
 * Конфиг задаётся по убыванию приоритета:
 *   1. --config=/путь/config.yaml   — CLI, в том числе запуск из systemd
 *   2. MARKVAULT_CONFIG              — fastcgi_param от nginx
 *   3. config.yaml рядом с index.php — локальная разработка
 *
 * Собранный PHAR содержит и app/, и vendor/, поэтому подключается сам себя.
 * Если рядом с index.php лежит markvault.phar — запускается он: так проверяют
 * сборку, не заглядывая внутрь.
 */

$nextToApp = __DIR__ . '/markvault.phar';

// Внутри PHAR этого файла нет, поэтому проверка заодно защищает от рекурсии.
if (is_file($nextToApp)) {
    require_once $nextToApp;
} else {
    require_once __DIR__ . '/vendor/autoload.php';
}

$args = App\Helper::args();

if (App\Helper::handleFlags($args)) {
    exit(0);
}

$configFile = App\Helper::configFile($args);

if ($configFile !== null && !is_file($configFile)) {
    error_log('MarkVault: конфиг не найден — ' . $configFile);
    http_response_code(500);
    exit('MarkVault: не удалось прочитать конфигурацию. Подробности в error.log.');
}

(new App\Application(App\Helper::baseDir(__DIR__, $configFile), null, $configFile))->run();