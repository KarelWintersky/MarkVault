<?php
declare(strict_types=1);

namespace App\View;

/**
 * Сборка страницы целиком: готовит переменные для разметки и подключает
 * шаблон layout.php. Шаблон — единственное место, где есть HTML.
 */
final class Layout
{
    private const TEMPLATE = __DIR__ . '/layout.php';

    public function __construct(private readonly NavRenderer $nav)
    {
    }

    public function render(Page $page): string
    {
        $theme = $page->theme;
        $darkVars = $theme->dark();
        $lightVars = $theme->light();
        $defaultTheme = $theme->default();

        $protection = $page->protection;
        $protectionEnabled = $protection->isEnabled();
        $isAuthorized = $page->isAuthorized;

        $siteTitle = $page->siteTitle;
        $navTitle = $page->navTitle;
        $title = $page->title !== '' ? $page->title : $siteTitle;
        $debugInfo = $page->notice;
        $hasDocuments = $page->hasDocuments;

        $current = $page->current;
        $content = $page->content;
        $breadcrumbs = $page->breadcrumbs;

        $tree = $this->nav->render(
            $page->tree,
            $page->current !== null ? $page->current->relative : '',
        );

        $saveToLocalStorage = $page->savePasswordToStorage;
        $submittedPassword = $page->submittedPassword;

        ob_start();
        require self::TEMPLATE;

        return (string)ob_get_clean();
    }
}