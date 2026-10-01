<?php
declare(strict_types=1);

namespace App\Content;

use App\Support\Path;

/**
 * Карта человекочитаемых названий из секции «titles».
 *
 * Ключ — относительный путь («00-overview.md» для файла, «guides/» для папки),
 * значение — подпись в навигации.
 */
final class TitleResolver
{
    /**
     * @param array<string,string> $titles нормализованная карта
     */
    public function __construct(private readonly array $titles = [])
    {
    }

    /**
     * @param array<string,mixed> $config весь конфиг
     */
    public static function fromConfig(array $config): self
    {
        $titles = $config['titles'] ?? [];

        if (!is_array($titles)) {
            return new self();
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

        return new self($map);
    }

    /**
     * Название файла или папки; если его нет в карте — $fallback.
     *
     * @param string $relative «guides/install.md» или «guides/»
     * @param string $fallback имя файла без расширения либо имя папки
     */
    public function resolve(string $relative, string $fallback): string
    {
        $relative = Path::key($relative);

        return $this->titles[$relative]
            ?? $this->titles[$relative . '/']
            ?? $fallback;
    }
}