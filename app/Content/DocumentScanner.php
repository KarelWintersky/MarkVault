<?php
declare(strict_types=1);

namespace App\Content;

/**
 * Рекурсивный поиск markdown-файлов в каталоге контента.
 */
final class DocumentScanner
{
    private const MARKDOWN_PATTERN = '/\.md$/i';

    /**
     * @param string $contentDir корень контента
     * @param TitleResolver $titles названия из конфига
     * @param array<int,string> $hide имена файлов и папок, которые не показываем
     */
    public function __construct(
        private readonly string $contentDir,
        private readonly TitleResolver $titles = new TitleResolver(),
        private readonly array $hide = [],
    ) {
    }

    /**
     * @return Document[] в произвольном (как правило, файловом) порядке
     */
    public function scan(): array
    {
        return $this->scanDirectory($this->contentDir, '');
    }

    public static function isMarkdown(string $path): bool
    {
        return is_file($path) && preg_match(self::MARKDOWN_PATTERN, $path) === 1;
    }

    /**
     * @return Document[]
     */
    private function scanDirectory(string $dir, string $basePath): array
    {
        $documents = [];

        $items = @scandir($dir);
        if ($items === false) {
            return $documents;
        }

        foreach ($items as $item) {
            if (in_array($item, $this->hide, true) || str_starts_with($item, '.')) {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $item;
            $relative = $basePath === '' ? $item : $basePath . '/' . $item;

            if (is_dir($fullPath)) {
                $documents = array_merge($documents, $this->scanDirectory($fullPath, $relative));
                continue;
            }

            if (!self::isMarkdown($fullPath)) {
                continue;
            }

            $documents[] = new Document(
                $this->titles->resolve($relative, pathinfo($item, PATHINFO_FILENAME)),
                $item,
                $fullPath,
                $relative,
                (int)filemtime($fullPath),
            );
        }

        return $documents;
    }
}