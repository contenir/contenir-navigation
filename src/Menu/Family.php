<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use function array_column;
use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_map;
use function array_merge;
use function usort;

/**
 * A menu's rows grouped by parent: those at the top of the menu, and those
 * under each parent that is there. An item whose parent is missing, or is
 * itself, belongs at the top. Also finds an item's siblings in order and
 * everything nested under it.
 *
 * @internal
 */
final readonly class Family
{
    /**
     * @param list<Item>             $tops
     * @param array<int, list<Item>> $children By parent id
     */
    private function __construct(
        public array $tops,
        public array $children,
    ) {}

    /**
     * The ids of every item nested under $id.
     *
     * @param list<Item> $items
     *
     * @return list<int>
     */
    public static function descendants(array $items, int $id): array
    {
        $found = [];
        foreach ($items as $item) {
            if ($item->parentId !== $id || null === $item->id) {
                continue;
            }

            $found[] = [$item->id];
            $found[] = self::descendants($items, $item->id);
        }

        return array_merge(...$found);
    }

    /**
     * @param list<Item> $items
     */
    public static function of(array $items): self
    {
        $ids = array_flip(array_filter(
            array_column($items, 'id'),
            static fn(?int $id): bool => null !== $id,
        ));
        $tops     = [];
        $children = [];
        foreach ($items as $item) {
            $parent = $item->parentId;
            if (null !== $parent && $parent !== $item->id && array_key_exists($parent, $ids)) {
                $children[$parent][] = $item;
                continue;
            }

            $tops[] = $item;
        }

        return new self($tops, $children);
    }

    /**
     * The ids of the items under $parentId, other than $except, in order.
     *
     * @param list<Item> $items
     *
     * @return list<int>
     */
    public static function siblings(array $items, ?int $parentId, int $except): array
    {
        $siblings = [];
        foreach ($items as $item) {
            if ($item->parentId !== $parentId || null === $item->id || $item->id === $except) {
                continue;
            }

            $siblings[] = $item;
        }

        usort($siblings, static fn(Item $a, Item $b): int => [$a->sequence, $a->id] <=> [$b->sequence, $b->id]);

        return array_map(static fn(Item $item): int => (int) $item->id, $siblings);
    }
}
