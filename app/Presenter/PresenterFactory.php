<?php

declare(strict_types=1);

namespace App\Presenter;

use InvalidArgumentException;

/**
 * Фабрика шаблонизаторов.
 *
 * Использование:
 *   $factory = new PresenterFactory(__DIR__ . '/templates');
 *   $tpl = $factory->create('simplest', 'mdshelf');
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

    /**
     * @param string $name движок («template.engine»)
     * @param string $dir  каталог внутри templates/ («template.dir»)
     */
    public function create(string $name, string $dir = self::SIMPLEST): PresenterInterface
    {
        $dir = str_replace(['..', "\0"], '', $dir);
        if ($dir === '') {
            $dir = self::SIMPLEST;
        }

        return match (strtolower($name)) {
            self::SIMPLEST => new Simplest($this->templateDir . '/' . $dir),
            default => throw new InvalidArgumentException("Unknown template engine: {$name}"),
        };
    }

    /** @return string[] */
    public static function available(): array
    {
        return [self::SIMPLEST];
    }
}
