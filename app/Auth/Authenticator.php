<?php
declare(strict_types=1);

namespace App\Auth;

use App\Http\Request;

/**
 * Аутентификация по паролю из config.yaml.
 *
 * Пароль принимается тремя способами, в порядке приоритета:
 *   1. POST-поле «password» — форма входа;
 *   2. кука — запомненный пароль (base64, потому что в конфиге он лежит открытым текстом);
 *   3. заголовок «X-Auth-Token» — для внешних клиентов и health-checks.
 */
final class Authenticator
{
    public const COOKIE_NAME = 'md_docs_auth';
    public const COOKIE_LIFETIME = 2592000;
    public const TOKEN_HEADER = 'HTTP_X_AUTH_TOKEN';

    private ?string $submittedPassword = null;

    public function __construct(
        private readonly Request $request,
        private readonly Protection $protection,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->protection->isEnabled();
    }

    public function isLogoutRequested(): bool
    {
        return $this->request->query('logout') !== null;
    }

    /**
     * Пароль, пришедший с текущим запросом ('' — ничего не пришло).
     */
    public function submittedPassword(): string
    {
        return $this->submittedPassword ??= $this->resolvePassword();
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
        if (!$this->protection->isEnabled()) {
            return true;
        }

        return hash_equals(trim($this->protection->password()), $this->submittedPassword());
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