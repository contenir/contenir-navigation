<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

/**
 * How a menu item is drawn: its label, its icon, or both. An item drawn
 * with an icon names an icon token, which each site maps to its own icon.
 *
 * @api
 */
enum Display: string
{
    case Label = 'label';

    case Icon = 'icon';

    case IconLabel = 'icon_label';

    public function showsIcon(): bool
    {
        return self::Label !== $this;
    }
}
