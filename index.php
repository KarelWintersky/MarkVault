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
 */

// Собранный PHAR содержит и app/, и vendor/, поэтому подключается сам себя.
// Если рядом с index.php лежит markvault.phar — запускается он: так проверяют
// сборку, не заглядывая внутрь. Внутри PHAR этого файла нет, поэтому проверка
// заодно защищает от рекурсии.
if (is_file(__DIR__ . '/markvault.phar')) {
    require_once __DIR__ . '/markvault.phar';

    // phar — это main, он уже отрисовал страницу; продолжать нечего, иначе
    // ниже отрисовалась бы вторая копия той же страницы.
    return;
}

require_once __DIR__ . '/vendor/autoload.php';

// Разбор ключей — только под CLI, и только когда они есть: пустой вызов
// должен рендерить книгу, а не показывать справку.
if (App\Helper::hasCliOptions(App\Helper::args())) {
    App\Helper::runCli();
}

$configFile = App\Helper::configFile();

if ($configFile !== null && !is_file($configFile)) {
    error_log('MarkVault: конфиг не найден — ' . $configFile);
    http_response_code(500);
    // Сообщение — тело ответа, поэтому echo, а не stderr: под FPM оно идёт
    // клиенту. exit() со строкой дал бы код 0, а ошибка обязана быть видна
    // и по коду возврата CLI.
    echo 'MarkVault: не удалось прочитать конфигурацию. Подробности в error.log.';
    exit(1);
}

(
    new App\Application(
        App\Helper::baseDir(__DIR__, $configFile), null, $configFile
    )
)->run();
