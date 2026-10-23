<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use function array_key_exists;
use function usort;

/**
 * Builds one menu's tree from its rows.
 *
 * @internal
 */
final class TreeBuilder
{
    /**
     * @var array<int, true> The ids already in the tree
     */
    private array $placed = [];

    /**
     * @param list<Item> $items
     */
    private function __construct(
        private readonly array $items,
        private readonly Family $family,
    ) {}

    /**
     * @param list<Item> $items
     *
     * @return list<Node>
     */
    public static function build(array $items): array
    {
        return (new self($items, Family::of($items)))->nodes();
    }

    /**
     * The tree from the top of the menu, then any item it couldn't reach,
     * placed at the top.
     *
     * @return list<Node>
     */
    private function nodes(): array
    {
        $nodes = $this->place($this->family->tops);
        foreach ($this->items as $item) {
            if (null === $item->id || array_key_exists($item->id, $this->placed)) {
                continue;
            }

            foreach ($this->place([$item]) as $node) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    /**
     * Nodes for $items in order, each with the items under it, leaving out
     * any already in the tree.
     *
     * @param list<Item> $items
     *
     * @return list<Node>
     */
    private function place(array $items): array
    {
        usort($items, static fn(Item $a, Item $b): int => [$a->sequence, $a->id] <=> [$b->sequence, $b->id]);

        $nodes = [];
        foreach ($items as $item) {
            if (null === $item->id) {
                $nodes[] = new Node($item);
                continue;
            }

            if (array_key_exists($item->id, $this->placed)) {
                continue;
            }

            $this->placed[$item->id] = true;
            $nodes[]                 = new Node($item, $this->place($this->family->children[$item->id] ?? []));
        }

        return $nodes;
    }
}
