<?php
declare(strict_types=1);

namespace App\DTO;

/**
 * Один найденный markdown-документ.
 */
final class Document
{
    public function __construct(
        /** Заголовок из секции «titles» или имя файла без расширения. */
        public readonly string $name,
        /** Имя файла без пути — для сравнения по короткому параметру «?file=». */
        public readonly string $file,
        /** Абсолютный путь на диске. */
        public readonly string $path,
        /** Путь от корня контента — он же ключ везде в интерфейсе. */
        public readonly string $relative,
        public readonly int $mtime,
    ) {
    }
}