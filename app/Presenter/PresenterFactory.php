<?php

declare(strict_types=1);

namespace App\Presenter;

use InvalidArgumentException;

/**
 * Фабрика шаблонизаторов.
 *
 * Использование:
 *   $factory = new PresenterFactory(__DIR__ . '/templates');
 *   $tpl = $factory->create('simplest');
 *   echo $tpl->render('page', $vars);
 *
 * Имя движка приходит из конфига («template.engine»), поэтому подключить
 * другой движок — реализовать PresenterInterface и добавить ветку в create().
 */
final class PresenterFactory
{
    public const SIMPLEST = 'simplest';

    public function __construct(
        private readonly string $templateDir,
    ) {
    }

    public function create(string $name): PresenterInterface
    {
        return match (strtolower($name)) {
            self::SIMPLEST => new Simplest($this->templateDir . '/simplest'),
            default => throw new InvalidArgumentException("Unknown template engine: {$name}"),
        };
    }

    /** @return string[] */
    public static function available(): array
    {
        return [self::SIMPLEST];
    }
}
