<?php

declare(strict_types=1);

namespace Contenir\Navigation\Exception;

use RuntimeException;

use function sprintf;

/**
 * The container's settings for navigation can't be used.
 *
 * @api
 */
final class ConfigurationException extends RuntimeException implements ExceptionInterface
{
    public static function invalidSetting(string $key, string $expected): self
    {
        return new self(sprintf('The setting %s must be %s', $key, $expected));
    }

    public static function wrongService(string $name, string $expected): self
    {
        return new self(sprintf('The container entry "%s" is not a %s', $name, $expected));
    }
}
