<?php

declare(strict_types=1);

namespace Contenir\Navigation\Link;

use Contenir\Navigation\Exception\InvalidItemException;

use function strlen;
use function trim;

/**
 * A record a menu item links to, by its section and id, as the CMS names
 * them. The site turns it into a URL with its own routes.
 *
 * @api
 */
final readonly class RecordTarget
{
    /**
     * The longest id a record item's column holds.
     */
    public const int LONGEST_ID = 64;

    public string $id;

    /**
     * @throws InvalidItemException When the section or id is empty, or the id too long.
     */
    public function __construct(
        public string $section,
        string|int $id,
    ) {
        $this->id = (string) $id;
        if ('' === trim($section) || '' === trim($this->id) || strlen($this->id) > self::LONGEST_ID) {
            throw InvalidItemException::invalid(
                'A record',
                "{$section}:{$this->id}",
                'it needs a section and an id of at most 64 characters',
            );
        }
    }
}
