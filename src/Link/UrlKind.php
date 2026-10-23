<?php

declare(strict_types=1);

namespace Contenir\Navigation\Link;

/**
 * What kind of address a URL item holds, read from what the editor typed.
 *
 * @api
 */
enum UrlKind: string
{
    /**
     * A page on this site, kept relative, such as /about/our-team?tab=2#staff.
     */
    case Path = 'path';

    /**
     * A full link to another site, such as https://shop.example.com.
     */
    case External = 'external';

    /**
     * A spot on the page the menu appears on, such as #booking.
     */
    case Fragment = 'fragment';

    /**
     * An email address, held as a mailto: link.
     */
    case Email = 'email';

    /**
     * A phone number, held as a tel: link.
     */
    case Phone = 'phone';
}
