<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Menu;

use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Menu\Tree;
use Contenir\Navigation\Tests\TestAsset\Factory\ItemFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;

#[Group('unit')]
final class TreeTest extends TestCase
{
    /**
     * Each node's id and children, nested.
     *
     * @param list<Node> $nodes
     *
     * @return list<array{int|null, list<mixed>}>
     */
    private static function shape(array $nodes): array
    {
        return array_map(static fn(Node $node): array => [$node->item->id, self::shape($node->children)], $nodes);
    }

    #[Test]
    public function aLoopBackToAPlacedItemLeavesTheRestOfItsSiblingsInPlace(): void
    {
        $tree = Tree::build([
            ItemFactory::text(2, parentId: 3),
            ItemFactory::text(3, parentId: 2),
            ItemFactory::text(4, parentId: 3, sequence: 1),
        ]);

        static::assertSame([[2, [[3, [[4, []]]]]]], self::shape($tree));
    }

    #[Test]
    public function aNewItemAmongChildrenLeavesItsSiblingsInPlace(): void
    {
        $tree = Tree::build([
            ItemFactory::text(1),
            Item::text('New')->withPlacement(1, 1, 0),
            ItemFactory::text(2, parentId: 1, sequence: 1),
        ]);

        static::assertSame([[1, [[null, []], [2, []]]]], self::shape($tree));
    }

    #[Test]
    public function aNewItemHasNoChildren(): void
    {
        $tree = Tree::build([Item::text('New'), ItemFactory::text(1, parentId: null)]);

        static::assertSame([[null, []], [1, []]], self::shape($tree));
    }

    #[Test]
    public function anItemUnderItselfIsAtTheTop(): void
    {
        static::assertSame([[1, []]], self::shape(Tree::build([ItemFactory::text(1, parentId: 1)])));
    }

    #[Test]
    public function anItemWhoseParentIsMissingIsAtTheTop(): void
    {
        static::assertSame(
            [[1, []], [2, []]],
            self::shape(Tree::build([ItemFactory::text(2, parentId: 99, sequence: 1), ItemFactory::text(1)])),
        );
    }

    #[Test]
    public function flattensATreeBackIntoPlacedRows(): void
    {
        $rows = Tree::flatten(
            [new Node(ItemFactory::text(7, sequence: 5), [new Node(ItemFactory::text(
                8,
                sequence: 9,
            ))]), new Node(ItemFactory::text(9))],
            3,
        );

        static::assertSame(
            [[7, 3, null, 0], [8, 3, 7, 0], [9, 3, null, 1]],
            array_map(static fn(Item $item): array => [
                $item->id,
                $item->navigationId,
                $item->parentId,
                $item->sequence,
            ], $rows),
        );
    }

    #[Test]
    public function flattensNothingToNothing(): void
    {
        static::assertSame([], Tree::flatten([], 1));
    }

    #[Test]
    public function itemsInALoopAfterOthersAreStillPlaced(): void
    {
        static::assertSame(
            [[1, []], [2, [[3, []]]]],
            self::shape(Tree::build([
                ItemFactory::text(1),
                ItemFactory::text(2, parentId: 3),
                ItemFactory::text(3, parentId: 2),
            ])),
        );
    }

    #[Test]
    public function itemsInALoopAreAtTheTopRatherThanLost(): void
    {
        static::assertSame(
            [[1, [[2, []]]]],
            self::shape(Tree::build([ItemFactory::text(1, parentId: 2), ItemFactory::text(2, parentId: 1)])),
        );
    }

    #[Test]
    public function nestsItemsUnderTheirParentsInOrder(): void
    {
        $tree = Tree::build([
            ItemFactory::text(4, parentId: 1, sequence: 1),
            ItemFactory::text(2, sequence: 1),
            ItemFactory::text(3, parentId: 1, sequence: 0),
            ItemFactory::text(1, sequence: 0),
            ItemFactory::text(5, parentId: 3),
        ]);

        static::assertSame([[1, [[3, [[5, []]]], [4, []]]], [2, []]], self::shape($tree));
    }

    #[Test]
    public function siblingsWithTheSameSequenceAreInIdOrder(): void
    {
        static::assertSame(
            [[1, []], [2, []]],
            self::shape(Tree::build([ItemFactory::text(2), ItemFactory::text(1)])),
        );
    }
}
