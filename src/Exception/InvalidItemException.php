<?php

declare(strict_types=1);

namespace Contenir\Navigation\Exception;

use InvalidArgumentException;

use function sprintf;

/**
 * A menu or menu item that breaks a rule, such as a handle with spaces or a
 * text item without a label.
 *
 * @api
 */
final class InvalidItemException extends InvalidArgumentException implements ExceptionInterface
{
    public static function because(string $reason): self
    {
        return new self($reason);
    }

    public static function invalid(string $what, string $value, string $rule): self
    {
        return new self(sprintf('%s "%s" is not valid: %s', $what, $value, $rule));
    }
}
