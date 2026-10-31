<?php
declare(strict_types=1);

namespace App\Units;

use App\Config;

/**
 * Каталоги ассетов и URL-префиксы, под которыми они доступны.
 *
 * Секция «assets.paths» принимает карту «URL-префикс → каталог» или список
 * каталогов. Список монтирует каждый каталог под его именем: «assets» →
 * «/assets». Относительные пути разрешаются от каталога контента, абсолютные
 * используются как есть. Без секции «assets» каталог «assets» внутри контента
 * доступен по адресу «/assets».
 */
final class Assets
{
    private const MIME_TYPES = [
        'css' => 'text/css; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'mjs' => 'text/javascript; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'svg' => 'image/svg+xml',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject',
        'pdf' => 'application/pdf',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'wav' => 'audio/wav',
    ];

    /** @var array<string,string> URL-префикс → абсолютный каталог */
    private readonly array $mounts;

    public function __construct(Config $config, string $contentDir)
    {
        $this->mounts = self::mounts($config->get('assets', null), $contentDir);
    }

    /**
     * Запрос относится к одному из смонтированных каталогов?
     */
    public function handles(string $urlPath): bool
    {
        return $this->match($urlPath) !== null;
    }

    /**
     * Абсолютный путь к запрошенному файлу; null — файла нет или путь выходит
     * за пределы смонтированного каталога.
     */
    public function resolve(string $urlPath): ?string
    {
        $match = $this->match($urlPath);
        if ($match === null) {
            return null;
        }

        [$root, $relative] = $match;

        $segments = [];
        foreach (explode('/', str_replace('\\', '/', rawurldecode($relative))) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..' || str_contains($segment, "\0")) {
                return null;
            }

            $segments[] = $segment;
        }

        if ($segments === []) {
            return null;
        }

        $file = $root . '/' . implode('/', $segments);
        if (!is_file($file) || !is_readable($file)) {
            return null;
        }

        $realFile = realpath($file);
        $realRoot = realpath($root);

        if ($realFile === false || $realRoot === false) {
            return null;
        }

        if ($realFile !== $realRoot && !str_starts_with($realFile, $realRoot . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return $realFile;
    }

    public static function mimeType(string $file): string
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (isset(self::MIME_TYPES[$extension])) {
            return self::MIME_TYPES[$extension];
        }

        if (class_exists(\finfo::class)) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file);

            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }

    /**
     * @return array{0:string,1:string}|null [каталог, путь внутри каталога]
     */
    private function match(string $urlPath): ?array
    {
        $path = '/' . ltrim(str_replace('\\', '/', $urlPath), '/');

        foreach ($this->mounts as $prefix => $root) {
            if ($path === $prefix) {
                return [$root, ''];
            }

            if (str_starts_with($path, $prefix . '/')) {
                return [$root, substr($path, strlen($prefix) + 1)];
            }
        }

        return null;
    }

    /**
     * @return array<string,string>
     */
    private static function mounts(mixed $section, string $contentDir): array
    {
        $paths = null;

        if (is_array($section) && array_key_exists('paths', $section)) {
            $paths = $section['paths'];
        } elseif (is_array($section) && $section !== []) {
            $paths = $section;
        }

        if (!is_array($paths)) {
            $paths = ['/assets' => 'assets'];
        }

        $mounts = [];
        $list = array_is_list($paths);

        foreach ($paths as $key => $value) {
            if (!is_string($value) && !is_int($value)) {
                continue;
            }

            $path = trim((string)$value);
            if ($path === '') {
                continue;
            }

            if ($list) {
                $prefix = '/' . basename(str_replace('\\', '/', $path));
            } else {
                $prefix = '/' . trim(str_replace('\\', '/', (string)$key), '/');
            }

            if ($prefix === '/') {
                continue;
            }

            $mounts[$prefix] = self::absolute($path, $contentDir);
        }

        uksort(
            $mounts,
            static fn (string $a, string $b): int => strlen($b) <=> strlen($a),
        );

        return $mounts;
    }

    private static function absolute(string $path, string $contentDir): string
    {
        if (Path::isAbsolute($path)) {
            return Path::normalize($path);
        }

        return Path::normalize($contentDir . '/' . $path);
    }
}
