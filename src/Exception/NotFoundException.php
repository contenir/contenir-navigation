<?php

declare(strict_types=1);

namespace Contenir\Navigation\Exception;

use RuntimeException;

use function sprintf;

/**
 * A menu, item or revision asked for by id that isn't there.
 *
 * @api
 */
final class NotFoundException extends RuntimeException implements ExceptionInterface
{
    public static function item(int $id): self
    {
        return new self(sprintf('There is no menu item %d', $id));
    }

    public static function navigation(int $id): self
    {
        return new self(sprintf('There is no menu %d', $id));
    }

    public static function revision(int $id): self
    {
        return new self(sprintf('There is no menu revision %d', $id));
    }
}
