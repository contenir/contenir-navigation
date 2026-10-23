<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use Contenir\Navigation\Exception\InvalidItemException;

/**
 * The rule tying a menu item's kind to what it links to.
 *
 * @internal
 */
final class ItemLink
{
    /**
     * Whether the item links as its kind says: a record item to a record, a
     * URL item to an address with a label, and a text item to nothing.
     *
     * @throws InvalidItemException
     */
    public static function check(Item $item): void
    {
        $linked = null !== $item->record || null !== $item->url;
        $valid  = match ($item->kind) {
            ItemKind::Record => null !== $item->record && null === $item->url && null === $item->html,
            ItemKind::Url => null !== $item->url
                && null === $item->record
                && null === $item->fragment
                && null === $item->html
                && null !== $item->label,
            ItemKind::Text => ! $linked && null === $item->fragment && null === $item->target && null !== $item->label,
        };

        if (! $valid) {
            throw InvalidItemException::because(match ($item->kind) {
                ItemKind::Record => 'A record item links to a record alone',
                ItemKind::Url    => 'A URL item links to an address alone, with a label',
                ItemKind::Text   => 'A text item has a label and no link',
            });
        }
    }
}
