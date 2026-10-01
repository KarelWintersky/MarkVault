<?php
declare(strict_types=1);

namespace App\Content;

/**
 * Собирает плоский список документов в дерево навигации.
 *
 * Форма узлов:
 *   папка — ['_type' => 'dir', '_path' => 'guides/', '_label' => 'Руководства', '_children' => [...]]
 *   файл  — ['_type' => 'file', '_data' => Document]
 *
 * Порядок внутри папок наследуется из входного списка, поэтому на вход
 * нужно подать уже отсортированные документы.
 */
final class NavTreeBuilder
{
    public function __construct(private readonly TitleResolver $titles = new TitleResolver())
    {
    }

    /**
     * @param Document[] $documents
     * @return array<int|string,array<string,mixed>>
     */
    public function build(array $documents): array
    {
        $tree = [];

        foreach ($documents as $document) {
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
                        '_label' => $this->titles->resolve($path . '/', $part),
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
}