<?php
declare(strict_types=1);

namespace App;

use Arris\Toolkit\CLI\OptionType;
use Arris\Toolkit\CLIConsole;
use Arris\Toolkit\CLIRunner;

/**
 * Служебное для точки входа: ключи командной строки и поиск конфига.
 *
 * Всё, что нужно index.php до создания Application. Чтение YAML, разбор
 * запроса и рендер живут в других классах — здесь только разбор аргументов,
 * на который способен CLIRunner, и разметка справки средствами CLIConsole.
 */
final class Helper
{
    /** Параметр, которым nginx передаёт путь к конфигу. */
    public const ENV_CONFIG = 'MARKVAULT_CONFIG';

    /** Путь, заданный ключом --config; null — ключа не было. */
    private static ?string $configOption = null;

    /** Аргументы командной строки; вне CLI всегда пустые. */
    public static function args(): array
    {
        // Под FPM register_argc_argv обычно выключен, поэтому полагаться
        // на $argv нельзя — читаем $_SERVER и только в CLI.
        return PHP_SAPI === 'cli' ? ($_SERVER['argv'] ?? []) : [];
    }

    /**
     * Есть ли среди аргументов хоть что-то похожее на ключ.
     *
     * Нужен, чтобы пустой вызов (`php index.php`, регрессии) рендерил книгу,
     * а не показывал справку: CLIRunner трактует «опций нет» как повод
     * для --help.
     */
    public static function hasCliOptions(array $args): bool
    {
        // Нулевой элемент — имя скрипта, ключом быть не может.
        foreach (array_slice($args, 1) as $arg) {
            if (str_starts_with($arg, '-')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Разбор ключей и выполнение обработчиков.
     *
     * Само завершает процесс там, где завершаться и надо: на --help после
     * напечатанной справки и на --version из обработчика.
     */
    public static function runCli(): void
    {
        // run() возвращает false, когда показана справка или опций нет.
        if (!self::cli()->run()) {
            exit(0);
        }
    }

    /**
     * Путь к конфигу по убыванию приоритета: --config=, MARKVAULT_CONFIG,
     * getenv(). null — конфиг не задан, берёмся за лежащий рядом с приложением.
     */
    public static function configFile(): ?string
    {
        if (self::$configOption !== null) {
            return self::$configOption;
        }

        $fromEnv = $_SERVER[self::ENV_CONFIG] ?? getenv(self::ENV_CONFIG);

        return is_string($fromEnv) && trim($fromEnv) !== '' ? trim($fromEnv) : null;
    }

    /**
     * База для относительного «content.path»: каталог конфига, иначе корень
     * приложения.
     *
     * realpath() нужен ещё и затем, чтобы «..» в пути не увели за пределы
     * каталога конфига.
     */
    public static function baseDir(string $appRoot, ?string $configFile): string
    {
        if ($configFile === null) {
            return $appRoot;
        }

        return dirname(realpath($configFile) ?: $configFile);
    }

    /**
     * Версия сборки из vendor/_version, который кладёт build_phar.sh.
     *
     * Файл лежит в vendor/, поэтому от каталога app/ идём на уровень выше:
     * внутри phar это phar://…/vendor/_version, в checkout — vendor/_version.
     * В обычном checkout файла нет, и версия неизвестна.
     *
     * Без разметки CLIConsole: --version читают скрипты, ANSI-коды там лишние.
     */
    public static function version(): string
    {
        $text = @file_get_contents(dirname(__DIR__) . '/vendor/_version');

        if ($text === false) {
            return 'unknown (сборка не выполнялась)';
        }

        $lines = array_values(array_filter(array_map(trim(...), explode("\n", $text))));

        // build_phar.sh пишет сводку последней строкой — она и годится
        // для однострочного --version.
        $summary = end($lines);

        return str_starts_with((string)$summary, 'Version: ')
            ? $summary
            : trim($text);
    }

    private static function cli(): CLIRunner
    {
        $cli = new CLIRunner(self::description());

        $cli->register(
            option: 'config',
            short: 'c',
            type: OptionType::WithValue,
            // required обязан быть false: конфиг не обязателен, его может
            // принести MARKVAULT_CONFIG, а тип WithValue без default
            // автоматически становится обязательным.
            required: false,
            description: 'путь к config.yaml книги; перекрывает MARKVAULT_CONFIG',
            handler: function (mixed $value): void {
                // getopt отдаёт массив, если ключ повторился, и пустую
                // строку на «--config=» — оба случая означают не один путь.
                if (!is_string($value) || trim($value) === '') {
                    // Ошибка разбора уходит в stderr, как требует sysexits
                    // для кода 2; разметку даёт тот же CLIConsole, что и
                    // у help, — get_message() возвращает её без вывода.
                    fwrite(
                        STDERR,
                        (string)CLIConsole::get_message("<font color='red'>Ключу --config нужен один путь к config.yaml</font>"),
                    );
                    exit(2);
                }

                self::$configOption = trim($value);
            },
        );

        $cli->register(
            option: 'version',
            short: 'V',
            description: 'показать версию сборки и выйти',
            handler: function (): void {
                echo self::version(), PHP_EOL;
                exit(0);
            },
        );

        return $cli;
    }

    /** Описание для справки: разметку разворачивает CLIConsole. */
    private static function description(): string
    {
        $lines = [
            "<font color='cyan'>MarkVault</font> — просмотрщик Markdown-каталога.",
            '',
            'Код целиком лежит в этом PHAR, а данные — рядом с ним: конфиг и',
            'каталог документов. Конфиг задаёт путь к контенту, заголовки,',
            'сортировку, тему и необязательную парольную защиту.',
            '',
            "<font color='green'>Примеры</font>",
            '  php markvault.phar --config=/путь/к/config.yaml',
            '  php markvault.phar --version',
            '',
            "<font color='green'>Под nginx</font> исполняемый файл — сам PHAR, а конфиг",
            'приходит параметром. Свои fastcgi_param обязаны идти ПОСЛЕ',
            'include fastcgi_params, иначе перекроются значения из общего файла.',
            '',
            '  location ~ \.php$ {',
            '      include fastcgi_params;',
            '      fastcgi_param SCRIPT_FILENAME /usr/local/bin/markvault.phar;',
            '      fastcgi_param MARKVAULT_CONFIG /var/www.books/Книга/config.yaml;',
            '      fastcgi_pass unix:/run/php/php8.2-fpm.sock;',
            '  }',
            '',
            "<font color='green'>Минимальный config.yaml</font> рядом с каталогом content:",
            '',
            '  site:',
            '    title: "Моя книга"',
            '  content:',
            '    path: ./content',
            '    default_file: README.md',
            '    folders: opened',
            '',
            "<font color='green'>Версия</font>",
            '  ' . self::version(),
        ];

        return implode(PHP_EOL, $lines);
    }
}