<?php
declare(strict_types=1);

namespace App\Content;

use App\Support\Path;

/**
 * Сортировка документов по секции «sort» конфигурации.
 *
 * Порядок в навигации:
 *   1. «sort.before_all» — в порядке перечисления в конфиге;
 *   2. остальные — natural sort по относительному пути;
 *   3. «sort.after_all» — в порядке перечисления в конфиге.
 *
 * before_all приоритетнее: файл из обоих списков остаётся в before.
 */
final class SortRules
{
    public const GROUP_BEFORE = 0;
    public const GROUP_NORMAL = 1;
    public const GROUP_AFTER = 2;

    /**
     * @param array<string,int> $before
     * @param array<string,int> $after
     */
    private function __construct(
        private readonly array $before,
        private readonly array $after,
    ) {
    }

    public static function none(): self
    {
        return new self([], []);
    }

    /**
     * @param array<string,mixed> $config весь конфиг
     */
    public static function fromConfig(array $config): self
    {
        $sort = $config['sort'] ?? [];

        if (!is_array($sort)) {
            return self::none();
        }

        $before = self::normalize($sort['before_all'] ?? []);
        $after = self::normalize($sort['after_all'] ?? []);

        foreach (array_keys($before) as $path) {
            unset($after[$path]);
        }

        return new self($before, $after);
    }

    /**
     * Группа и позиция документа. Меньше — выше в списке.
     *
     * @return array{0:int,1:int}
     */
    public function weight(string $relative): array
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
     * @param Document[] $documents
     * @return Document[]
     */
    public function apply(array $documents): array
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
     * Список превращается в карту «путь → порядковый номер», дублики игнорируются.
     *
     * @return array<string,int>
     */
    private static function normalize(mixed $list): array
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