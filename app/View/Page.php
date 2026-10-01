<?php
declare(strict_types=1);

namespace App\View;

use App\Auth\Protection;
use App\Config\Theme;
use App\Content\Document;

/**
 * Всё, что шаблон страницы должен знать о текущем запросе.
 */
final class Page
{
    /**
     * @param string $siteTitle из «site.title»
     * @param string $navTitle из «site.nav_title»
     * @param string $title заголовок открытого документа или страницы
     * @param string $notice предупреждение вместо навигации (например, «.md не найдены»)
     * @param bool $hasDocuments нашлись ли вообще markdown-файлы
     * @param array<int|string,array<string,mixed>> $tree дерево навигации
     * @param Document|null $current открытый документ
     * @param string $content готовый HTML документа
     * @param array<int,array{name:string,path:string,isFile:bool}> $breadcrumbs
     * @param bool $isAuthorized пустил ли пользователя внутрь
     * @param bool $savePasswordToStorage нужно ли продублировать пароль в localStorage
     * @param string $submittedPassword введённый пароль (для localStorage)
     */
    public function __construct(
        public readonly Theme $theme,
        public readonly Protection $protection,
        public readonly string $siteTitle,
        public readonly string $navTitle,
        public readonly string $title,
        public readonly string $notice,
        public readonly bool $hasDocuments,
        public readonly array $tree,
        public readonly ?Document $current,
        public readonly string $content,
        public readonly array $breadcrumbs,
        public readonly bool $isAuthorized,
        public readonly bool $savePasswordToStorage,
        public readonly string $submittedPassword,
    ) {
    }
}