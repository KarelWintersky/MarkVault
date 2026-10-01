<?php
declare(strict_types=1);

namespace App\Config;

use Arris\AppConfig;
use Arris\Core\Config\Exception\ParseException;

/**
 * Загрузка конфигурации MarkVault поверх karelwintersky/arris.config.
 *
 * Пакет сам умеет сливать файл с дефолтами и не падать на отсутствующем
 * файле (префикс «?»), поэтому отсюда нужен только один дополнительный
 * сценарий — битый YAML: в нём тоже откатываемся на дефолты.
 */
final class ConfigFactory
{
    public const FILE_NAME = 'config.yaml';

    private function __construct()
    {
    }

    /**
     * @param string $baseDir корень приложения (каталог с index.php)
     * @param string|null $file путь к конфигу, по умолчанию «$baseDir/config.yaml»
     */
    public static function load(string $baseDir, ?string $file = null): AppConfig
    {
        $path = $file ?? $baseDir . '/' . self::FILE_NAME;

        try {
            return new AppConfig(['?' . $path], self::defaults($baseDir));
        } catch (ParseException) {
            return new AppConfig([], self::defaults($baseDir));
        }
    }

    /**
     * Конфигурация по умолчанию. Всё, чего нет в config.yaml, берётся отсюда.
     *
     * @return array<string,mixed>
     */
    public static function defaults(string $baseDir): array
    {
        return [
            'site' => [
                'title' => 'Docs',
                'nav_title' => 'Документы',
                'default_file' => 'README.md',
            ],
            'content' => $baseDir,
            'hide' => [
                'index.php', 'vendor', 'composer.json', 'composer.lock',
                '.git', '.gitignore', 'test.php', 'config.yaml',
            ],
            'theme' => [
                'default' => 'dark',
                'dark' => [
                    'bg' => '#0f1115',
                    'panel' => '#161920',
                    'text' => '#e6e8eb',
                    'muted' => '#9aa0a6',
                    'accent' => '#5b9df9',
                    'code_bg' => '#1e222a',
                    'border' => '#2a2f3a',
                ],
                'light' => [
                    'bg' => '#faf9f7',
                    'panel' => '#f2f0eb',
                    'text' => '#232323',
                    'muted' => '#6b6b6b',
                    'accent' => '#0366d6',
                    'code_bg' => '#f6f8fa',
                    'border' => '#e1e4e8',
                ],
            ],
        ];
    }
}