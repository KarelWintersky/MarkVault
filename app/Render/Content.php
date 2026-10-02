<?php
declare(strict_types=1);

namespace App\Render;

use App\Config;
use App\DTO\Document;
use App\Units\Path;

/**
 * Каталог контента: что в нём лежит, в каком порядке показывать и какую
 * страницу открыть.
 *
 * Карта названий из секции «titles» тоже здесь — она применяется к именам
 * файлов и папок, то есть к содержимому каталога.
 *
 * Порядок документов:
 *   1. «sort.before_all» — в порядке перечисления в конфиге;
 *   2. остальные — natural sort по относительному пути;
 *   3. «sort.after_all» — в порядке перечисления в конфиге.
 *
 * before_all приоритетнее: файл из обоих списков остаётся в before.
 */
final class Content
{
    private const MARKDOWN_PATTERN = '/\.md$/i';

    private const GROUP_BEFORE = 0;
    private const GROUP_NORMAL = 1;
    private const GROUP_AFTER = 2;

    /** @var array<string,string> относительный путь → подпись */
    private readonly array $titles;

    /** @var array<int,string> имена файлов и папок, которые не показываем */
    private readonly array $hide;

    /** @var array<string,int> путь → порядковый номер в before_all */
    private readonly array $before;

    /** @var array<string,int> путь → порядковый номер в after_all */
    private readonly array $after;

    private readonly string $defaultFile;

    /** @var Document[]|null ленивый кэш, чтобы каталог не сканировался дважды */
    private ?array $documents = null;

    public function __construct(
        private readonly Config $config,
        private readonly string $contentDir,
    ) {
        $this->titles = self::toTitleMap($config->get('titles', []));

        $hide = $config->get('hide', []);
        $this->hide = is_array($hide)
            ? array_values(array_map(strval(...), $hide))
            : [];

        $sort = $config->get('sort', []);
        $sort = is_array($sort) ? $sort : [];

        $before = self::toOrderMap($sort['before_all'] ?? []);
        $after = self::toOrderMap($sort['after_all'] ?? []);

        // before_all приоритетнее: файл из обоих списков остаётся в before.
        foreach (array_keys($before) as $path) {
            unset($after[$path]);
        }

        $this->before = $before;
        $this->after = $after;

        $this->defaultFile = $config->string('content.default_file', 'README.md');
    }

    /**
     * Все документы каталога, уже в порядке навигации.
     *
     * @return Document[]
     */
    public function all(): array
    {
        return $this->documents ??= $this->sort($this->scanDirectory($this->contentDir, ''));
    }

    /**
     * Выбор документа, который нужно показать.
     *
     * Приоритет параметра «?file=»: сначала полный относительный путь, затем
     * короткое имя файла. Если ничего не нашлось — первый документ в списке,
     * чтобы страница не осталась пустой.
     *
     * @param string|null $requested значение «?file=» или null
     */
    public function select(?string $requested): ?Document
    {
        $needle = $requested ?? $this->defaultFile;

        foreach ($this->all() as $document) {
            if ($document->relative === $needle || $document->file === $needle) {
                return $document;
            }
        }

        return $this->all()[0] ?? null;
    }

    /**
     * Название файла или папки; если его нет в карте — $fallback.
     *
     * @param string $relative «guides/install.md» или «guides/»
     * @param string $fallback имя файла без расширения либо имя папки
     */
    public function resolveTitle(string $relative, string $fallback): string
    {
        $relative = Path::key($relative);

        return $this->titles[$relative]
            ?? $this->titles[$relative . '/']
            ?? $fallback;
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
                $this->resolveTitle($relative, pathinfo($item, PATHINFO_FILENAME)),
                $item,
                $fullPath,
                $relative,
                (int)filemtime($fullPath),
            );
        }

        return $documents;
    }

    private static function isMarkdown(string $path): bool
    {
        return is_file($path) && preg_match(self::MARKDOWN_PATTERN, $path) === 1;
    }

    /**
     * @param Document[] $documents
     * @return Document[]
     */
    private function sort(array $documents): array
    {
        usort($documents, function (Document $a, Document $b): int {
            [$groupA, $indexA] = $this->weight($a->relative);
            [$groupB, $indexB] = $this->weight($b->relative);

            if ($groupA !== $groupB) {
                return $groupA <=> $groupB;
            }

            // Внутри before_all/after_all порядок задаёт конфиг…
            if ($groupA !== self::GROUP_NORMAL) {
                return $indexA <=> $indexB;
            }

            // …а внутри основной группы — natural sort по пути.
            return strnatcmp($a->relative, $b->relative);
        });

        return $documents;
    }

    /**
     * Группа и позиция документа. Меньше — выше в списке.
     *
     * @return array{0:int,1:int}
     */
    private function weight(string $relative): array
    {
        $relative = Path::key($relative);

        // Для папки конфиг может содержать и «guides», и «guides/» — проверяем оба.
        if (isset($this->before[$relative])) {
            return [self::GROUP_BEFORE, $this->before[$relative]];
        }
        if (isset($this->before[$relative . '/'])) {
            return [self::GROUP_BEFORE, $this->before[$relative . '/']];
        }
        if (isset($this->after[$relative])) {
            return [self::GROUP_AFTER, $this->after[$relative]];
        }
        if (isset($this->after[$relative . '/'])) {
            return [self::GROUP_AFTER, $this->after[$relative . '/']];
        }

        return [self::GROUP_NORMAL, 0];
    }

    /**
     * Секция «titles» превращается в карту «путь → подпись», мусор отбрасывается.
     *
     * ВНИМАНИЕ, ловушка синтаксиса: ключи секции содержат точки
     * («05a-landing.md»), а Config::get() использует точку как разделитель
     * вложенности. Поэтому читать «titles» по частям нельзя —
     * get('titles.05a-landing.md') вернёт default, а не подпись.
     * Работает только чтение секции целиком, как здесь. Настройку для
     * отдельного файла придётся держать вне секции «titles».
     *
     * @return array<string,string>
     */
    private static function toTitleMap(mixed $titles): array
    {
        if (!is_array($titles)) {
            return [];
        }

        $map = [];

        foreach ($titles as $path => $label) {
            $path = Path::key((string)$path);
            $label = trim((string)$label);

            if ($path === '' || $label === '') {
                continue;
            }

            $map[$path] = $label;
        }

        return $map;
    }

    /**
     * Список превращается в карту «путь → порядковый номер», дублики игнорируются.
     *
     * @return array<string,int>
     */
    private static function toOrderMap(mixed $list): array
    {
        if (!is_array($list)) {
            return [];
        }

        $out = [];
        $index = 0;

        foreach ($list as $path) {
            $path = Path::key((string)$path);

            if ($path === '' || isset($out[$path])) {
                continue;
            }

            $out[$path] = $index++;
        }

        return $out;
    }
}
