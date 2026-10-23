<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\InvalidLinkException;

use function is_array;

/**
 * A menu's item tree as a revision holds it: plain arrays, one per item,
 * with its children nested under it, ready for JSON. Each item keeps its
 * id, so restoring a revision brings back the same rows.
 *
 * @api
 */
final class Snapshot
{
    /**
     * The tree an encoded snapshot holds, as items of $navigationId.
     *
     * @param array<array-key, mixed> $encoded
     *
     * @return list<Node>
     *
     * @throws InvalidItemException When an item can't be read back.
     * @throws InvalidLinkException When an item's address can't be read back.
     *
     * @mago-expect analysis:mixed-assignment Decoded items are untyped; each is checked before it is read.
     */
    public static function decode(array $encoded, int $navigationId, ?int $parentId = null): array
    {
        $nodes    = [];
        $position = 0;
        foreach ($encoded as $values) {
            if (! is_array($values)) {
                throw InvalidItemException::because('A revision item is not a list of fields');
            }

            $item     = SnapshotItem::read($values, $navigationId, $parentId, $position++);
            $children = is_array($values['children'] ?? null) ? $values['children'] : [];
            $nodes[]  = new Node($item, self::decode($children, $navigationId, $item->id));
        }

        return $nodes;
    }

    /**
     * @param list<Node> $nodes
     *
     * @return list<array<string, mixed>>
     */
    public static function encode(array $nodes): array
    {
        $encoded = [];
        foreach ($nodes as $node) {
            $fields             = self::fields($node->item);
            $fields['children'] = self::encode($node->children);
            $encoded[]          = $fields;
        }

        return $encoded;
    }

    /**
     * An item's own fields, without its place in the tree.
     *
     * @return array{id: int|null, kind: string, record_section: string|null, record_id: string|null, url: string|null, fragment: string|null, label: string|null, html: string|null, display: string, icon: string|null, target: string|null, class: string|null, active: bool}
     */
    public static function fields(Item $item): array
    {
        return [
            'id'             => $item->id,
            'kind'           => $item->kind->value,
            'record_section' => $item->record?->section,
            'record_id'      => $item->record?->id,
            'url'            => $item->url?->href,
            'fragment'       => $item->fragment,
            'label'          => $item->label,
            'html'           => $item->html,
            'display'        => $item->display->value,
            'icon'           => $item->icon,
            'target'         => $item->target,
            'class'          => $item->class,
            'active'         => $item->active,
        ];
    }
}
