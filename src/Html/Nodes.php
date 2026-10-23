<?php

declare(strict_types=1);

namespace Contenir\Navigation\Html;

use DOMNode;

/**
 * A node's children as they are now, so a walk can move or remove them
 * without the live list shifting under it.
 *
 * @internal
 */
final class Nodes
{
    /**
     * @return list<DOMNode>
     */
    public static function children(DOMNode $parent): array
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            if (! $child instanceof DOMNode) {
                continue;
            }

            $children[] = $child;
        }

        return $children;
    }
}
