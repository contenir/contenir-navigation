<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use Contenir\Navigation\Exception\InvalidItemException;

use function ltrim;
use function mb_strlen;
use function preg_match;
use function trim;

/**
 * The rules a menu item's values follow.
 *
 * @internal
 */
final class ItemRules
{
    public const int LONGEST_TEXT = 255;

    private const string ICON = '/^[a-z0-9][a-z0-9_.:-]{0,63}$/D';

    private const string CLASSES = '/^-?[A-Za-z_][A-Za-z0-9_-]*( -?[A-Za-z_][A-Za-z0-9_-]*)*$/D';

    private const string FRAGMENT = '/^[^\s#]+$/D';

    /**
     * Extra classes separated by single spaces, or null when there are none.
     *
     * @throws InvalidItemException
     */
    public static function classes(?string $class): ?string
    {
        $classes = self::text('Classes', $class);
        if (null !== $classes && 1 !== preg_match(self::CLASSES, $classes)) {
            throw InvalidItemException::invalid('Classes', $classes, 'use class names separated by single spaces');
        }

        return $classes;
    }

    /**
     * How the item is drawn: an icon token where its display shows one, and
     * a target of _blank or none.
     *
     * @throws InvalidItemException
     */
    public static function drawing(Display $display, ?string $icon, ?string $target): void
    {
        if ($display->showsIcon() && null === $icon) {
            throw InvalidItemException::because('An item drawn with an icon needs an icon token');
        }

        if (null !== $icon && 1 !== preg_match(self::ICON, $icon)) {
            throw InvalidItemException::invalid(
                'An icon token',
                $icon,
                'use 1 to 64 lowercase letters, digits, dots, colons, dashes and underscores',
            );
        }

        if (null !== $target && Item::NEW_TAB !== $target) {
            throw InvalidItemException::invalid('A target', $target, 'only _blank opens a link elsewhere');
        }
    }

    /**
     * A fragment without its leading #, or null when there is none.
     *
     * @throws InvalidItemException
     */
    public static function fragment(?string $fragment): ?string
    {
        $value = self::text('A fragment', null === $fragment ? null : ltrim(trim($fragment), characters: '#'));
        if (null !== $value && 1 !== preg_match(self::FRAGMENT, $value)) {
            throw InvalidItemException::invalid('A fragment', $value, 'it can\'t hold spaces or a second #');
        }

        return $value;
    }

    /**
     * Trimmed text, null when there is none.
     *
     * @throws InvalidItemException When it is longer than its column holds.
     */
    public static function text(string $what, ?string $value): ?string
    {
        $text = null === $value ? '' : trim($value);
        if (mb_strlen($text) > self::LONGEST_TEXT) {
            throw InvalidItemException::invalid($what, $text, 'it can be at most 255 characters');
        }

        return '' === $text ? null : $text;
    }
}
