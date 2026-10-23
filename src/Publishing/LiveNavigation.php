<?php

declare(strict_types=1);

namespace Contenir\Navigation\Publishing;

use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Menu\Revision;

/**
 * A menu as the site shows it: the menu and its live revision, whose tree
 * holds every item as it was published, active or not.
 *
 * @api
 */
final readonly class LiveNavigation
{
    public function __construct(
        public Navigation $navigation,
        public Revision $revision,
    ) {}

    /**
     * @return list<Node>
     */
    public function nodes(): array
    {
        return $this->revision->nodes;
    }
}
