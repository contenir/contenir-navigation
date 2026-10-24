<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use Contenir\Navigation\Exception\InvalidItemException;

use function mb_strlen;
use function strlen;
use function trim;

/**
 * Who published a revision: their user id in the CMS, held as text since a
 * host's ids may be numbers or strings, and their name as it was when they
 * published, so history still names them after they are renamed or gone.
 *
 * @api
 */
final readonly class PublishedBy
{
    public const int LONGEST_ID = 64;

    public string $id;

    /**
     * @throws InvalidItemException When the id or name is empty or too long.
     */
    public function __construct(
        string|int $id,
        public string $name,
    ) {
        $this->id = (string) $id;
        if ('' === trim($this->id) || strlen($this->id) > self::LONGEST_ID) {
            throw InvalidItemException::invalid('A publisher id', $this->id, 'it needs 1 to 64 characters');
        }

        if ('' === trim($name) || mb_strlen($name) > Item::LONGEST_TEXT) {
            throw InvalidItemException::invalid('A publisher name', $name, 'it needs 1 to 255 characters');
        }
    }
}
