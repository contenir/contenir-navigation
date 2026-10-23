<?php

declare(strict_types=1);

namespace Contenir\Navigation\Exception;

use InvalidArgumentException;

use function sprintf;

/**
 * What an editor typed is not a link a menu can hold: empty, an address
 * that can't be read, or one that runs code, such as javascript:.
 *
 * @api
 */
final class InvalidLinkException extends InvalidArgumentException implements ExceptionInterface
{
    public static function empty(): self
    {
        return new self('A link needs an address');
    }

    public static function refusedScheme(string $scheme): self
    {
        return new self(sprintf('A menu can\'t link to a %s: address', $scheme));
    }

    public static function unreadable(string $input): self
    {
        return new self(sprintf(
            '"%s" isn\'t a link: start a page on this site with /, a spot on the page with #, or give a full address',
            $input,
        ));
    }
}
