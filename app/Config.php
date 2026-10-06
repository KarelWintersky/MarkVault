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

    public const FOLDERS_OPENED = 'opened';
    public const FOLDERS_COLLAPSED = 'collapsed';

    /**
     * «content.folders_remember» — как папки помнят раскрытие между переходами.
     *
     *   FOLDERS_REMEMBER_NONE       не помнить (поведение по умолчанию);
     *   FOLDERS_REMEMBER_PER_FOLDER каждая папка помнит своё состояние, открыто
     *                              сколько угодно одновременно;
     *   FOLDERS_REMEMBER_SINGLE     открыта не более одной папки на уровне:
     *                              открытие одной схлопывает остальных соседей.
     */
    public const FOLDERS_REMEMBER_NONE = 'none';
    public const FOLDERS_REMEMBER_PER_FOLDER = 'per_folder';
    public const FOLDERS_REMEMBER_SINGLE = 'single';

    /** Ключи конфигурации → имена CSS-переменных. */
    private const CSS_VARIABLES = [
        'bg' => '--bg',
        'panel' => '--panel',
        'text' => '--text',
        'muted' => '--muted',
        'accent' => '--accent',
        'code_bg' => '--code-bg',
        'border' => '--border',
        // Токены темы mdshelf. Простые темы этих ключей не задают —
        // toCssVariables() пропускает отсутствующие, поэтому карта общая.
        'surface' => '--surface',
        'surface_hover' => '--surface-hover',
        'text_heading' => '--text-heading',
        'accent_hover' => '--accent-hover',
        'accent_soft' => '--accent-soft',
        'border_subtle' => '--border-subtle',
        'shadow_sm' => '--shadow-sm',
        'shadow_lg' => '--shadow-lg',
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
            ],
            'content' => [
                'path' => $baseDir,
                'default_file' => 'README.md',
                'folders' => self::FOLDERS_OPENED,
                'folders_remember' => self::FOLDERS_REMEMBER_NONE,
            ],
            'hide' => [
                'index.php', 'vendor', 'composer.json', 'composer.lock',
                '.git', '.gitignore', 'test.php', 'config.yaml',
            ],
            'sort'  =>  [
                'folders_first' =>  true
            ],
            'theme' => [
                'use' => 'simplest',
                'default' => 'dark',
            ],
            'themes' => [
                'simplest' => [
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
                'mdshelf' => [
                    'dark' => [
                        'bg' => '#09090b',
                        'panel' => '#18181b',
                        'surface' => '#18181b',
                        'surface_hover' => '#27272a',
                        'text' => '#9e9ea8',
                        'text_heading' => '#d4d4d8',
                        'muted' => '#71717a',
                        'accent' => '#10b981',
                        'accent_hover' => '#34d399',
                        'accent_soft' => 'rgba(16, 185, 129, 0.15)',
                        'border' => '#27272a',
                        'border_subtle' => '#18181b',
                        'shadow_sm' => '0 1px 2px rgba(0, 0, 0, 0.8)',
                        'shadow_lg' => '0 20px 25px -5px rgba(0, 0, 0, 0.8), 0 10px 10px -5px rgba(0, 0, 0, 0.6)',
                    ],
                    'light' => [
                        'bg' => '#ffffff',
                        'panel' => '#fafafa',
                        'surface' => '#fafafa',
                        'surface_hover' => '#f4f4f5',
                        'text' => '#3f3f46',
                        'text_heading' => '#09090b',
                        'muted' => '#71717a',
                        'accent' => '#059669',
                        'accent_hover' => '#047857',
                        'accent_soft' => 'rgba(5, 150, 105, 0.08)',
                        'border' => '#e4e4e7',
                        'border_subtle' => '#f4f4f5',
                        'shadow_sm' => '0 1px 2px 0 rgba(0, 0, 0, 0.05)',
                        'shadow_lg' => '0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05)',
                    ],
                ],
            ]
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
     * Приводит значение настройки к bool мягко.
     *
     * Нужен там, где настройку удобнее достать из секции целиком, а не по
     * ключу (см. «protected»: его ключи содержат точки). Незакавыченное
     * «false» в YAML разбирается как bool, а «false» в кавычках приходит
     * строкой. Отсутствие ключа и любой мусор дают $default — битый конфиг
     * не должен ломать страницу.
     */
    public static function toBool(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return !in_array(strtolower(trim($value)), ['false', 'no', 'off', '0', ''], true);
        }

        if (is_int($value)) {
            return $value !== 0;
        }

        return $default;
    }

    /**
     * Папки в навигации свёрнуты по умолчанию?
     *
     * Настройка «content.folders» — перечисление «opened» или «collapsed».
     * Любое другое значение считается «opened», как и тема в defaultTheme().
     *
     * Ветка с открытым документом всё равно раскрывается — это делает
     * клиентский JS, иначе не было бы видно, где находится текущая страница.
     */
    public function foldersCollapsed(): bool
    {
        return $this->string('content.folders', self::FOLDERS_OPENED) === self::FOLDERS_COLLAPSED;
    }

    /**
     * Режим памяти раскрытых папок — «content.folders_remember».
     *
     * Значение приводится к известному перечислению: всё остальное считается
     * «none», как и в defaultTheme(). Смысл режимов — в константах
     * FOLDERS_REMEMBER_*.
     *
     * Настройка не меняет то, как папки выглядят при первой отрисовке: это
     * по-прежнему «content.folders». Разница — только в том, что происходит
     * после клика и при переходе по ссылке.
     */
    public function foldersRemember(): string
    {
        $mode = $this->string('content.folders_remember', self::FOLDERS_REMEMBER_NONE);

        return match ($mode) {
            self::FOLDERS_REMEMBER_PER_FOLDER, self::FOLDERS_REMEMBER_SINGLE => $mode,
            default => self::FOLDERS_REMEMBER_NONE,
        };
    }

    /**
     * Набор переменных темы: «dark» или «light».
     *
     * @return array<string,string>
     */
    public function themeVariables(string $theme): array
    {
        $themes = $this->get('themes', []);
        if (!is_array($themes)) {
            return [];
        }

        $use = $this->useTheme();
        $set = $themes[$use] ?? [];

        if (!is_array($set)) {
            return [];
        }

        $variables = $set[$theme] ?? [];

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

    /** Какое название темы используется (theme.use). */
    public function useTheme(): string
    {
        $section = $this->get('theme', []);
        $use = is_array($section) ? ($section['use'] ?? 'simplest') : 'simplest';

        return is_string($use) && $use !== '' ? $use : 'simplest';
    }


    /** Какая тема показывается по умолчанию. */
    public function defaultTheme(): string
    {
        $section = $this->get('theme', []);
        $default = is_array($section) ? ($section['default'] ?? self::DARK) : self::DARK;

        return in_array($default, [self::DARK, self::LIGHT], true) ? $default : self::DARK;
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
