<?php
declare(strict_types=1);

namespace App;

use App\Units\Path;
use App\Units\Request;

/**
 * Парольная защита документов и аутентификация.
 *
 * Пароль принимается тремя способами, в порядке приоритета:
 *   1. POST-поле «password» — форма входа;
 *   2. кука — запомненный пароль (base64, потому что в конфиге он лежит открытым текстом);
 *   3. заголовок «X-Auth-Token» — для внешних клиентов и health-checks.
 *
 * Секция «protected» целиком опциональна. Пустой пароль («» или одни пробелы)
 * выключает защиту так же, как и отсутствие секции. «0» — валидный пароль.
 */
final class Auth
{
    public const COOKIE_NAME = 'md_docs_auth';
    public const COOKIE_LIFETIME = 2592000;
    public const TOKEN_HEADER = 'HTTP_X_AUTH_TOKEN';

    /** @var array<string,mixed> секция «protected» */
    private readonly array $rules;

    private ?string $submittedPassword = null;

    public function __construct(Config $config, private readonly Request $request)
    {
        $protected = $config->get('protected', []);

        $this->rules = is_array($protected) ? $protected : [];
    }

    /**
     * Защита включена, только если задан непустой пароль.
     */
    public function isEnabled(): bool
    {
        if (!array_key_exists('password', $this->rules)) {
            return false;
        }

        $password = $this->rules['password'];

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

        $files = $this->rules['files'] ?? [];
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

    public function isLogoutRequested(): bool
    {
        return $this->request->query('logout') !== null;
    }

    /**
     * Прислал ли пользователь пароль формой в этой же записи-запросе.
     */
    public function isPasswordSubmitted(): bool
    {
        return $this->request->isPost() && $this->request->hasPost('password');
    }

    /**
     * Разрешён ли доступ. При выключенной защите разрешён всегда.
     */
    public function isAuthorized(): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        return hash_equals(trim($this->password()), $this->submittedPassword());
    }

    /**
     * Пароль, пришедший с текущим запросом ('' — ничего не пришло).
     */
    public function submittedPassword(): string
    {
        return $this->submittedPassword ??= $this->resolvePassword();
    }

    /**
     * Запоминает пароль на 30 дней.
     */
    public function remember(): void
    {
        $this->setCookie(
            base64_encode($this->submittedPassword()),
            time() + self::COOKIE_LIFETIME,
        );
    }

    /**
     * Сбрасывает запомненный пароль.
     */
    public function forget(): void
    {
        $this->setCookie('', time() - 3600);
    }

    public function lockIcon(): string
    {
        return (string)($this->rules['lock_icon'] ?? '🔒');
    }

    /**
     * Показывать ли защищённые файлы в оглавлении.
     *
     * Ключ «protected.visible». По умолчанию включён — без него поведение
     * прежнее: файл виден всем с замочком и открывается по клику после
     * ввода пароля.
     *
     * При «visible: false» файл скрыт от тех, кто ещё не вошёл: вместо
     * замочка его нет в списке вообще. Вошедшим с паролем он, наоборот,
     * виден, как обычно — скрывать надо от гостей, а не от тех, кому пароль
     * уже введён. Проверка пароля сама по себе не слабеет.
     */
    public function showsProtectedInNavigation(): bool
    {
        return Config::toBool($this->rules['visible'] ?? null, true);
    }

    public function title(): string
    {
        return (string)($this->rules['title'] ?? 'Файл защищён');
    }

    /** Подсказка в модальном окне. */
    public function hint(): string
    {
        return (string)($this->rules['hint'] ?? 'Введите пароль для доступа');
    }

    /** Плейсхолдер поля пароля в боковой панели. */
    public function passwordFieldPlaceholder(): string
    {
        return (string)($this->rules['hint'] ?? 'Пароль');
    }

    private function password(): string
    {
        return (string)$this->rules['password'];
    }

    private function resolvePassword(): string
    {
        if ($this->isPasswordSubmitted()) {
            return $this->request->post('password', '') ?? '';
        }

        $cookie = $this->request->cookie(self::COOKIE_NAME, '') ?? '';
        if ($cookie !== '') {
            return $this->decodeBase64($cookie);
        }

        $token = $this->request->server(self::TOKEN_HEADER, '') ?? '';
        if ($token !== '') {
            return $this->decodeBase64($token);
        }

        return '';
    }

    private function decodeBase64(string $value): string
    {
        $decoded = base64_decode($value, true);

        return $decoded === false ? '' : $decoded;
    }

    private function setCookie(string $value, int $expires): void
    {
        setcookie(self::COOKIE_NAME, $value, [
            'expires' => $expires,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
