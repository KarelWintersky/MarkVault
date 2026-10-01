<?php
declare(strict_types=1);

namespace App\Render;

use App\Units\Path;
use Parsedown;

/**
 * Markdown → HTML с починкой внутренних ссылок.
 *
 * Ссылки на «*.md» внутри документа превращаются в «?file=…», чтобы
 * навигация работала без веб-сервера, отдающего .md как есть.
 */
final class Markdown
{
    private const LINK_PATTERN = '/<a\s+href="([^"]*\.md(?:#[^"]*)?)"([^>]*)>/i';

    private Parsedown $parsedown;

    public function __construct(?Parsedown $parsedown = null)
    {
        $this->parsedown = $parsedown ?? new Parsedown();
        $this->parsedown->setMarkupEscaped(false);
        $this->parsedown->setBreaksEnabled(true);
    }

    /**
     * @param string $markdown исходный текст файла
     * @param string $currentFile относительный путь файла — база для ссылок
     */
    public function render(string $markdown, string $currentFile): string
    {
        return $this->convertInternalLinks(
            $this->parsedown->text($markdown),
            $currentFile,
        );
    }

    /**
     * Заголовок документа: первая строка вида «# Заголовок», иначе $fallback.
     */
    public function title(string $markdown, string $fallback): string
    {
        $firstLine = trim(explode("\n", $markdown)[0] ?? '');

        return preg_match('/^#\s+(.*)/', $firstLine, $matches) === 1
            ? trim($matches[1])
            : $fallback;
    }

    private function convertInternalLinks(string $html, string $currentFile): string
    {
        return preg_replace_callback(
            self::LINK_PATTERN,
            function (array $matches) use ($currentFile): string {
                $href = $matches[1];
                $rest = $matches[2];

                // Уже перенаправленная ссылка — не трогаем.
                if (str_contains($href, '?file=')) {
                    return $matches[0];
                }

                $parts = explode('#', $href, 2);
                $path = $parts[0];
                $anchor = isset($parts[1]) ? '#' . $parts[1] : '';

                $currentDir = dirname($currentFile);
                if ($currentDir === '.') {
                    $currentDir = '';
                }

                if (!str_starts_with($path, '/') && !str_starts_with($path, 'http')) {
                    // Относительная ссылка — строим от каталога текущего файла.
                    $target = Path::normalize($currentDir === '' ? $path : $currentDir . '/' . $path);
                } else {
                    // Абсолютная ссылка внутри контента — обрезаем корневой слэш.
                    $target = ltrim($path, '/');
                }

                $href = '?file=' . $target . $anchor;

                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $rest . '>';
            },
            $html,
        ) ?? $html;
    }
}