<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use function array_diff_key;
use function array_intersect_key;
use function array_replace;
use function count;

/**
 * How many changes a menu's draft holds against its live revision: each
 * item added, each removed, and each changed, whether in its fields or its
 * place. An item never saved counts as added.
 *
 * @api
 */
final class Changes
{
    /**
     * @param list<Node> $draft
     * @param list<Node> $live
     */
    public static function count(array $draft, array $live): int
    {
        $drafted = self::places($draft, null);
        $was     = self::places($live, null);
        $saved   = $drafted['saved'];
        $before  = $was['saved'];

        $changed = 0;
        foreach (array_intersect_key($saved, $before) as $id => $place) {
            $changed += $place === ($before[$id] ?? null) ? 0 : 1;
        }

        return (
            $drafted['unsaved']
                + count(array_diff_key($saved, $before))
                + count(array_diff_key($before, $saved))
                + $changed
        );
    }

    /**
     * Each saved item's fields and place, by id, and how many items were
     * never saved.
     *
     * @param list<Node> $nodes
     *
     * @return array{saved: array<int, array<string, mixed>>, unsaved: int}
     */
    private static function places(array $nodes, ?int $parentId): array
    {
        $saved   = [];
        $unsaved = 0;
        foreach ($nodes as $position => $node) {
            $id       = $node->item->id;
            $children = self::places($node->children, $id);
            $saved[]  = $children['saved'];
            $unsaved  += $children['unsaved'];
            if (null === $id) {
                $unsaved++;
                continue;
            }

            $saved[] = [$id => [...Snapshot::fields($node->item), 'parent' => $parentId, 'position' => $position]];
        }

        return ['saved' => array_replace([], ...$saved), 'unsaved' => $unsaved];
    }
}
