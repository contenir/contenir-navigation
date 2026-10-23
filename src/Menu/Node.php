<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

/**
 * An item in a menu's tree, with the items nested under it in order.
 *
 * @api
 */
final readonly class Node
{
    /**
     * @param list<Node> $children
     */
    public function __construct(
        public Item $item,
        public array $children = [],
    ) {}
}
