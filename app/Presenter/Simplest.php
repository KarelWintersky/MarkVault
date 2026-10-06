<?php

namespace App\Presenter;

use RuntimeException;

/**
 * Простейший шаблонизатор: обычный PHP-файл, подключённый через require.
 *
 * Переменные распаковываются в локальную область через extract(),
 * поэтому внутри шаблона они доступны как $title, $tree и т.д.
 *
 * Важно: EXTR_SKIP не даст шаблону перезаписать $template и $vars —
 * если в шаблоне случайно окажется переменная с таким именем,
 * она просто не будет перезаписана.
 */
final class Simplest implements PresenterInterface
{
    private const DEFAULT_DARK = [
        'bg'      => '#0f1115',
        'panel'   => '#161920',
        'text'    => '#e6e8eb',
        'muted'   => '#9aa0a6',
        'accent'  => '#5b9df9',
        'code_bg' => '#1e222a',
        'border'  => '#2a2f3a',
    ];

    private const DEFAULT_LIGHT = [
        'bg'      => '#faf9f7',
        'panel'   => '#f2f0eb',
        'text'    => '#232323',
        'muted'   => '#6b6b6b',
        'accent'  => '#0366d6',
        'code_bg' => '#f6f8fa',
        'border'  => '#e1e4e8',
    ];

    public function __construct(
        private readonly string $templateDir,
    ) {
    }

    public function render(string $template, array $vars = []): string
    {
        $file = $this->resolve($template);

        // Изолируем область видимости: extract() не должен течь наружу.
        $render = static function (string $__file, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            unset($__vars);

            ob_start();
            try {
                require $__file;
            } catch (\Throwable $e) {
                ob_end_clean();
                throw $e;
            }

            return (string) ob_get_clean();
        };

        return $render($file, $vars);
    }

    private function resolve(string $template): string
    {
        $file = rtrim($this->templateDir, '/\\')
            . DIRECTORY_SEPARATOR
            . str_replace(['..', "\0"], '', $template)
            . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("Template not found: {$file}");
        }

        return $file;
    }
}