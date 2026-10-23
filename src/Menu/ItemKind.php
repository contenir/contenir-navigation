<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

/**
 * What a menu item is: a link to a record, a link to a URL, or text alone.
 *
 * @api
 */
enum ItemKind: string
{
    /**
     * Links to a record, such as a page or a news article, which the site
     * resolves with its own routes.
     */
    case Record = 'record';

    /**
     * Links to an address: a path, a full link, a fragment, an email address
     * or a phone number.
     */
    case Url = 'url';

    /**
     * Text with no link, such as a heading over a group of items, which may
     * hold sanitised HTML.
     */
    case Text = 'text';
}
