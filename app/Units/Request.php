<?php
declare(strict_types=1);

namespace App\Units;

/**
 * Тонкая обёртка над суперглобалами: держит входные данные запроса в одном
 * месте и позволяет подменить их (тесты, CLI).
 */
final class Request
{
    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed> $post
     * @param array<string,mixed> $cookies
     * @param array<string,mixed> $server
     */
    public function __construct(
        private readonly array $query = [],
        private readonly array $post = [],
        private readonly array $cookies = [],
        private readonly array $server = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        return new self($_GET, $_POST, $_COOKIE, $_SERVER);
    }

    /** Значение из query-строки; null — параметр отсутствует. */
    public function query(string $key, ?string $default = null): ?string
    {
        return $this->scalar($this->query[$key] ?? $default);
    }

    public function post(string $key, ?string $default = null): ?string
    {
        return $this->scalar($this->post[$key] ?? $default);
    }

    public function hasPost(string $key): bool
    {
        return isset($this->post[$key]);
    }

    public function cookie(string $key, ?string $default = null): ?string
    {
        return $this->scalar($this->cookies[$key] ?? $default);
    }

    public function server(string $key, ?string $default = null): ?string
    {
        return $this->scalar($this->server[$key] ?? $default);
    }

    public function method(): string
    {
        return $this->server('REQUEST_METHOD', '') ?? '';
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    /**
     * Путь запроса без query-строки — цель редиректа после выхода.
     */
    public function uriPath(): string
    {
        $path = strtok($this->server('REQUEST_URI', '') ?? '', '?');

        return $path === false ? '' : $path;
    }

    /** Массивы и объекты в строку не превращаем — возвращаем null. */
    private function scalar(mixed $value): ?string
    {
        if ($value === null || is_array($value) || is_object($value)) {
            return null;
        }

        return (string)$value;
    }
}