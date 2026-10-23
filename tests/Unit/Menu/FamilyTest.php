<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Menu;

use Contenir\Navigation\Menu\Family;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Tests\TestAsset\Factory\ItemFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class FamilyTest extends TestCase
{
    /**
     * Item 1 at the top holding 2, which holds 4, and 3; 5 and 6 at the top
     * too, 6 first.
     *
     * @return list<Item>
     */
    private static function items(): array
    {
        return [
            ItemFactory::text(1, sequence: 1),
            ItemFactory::text(2, parentId: 1, sequence: 0),
            ItemFactory::text(3, parentId: 1, sequence: 1),
            ItemFactory::text(4, parentId: 2),
            ItemFactory::text(5, sequence: 1),
            ItemFactory::text(6, sequence: 0),
        ];
    }

    #[Test]
    public function findsEverythingNestedUnderAnItem(): void
    {
        static::assertSame([2, 4, 3], Family::descendants(self::items(), 1));
    }

    #[Test]
    public function findsNothingUnderALeaf(): void
    {
        static::assertSame([], Family::descendants(self::items(), 4));
    }

    #[Test]
    public function findsTheItemsAtTheTopInSequenceThenIdOrder(): void
    {
        static::assertSame([6, 1, 5], Family::siblings(self::items(), null, 0));
    }

    #[Test]
    public function findsTheSiblingsUnderAParentInOrderLeavingOneOut(): void
    {
        static::assertSame([3], Family::siblings(self::items(), 1, 2));
    }

    #[Test]
    public function groupsItemsByParentWithTheRestAtTheTop(): void
    {
        $family = Family::of(self::items());

        static::assertSame(
            [[1, 5, 6], [1 => [2, 3], 2 => [4]]],
            [
                array_map(static fn(Item $item): ?int => $item->id, $family->tops),
                array_map(static fn(array $children): array => array_map(
                    static fn(Item $item): ?int => $item->id,
                    $children,
                ), $family->children),
            ],
        );
    }

    #[Test]
    public function leavesNewItemsOutOfSiblings(): void
    {
        static::assertSame([1], Family::siblings([ItemFactory::text(1), Item::text('New')], null, 0));
    }
}
