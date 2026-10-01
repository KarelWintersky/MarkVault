<?php
declare(strict_types=1);

namespace App\Auth;

use App\Support\Path;

/**
 * Правила защиты: включена ли она, какие файлы закрыты, подписи интерфейса.
 *
 * Секция «protected» целиком опциональна. Пустой пароль («» или одни пробелы)
 * выключает защиту так же, как и отсутствие секции. «0» — валидный пароль.
 */
final class Protection
{
    /**
     * @param array<string,mixed> $config секция «protected»
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * @param array<string,mixed> $config весь конфиг
     */
    public static function fromConfig(array $config): self
    {
        $protected = $config['protected'] ?? [];

        return new self(is_array($protected) ? $protected : []);
    }

    /**
     * Защита включена, только если задан непустой пароль.
     */
    public function isEnabled(): bool
    {
        if (!array_key_exists('password', $this->config)) {
            return false;
        }

        $password = $this->config['password'];

        // Разрешаем только строку/число, всё остальное — выключено.
        if (!is_string($password) && !is_int($password) && !is_float($password)) {
            return false;
        }

        return trim((string)$password) !== '';
    }

    /**
     * Закрыт ли конкретный файл. Принимает и точный путь файла, и префикс
     * папки: «internal/» закрывает всё внутри.
     */
    public function protects(string $relative): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $files = $this->config['files'] ?? [];
        if (!is_array($files) || $files === []) {
            return false;
        }

        $relative = Path::key($relative);

        foreach ($files as $pattern) {
            $pattern = Path::key((string)$pattern);

            if ($pattern === '') {
                continue;
            }

            if (str_ends_with($pattern, '/')) {
                if (str_starts_with($relative, $pattern)) {
                    return true;
                }
                continue;
            }

            if ($relative === $pattern || str_starts_with($relative, $pattern . '/')) {
                return true;
            }
        }

        return false;
    }

    public function password(): string
    {
        return (string)$this->config['password'];
    }

    public function lockIcon(): string
    {
        return (string)($this->config['lock_icon'] ?? '🔒');
    }

    public function title(): string
    {
        return (string)($this->config['title'] ?? 'Файл защищён');
    }

    /** Подсказка в модальном окне. */
    public function hint(): string
    {
        return (string)($this->config['hint'] ?? 'Введите пароль для доступа');
    }

    /** Плейсхолдер поля пароля в боковой панели. */
    public function passwordFieldPlaceholder(): string
    {
        return (string)($this->config['hint'] ?? 'Пароль');
    }
}