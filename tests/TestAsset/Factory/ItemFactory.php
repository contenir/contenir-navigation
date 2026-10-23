<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\TestAsset\Factory;

use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\ItemKind;

/**
 * Menu items for tests, saved in menu 1 unless a test says otherwise.
 */
final class ItemFactory
{
    public static function page(int $id, ?int $parentId = null, int $sequence = 0, string $record = '1'): Item
    {
        return new Item(
            ItemKind::Record,
            record: new RecordTarget('page', $record),
            id: $id,
            navigationId: 1,
            parentId: $parentId,
            sequence: $sequence,
        );
    }

    public static function text(int $id, string $label = 'Heading', ?int $parentId = null, int $sequence = 0): Item
    {
        return new Item(
            ItemKind::Text,
            label: $label,
            id: $id,
            navigationId: 1,
            parentId: $parentId,
            sequence: $sequence,
        );
    }

    public static function url(
        int $id,
        string $url = '/contact',
        string $label = 'Contact',
        ?int $parentId = null,
        int $sequence = 0,
    ): Item {
        return new Item(
            ItemKind::Url,
            url: Url::parse($url),
            label: $label,
            id: $id,
            navigationId: 1,
            parentId: $parentId,
            sequence: $sequence,
        );
    }
}
