<?php
declare(strict_types=1);

namespace App\Content;

/**
 * Выбор документа, который нужно показать.
 *
 * Приоритет параметра «?file=»: сначала полный относительный путь, затем
 * короткое имя файла. Если ничего не нашлось — первый документ в списке,
 * чтобы страница не осталась пустой.
 */
final class DocumentSelector
{
    public function __construct(private readonly string $defaultFile = 'README.md')
    {
    }

    /**
     * @param Document[] $documents
     * @param string|null $requested значение «?file=» или null
     */
    public function select(array $documents, ?string $requested): ?Document
    {
        $needle = $requested ?? $this->defaultFile;

        foreach ($documents as $document) {
            if ($document->relative === $needle || $document->file === $needle) {
                return $document;
            }
        }

        return $documents[0] ?? null;
    }
}