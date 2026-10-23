<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use Contenir\Navigation\Exception\InvalidItemException;

use function array_merge;

/**
 * Turns a menu's rows into its tree and back. Siblings are ordered by
 * sequence, then by id. An item whose parent is missing, or that can't be
 * reached from the top of the menu, is placed at the top rather than lost.
 *
 * @api
 */
final class Tree
{
    /**
     * @param list<Item> $items
     *
     * @return list<Node>
     */
    public static function build(array $items): array
    {
        return TreeBuilder::build($items);
    }

    /**
     * The tree's items as rows of $navigationId, each placed under its
     * parent at its position among its siblings.
     *
     * @param list<Node> $nodes
     *
     * @return list<Item>
     *
     * @throws InvalidItemException When an item can't be placed, which a valid item always can.
     */
    public static function flatten(array $nodes, int $navigationId, ?int $parentId = null): array
    {
        $items = [];
        foreach ($nodes as $position => $node) {
            $item    = $node->item->withPlacement($navigationId, $parentId, $position);
            $items[] = [$item];
            $items[] = self::flatten($node->children, $navigationId, $item->id);
        }

        return array_merge(...$items);
    }
}
