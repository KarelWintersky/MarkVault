<?php
declare(strict_types=1);

namespace App\Config;

/**
 * Доступ к секции «theme» конфигурации и её представление в виде
 * CSS-переменных и JS-объекта.
 */
final class Theme
{
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

    /**
     * @param array<string,mixed> $config секция «theme» целиком
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @param array<string,mixed> $config весь конфиг
     */
    public static function fromConfig(array $config): self
    {
        $theme = $config['theme'] ?? [];

        return new self(is_array($theme) ? $theme : []);
    }

    /**
     * @return array<string,string>
     */
    public function variables(string $theme): array
    {
        $variables = $this->config[$theme] ?? [];

        return is_array($variables) ? $variables : [];
    }

    /**
     * @return array<string,string>
     */
    public function dark(): array
    {
        return $this->variables(self::DARK);
    }

    /**
     * @return array<string,string>
     */
    public function light(): array
    {
        return $this->variables(self::LIGHT);
    }

    public function default(): string
    {
        $default = $this->config['default'] ?? self::DARK;

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