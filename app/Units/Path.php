<?php
declare(strict_types=1);

namespace App\Units;

/**
 * Утилиты для работы с путями: относительные пути внутри контента и
 * разрешение каталогов конфигурации.
 */
final class Path
{
    private function __construct()
    {
    }

    /**
     * Приводит разделители к «/» и убирает ведущий слэш — так относительные
     * пути выглядят одинаково в конфиге, в навигации и в HTML.
     */
    public static function key(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Абсолютный ли путь: «/var/www», «C:\www» или UNC «\\host\share».
     */
    public static function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('~^[a-zA-Z]:[/\\\\]~', $path) === 1;
    }

    /**
     * Разворачивает «.» и «..», сохраняя признак абсолютности.
     * «a/./b/../c» → «a/c», «/a/./b» → «/a/b».
     */
    public static function normalize(string $path): string
    {
        $absolute = self::isAbsolute($path);

        $result = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                // Выход за корень абсолютного пути игнорируем, как это делает ФС.
                if ($result === [] && $absolute) {
                    continue;
                }
                array_pop($result);
                continue;
            }
            $result[] = $part;
        }

        $normalized = implode('/', $result);

        return $absolute ? '/' . $normalized : $normalized;
    }
}