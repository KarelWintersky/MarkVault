<?php
declare(strict_types=1);

namespace App;

use Arris\AppConfig;
use Arris\Core\Config\Exception\ParseException;

/**
 * Конфигурация MarkVault поверх karelwintersky/arris.config.
 *
 * Пакет сам умеет сливать файл с дефолтами и не падать на отсутствующем
 * файле (префикс «?»), поэтому здесь только два сценария: битый YAML — тоже
 * откат на дефолты, и представление секции «theme» в виде CSS-переменных.
 */
final class Config
{
    public const FILE_NAME = 'config.yaml';

    public const DARK = 'dark';
    public const LIGHT = 'light';

    /** Ключи конфигурации → имена CSS-переменных. */
    private const CSS_VARIABLES = [
        'bg' => '--bg',
        'panel' => '--panel',
        'text' => '--text',
        'muted' => '--muted',
        'accent' => '--accent',
        'code_bg' => '--code-bg',
        'border' => '--border',
    ];

    private function __construct(private readonly AppConfig $raw)
    {
    }

    /**
     * @param string $baseDir корень приложения (каталог с index.php)
     * @param string|null $file путь к конфигу, по умолчанию «$baseDir/config.yaml»
     */
    public static function load(string $baseDir, ?string $file = null): self
    {
        $path = $file ?? $baseDir . '/' . self::FILE_NAME;
        $defaults = self::defaults($baseDir);

        try {
            return new self(new AppConfig(['?' . $path], $defaults));
        } catch (ParseException) {
            return new self(new AppConfig([], $defaults));
        }
    }

    /**
     * Конфигурация по умолчанию. Всё, чего нет в config.yaml, берётся отсюда.
     *
     * @return array<string,mixed>
     */
    private static function defaults(string $baseDir): array
    {
        return [
            'site' => [
                'title' => 'Docs',
                'nav_title' => 'Документы',
                'default_file' => 'README.md',
            ],
            'content' => $baseDir,
            'nav' => [
                'collapsed' => false,
            ],
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

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw->get($key, $default);
    }

    /** Строковое значение настройки; массивы и объекты игнорируются. */
    public function string(string $key, string $default): string
    {
        $value = $this->raw->get($key, $default);

        return is_array($value) ? $default : (string)$value;
    }

    /**
     * Папки в навигации свёрнуты по умолчанию.
     *
     * Ветка с открытым документом всё равно раскрывается — это делает
     * клиентский JS, иначе не было бы видно, где находится текущая страница.
     */
    public function navCollapsed(): bool
    {
        return (bool)$this->get('nav.collapsed', false);
    }

    /**
     * Набор переменных темы: «dark» или «light».
     *
     * @return array<string,string>
     */
    public function themeVariables(string $theme): array
    {
        $section = $this->get('theme', []);

        if (!is_array($section)) {
            return [];
        }

        $variables = $section[$theme] ?? [];

        return is_array($variables) ? $variables : [];
    }

    /**
     * @return array<string,string>
     */
    public function dark(): array
    {
        return $this->themeVariables(self::DARK);
    }

    /**
     * @return array<string,string>
     */
    public function light(): array
    {
        return $this->themeVariables(self::LIGHT);
    }

    /** Какая тема показывается по умолчанию. */
    public function defaultTheme(): string
    {
        $section = $this->get('theme', []);
        $default = is_array($section) ? ($section['default'] ?? self::DARK) : self::DARK;

        return in_array($default, [self::DARK, self::LIGHT], true) ? (string)$default : self::DARK;
    }

    /**
     * Раскладывает набор переменных в строки CSS-объявлений.
     *
     * Отступ задан явно: блок подставляется в шаблон внутрь «:root { … }».
     *
     * @param array<string,string> $variables
     */
    public static function toCssVariables(array $variables, string $indent = '            '): string
    {
        $out = [];

        foreach (self::CSS_VARIABLES as $key => $cssVariable) {
            if (isset($variables[$key])) {
                $out[] = $indent . $cssVariable . ': ' . self::escape($variables[$key]) . ';';
            }
        }

        return implode("\n", $out);
    }

    private static function escape(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
