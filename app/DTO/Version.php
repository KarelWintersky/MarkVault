<?php

declare(strict_types=1);

namespace App\DTO;

use JsonException;

/**
 * Версия сборки из vendor/_version.json, который кладёт build_phar.sh.
 *
 */
final readonly class Version implements \JsonSerializable
{
    public function __construct(
        public string $tag,
        public string $hash,
        public string $subject,
        public string $date,
        public string $appVersion,
    ) {
    }

    public static function localDev(): self
    {
        $date = date('Y-m-d');
        $subject = 'LOCAL DEV';
        $hash = '000000';

        return new self(
            tag: '000000',
            hash: $hash,
            subject: $subject,
            date: $date,
            appVersion: "Version {$subject} #{$hash} ({$date})",
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            tag: $data['tag'],
            hash: $data['hash'],
            subject: $data['subject'],
            date: $data['date'],
            appVersion: $data['app_version'],
        );
    }

    public static function fromFile(string $path): self
    {
        if (!is_file($path) || !is_readable($path)) {
            return self::localDev('unreadable');
        }

        $contents = @file_get_contents($path);
        if ($contents === false) {
            return self::localDev('file_get_contents');
        }

        try {
            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return self::localDev('json_decode');
        }

        $data['app_version'] = "Version: {$data['subject']}, #{$data['hash']} ({$data['date']})";

        if (!is_array($data) || !self::hasRequiredKeys($data)) {
            return self::localDev('is_array | hasRequiredKeys');
        }

        return self::fromArray($data);
    }

    private static function hasRequiredKeys(array $data): bool
    {
        foreach (['tag', 'hash', 'subject', 'date', 'app_version'] as $key) {
            if (!isset($data[$key]) || !is_string($data[$key])) {
                return false;
            }
        }

        return true;
    }

    public function toArray(): array
    {
        return [
            'tag' => $this->tag,
            'hash' => $this->hash,
            'subject' => $this->subject,
            'date' => $this->date,
            'app_version' => $this->appVersion,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}