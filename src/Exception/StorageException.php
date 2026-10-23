<?php

declare(strict_types=1);

namespace Contenir\Navigation\Exception;

use RuntimeException;
use Throwable;

use function sprintf;

/**
 * The database failed, or held something the navigation tables can't hold.
 *
 * @api
 */
final class StorageException extends RuntimeException implements ExceptionInterface
{
    public static function failed(Throwable $previous): self
    {
        return new self(
            sprintf('The navigation tables could not be read or written: %s', $previous->getMessage()),
            previous: $previous,
        );
    }

    public static function missingColumn(string $column): self
    {
        return new self(sprintf('A navigation row has no "%s" column', $column));
    }

    public static function noResult(): self
    {
        return new self('The database driver returned no result');
    }

    public static function notATime(string $value): self
    {
        return new self(sprintf('Not a database time: %s', $value));
    }

    public static function unreadable(string $column, string $expected): self
    {
        return new self(sprintf('The "%s" column does not hold %s', $column, $expected));
    }

    public static function unreadableRevision(int $revision, string $reason): self
    {
        return new self(sprintf('Revision %d could not be read: %s', $revision, $reason));
    }
}
