<?php

declare(strict_types=1);

namespace Contenir\Navigation\Html;

use Contenir\Navigation\Exception\InvalidLinkException;
use Contenir\Navigation\Link\Url;
use DOMElement;

use function in_array;

/**
 * Keeps the attributes an element may have, with values it may hold: a link
 * as a menu item would hold it, _blank alone as a target, anything else as
 * it is. A link opening a new tab is given rel="noopener noreferrer".
 *
 * @internal
 */
final class Attributes
{
    /**
     * @param list<string> $allowed
     */
    public static function clean(DOMElement $element, array $allowed): void
    {
        /** @var list<string> $names */
        $names = $element->getAttributeNames();
        foreach ($names as $attribute) {
            $name  = $attribute;
            $value = in_array($name, $allowed, strict: true)
                ? self::value($name, $element->getAttribute($attribute))
                : null;
            $element->removeAttribute($attribute);
            if (null !== $value) {
                $element->setAttribute($name, $value);
            }
        }

        if ('a' === $element->tagName && $element->hasAttribute('target')) {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function href(string $value): ?string
    {
        try {
            return Url::parse($value)->href;
        } catch (InvalidLinkException) {
            return null;
        }
    }

    /**
     * What an allowed attribute keeps of $value, or null to drop it.
     */
    private static function value(string $attribute, string $value): ?string
    {
        return match ($attribute) {
            'href'   => self::href($value),
            'target' => '_blank' === $value ? $value : null,
            default  => $value,
        };
    }
}
