<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use DateTimeImmutable;

use function mb_strlen;
use function trim;

/**
 * A menu as it was published: its whole item tree, when, by whom when the
 * host knew, and an optional note saying what changed.
 *
 * @api
 */
final readonly class Revision
{
    public ?string $note;

    /**
     * @param list<Node> $nodes
     *
     * @throws InvalidItemException When the note is longer than 255 characters.
     *
     * @mago-expect lint:excessive-parameter-list One parameter per column of a navigation_revision row.
     */
    public function __construct(
        public int $navigationId,
        public array $nodes,
        public DateTimeImmutable $created,
        public ?PublishedBy $publishedBy = null,
        ?string $note = null,
        public ?int $id = null,
    ) {
        $text = null === $note ? '' : trim($note);
        if (mb_strlen($text) > Item::LONGEST_TEXT) {
            throw InvalidItemException::invalid('A note', $text, 'it can be at most 255 characters');
        }

        $this->note = '' === $text ? null : $text;
    }

    public function withId(int $id): self
    {
        return new self($this->navigationId, $this->nodes, $this->created, $this->publishedBy, $this->note, $id);
    }
}
