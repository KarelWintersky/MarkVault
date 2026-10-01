<?php
declare(strict_types=1);

namespace App\View;

use App\Auth\Protection;
use App\Content\Document;

/**
 * Отрисовка дерева навигации в HTML.
 *
 * Заблокированные файлы получают класс «locked», иконку и data-атрибуты —
 * по ним клиентский JS открывает модалку ввода пароля.
 */
final class NavRenderer
{
    public function __construct(
        private readonly Protection $protection,
        private readonly bool $isAuthorized,
    ) {
    }

    /**
     * @param array<int|string,array<string,mixed>> $tree результат NavTreeBuilder
     * @param string $currentRelative относительный путь открытого документа
     * @param int $depth уровень вложенности (2 отступа на уровень)
     */
    public function render(array $tree, string $currentRelative, int $depth = 0): string
    {
        $html = [];
        $indent = str_repeat('  ', $depth);

        foreach ($tree as $key => $node) {
            if ($node['_type'] === 'dir') {
                $html = array_merge(
                    $html,
                    $this->renderDirectory($node, (string)$key, $currentRelative, $indent, $depth),
                );
                continue;
            }

            if ($node['_type'] === 'file') {
                $html[] = $this->renderFile($node['_data'], $currentRelative, $indent);
            }
        }

        return implode("\n", $html);
    }

    /**
     * @param array<string,mixed> $node
     * @param string[] $html
     * @return string[]
     */
    private function renderDirectory(array $node, string $key, string $currentRelative, string $indent, int $depth): array
    {
        $label = self::escape((string)($node['_label'] ?? $key));

        return [
            "{$indent}<li class=\"dir\">",
            "{$indent}  <details open>",
            "{$indent}    <summary>{$label}</summary>",
            "{$indent}    <ul>",
            $this->render($node['_children'], $currentRelative, $depth + 2),
            "{$indent}    </ul>",
            "{$indent}  </details>",
            "{$indent}</li>",
        ];
    }

    private function renderFile(Document $document, string $currentRelative, string $indent): string
    {
        $isActive = $currentRelative !== '' && $document->relative === $currentRelative;
        $activeClass = $isActive ? ' class="active"' : '';

        $locked = $this->protection->protects($document->relative) && !$this->isAuthorized;

        $relative = self::escape($document->relative);
        $href = '?file=' . $relative;
        $label = self::escape($document->name);

        $liClass = $locked ? ' class="locked"' : '';

        // data-protected нужен JS для перехвата клика.
        $dataAttr = $locked ? ' data-protected="1" data-file="' . $relative . '"' : '';

        $lockBadge = $locked
            ? ' <span class="lock-badge" title="Защищено">' . $this->protection->lockIcon() . '</span>'
            : '';

        return "{$indent}<li{$liClass}><a href=\"{$href}\"{$activeClass}{$dataAttr}>{$label}{$lockBadge}</a></li>";
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}