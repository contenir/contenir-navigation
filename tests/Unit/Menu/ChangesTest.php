<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Menu;

use Contenir\Navigation\Menu\Changes;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Tests\TestAsset\Factory\ItemFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ChangesTest extends TestCase
{
    /**
     * Item 1 holding 2, then item 3.
     *
     * @return list<Node>
     */
    private static function live(): array
    {
        return [
            new Node(ItemFactory::text(1), [new Node(ItemFactory::text(2))]),
            new Node(ItemFactory::text(3, 'Shop')),
        ];
    }

    #[Test]
    public function aDraftLikeItsRevisionHasNoChanges(): void
    {
        static::assertSame(0, Changes::count(self::live(), self::live()));
    }

    #[Test]
    public function aMenuNeverPublishedHasAChangeForEveryItem(): void
    {
        static::assertSame(3, Changes::count(self::live(), []));
    }

    #[Test]
    public function anItemMovedAlongItsSiblingsIsAChangeForEachThatMoved(): void
    {
        $draft = [new Node(ItemFactory::text(3, 'Shop')), new Node(ItemFactory::text(1), [new Node(ItemFactory::text(
            2,
        ))])];

        static::assertSame(2, Changes::count($draft, self::live()));
    }

    #[Test]
    public function anItemMovedUnderAnotherIsAChange(): void
    {
        $draft = [new Node(ItemFactory::text(1), [
            new Node(ItemFactory::text(2)),
            new Node(ItemFactory::text(3, 'Shop')),
        ])];

        static::assertSame(1, Changes::count($draft, self::live()));
    }

    #[Test]
    public function anItemRelabelledIsAChange(): void
    {
        $draft = [new Node(ItemFactory::text(1), [new Node(ItemFactory::text(
            2,
            'Renamed',
        ))]), new Node(ItemFactory::text(3, 'Shop'))];

        static::assertSame(1, Changes::count($draft, self::live()));
    }

    #[Test]
    public function anUnsavedItemBeforeOthersStillCounts(): void
    {
        $draft = [
            new Node(Item::text('Unsaved')),
            new Node(ItemFactory::text(4, 'Added')),
            new Node(Item::text('Unsaved too')),
        ];

        static::assertSame(3, Changes::count($draft, []));
    }

    #[Test]
    public function itemsAddedRemovedAndNeverSavedAreEachAChange(): void
    {
        $draft = [
            new Node(ItemFactory::text(1), [new Node(ItemFactory::text(2))]),
            new Node(ItemFactory::text(4, 'Added')),
            new Node(Item::text('Unsaved'), [new Node(Item::text('Unsaved too'))]),
        ];

        static::assertSame(4, Changes::count($draft, self::live()));
    }
}
