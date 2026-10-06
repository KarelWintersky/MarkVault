<?php

namespace App\Presenter;

/**
 * Общий контракт шаблонизаторов.
 *
 * render($template, $vars) — отрендерить шаблон и вернуть строку.
 * Каждая реализация сама решает, как искать файл шаблона и как
 * обходиться с переменными.
 */
interface PresenterInterface
{
    /**
     * @param string $template Имя шаблона (без расширения), например "page".
     * @param array<string,mixed> $vars Переменные для шаблона.
     */
    public function render(string $template, array $vars = []): string;
}