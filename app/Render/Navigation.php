<?php
declare(strict_types=1);

namespace App\Render;

use App\Auth;
use App\DTO\Document;

/**
 * Боковая навигация: из плоского списка документов строится дерево, а дерево
 * печатается в HTML.
 *
 * Форма узлов:
 *   папка — ['_type' => 'dir', '_path' => 'guides/', '_label' => 'Руководства', '_children' => [...]]
 *   файл  — ['_type' => 'file', '_data' => Document]
 *
 * Заблокированные файлы получают класс «locked», иконку и data-атрибуты —
 * по ним клиентский JS открывает модалку ввода пароля.
 */
final class Navigation
{
    public function __construct(
        private readonly Auth $auth,
        private readonly Content $content,
        private readonly bool $isAuthorized,
        private readonly bool $collapsed = false,
    ) {
    }

    /**
     * @param Document[] $documents
     * @return array<int|string,array<string,mixed>>
     */
    public function build(array $documents): array
    {
        $tree = [];

        // При «protected.visible: false» защищённые файлы скрыты от тех, кто
        // ещё не ввёл пароль; вошедшим они видны, как и при обычном поведении
        // с замочком. Отсекаем их здесь, до построения дерева, а не при
        // отрисовке: иначе в ветке остался бы пустой <details> для папки,
        // в которой для гостя закрыты все файлы.
        //
        // На выбор открытого документа не влияет — по прямой ссылке файл
        // по-прежнему открывается и спрашивает пароль.
        $hideLocked = !$this->isAuthorized && !$this->auth->showsProtectedInNavigation();

        foreach ($documents as $document) {
            if ($hideLocked && $this->auth->protects($document->relative)) {
                continue;
            }

            $parts = explode('/', $document->relative);
            $lastIndex = count($parts) - 1;

            $branch = &$tree;
            $path = '';

            for ($i = 0; $i < $lastIndex; $i++) {
                $part = $parts[$i];
                $path = $path === '' ? $part : $path . '/' . $part;

                if (!isset($branch[$part])) {
                    $branch[$part] = [
                        '_type' => 'dir',
                        '_path' => $path,
                        '_label' => $this->content->resolveTitle($path . '/', $part),
                        '_children' => [],
                    ];
                }

                $branch = &$branch[$part]['_children'];
            }

            $branch[] = ['_type' => 'file', '_data' => $document];
            unset($branch);
        }

        return $tree;
    }

    /**
     * @param array<int|string,array<string,mixed>> $tree результат build()
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
     * @param string $key
     * @param string $currentRelative
     * @param string $indent
     * @param int $depth
     *
     * @return string[]
     */
    private function renderDirectory(array $node, string $key, string $currentRelative, string $indent, int $depth): array
    {
        $label = self::escape((string)($node['_label'] ?? $key));
        $details = $this->collapsed ? '<details>' : '<details open>';

        // data-path — устойчивый ключ папки для клиентского JS: по нему
        // состояние раскрытия ищется в localStorage и записывается обратно.
        // Путь уникален в дереве, поэтому две вложенные папки не путаются.
        $path = self::escape((string)($node['_path'] ?? $key));

        return [
            "{$indent}<li class=\"dir\">",
            "{$indent}  <details data-path=\"{$path}\">",
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

        $locked = $this->auth->protects($document->relative) && !$this->isAuthorized;

        $relative = self::escape($document->relative);
        $href = '?file=' . $relative;
        $label = self::escape($document->name);

        $liClass = $locked ? ' class="locked"' : '';

        // data-protected нужен JS для перехвата клика.
        $dataAttr = $locked ? ' data-protected="1" data-file="' . $relative . '"' : '';

        $lockBadge = $locked
            ? ' <span class="lock-badge" title="Защищено">' . $this->auth->lockIcon() . '</span>'
            : '';

        return "{$indent}<li{$liClass}><a href=\"{$href}\"{$activeClass}{$dataAttr}>{$label}{$lockBadge}</a></li>";
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
