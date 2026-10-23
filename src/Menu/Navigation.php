<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use DateTimeImmutable;

use function mb_strlen;
use function preg_match;
use function trim;

/**
 * A menu, such as the header or the footer: the handle a template asks for
 * it by, its title and description in the CMS, its place among the menus,
 * and its live revision, null until it is first published. Updated is
 * bumped by any change to its draft.
 *
 * @api
 */
final readonly class Navigation
{
    private const string HANDLE = '/^[a-z][a-z0-9-]{0,63}$/D';

    public const int LONGEST_TITLE = 255;

    /**
     * @throws InvalidItemException When the handle or title breaks its rule.
     *
     * @mago-expect lint:excessive-parameter-list One parameter per column of a navigation row.
     */
    public function __construct(
        public string $handle,
        public string $title,
        public ?string $description = null,
        public int $sequence = 0,
        public ?int $id = null,
        public ?int $revisionId = null,
        public ?DateTimeImmutable $updated = null,
    ) {
        if (1 !== preg_match(self::HANDLE, $handle)) {
            throw InvalidItemException::invalid(
                'A menu handle',
                $handle,
                'use 1 to 64 lowercase letters, digits and dashes, starting with a letter',
            );
        }

        if ('' === trim($title) || mb_strlen($title) > self::LONGEST_TITLE) {
            throw InvalidItemException::invalid('A menu title', $title, 'it needs 1 to 255 characters');
        }
    }

    /**
     * Whether it has been published, so the site shows it.
     */
    public function isLive(): bool
    {
        return null !== $this->revisionId;
    }

    public function withId(int $id): self
    {
        return new self(
            $this->handle,
            $this->title,
            $this->description,
            $this->sequence,
            $id,
            $this->revisionId,
            $this->updated,
        );
    }

    public function withRevision(?int $revisionId): self
    {
        return new self(
            $this->handle,
            $this->title,
            $this->description,
            $this->sequence,
            $this->id,
            $revisionId,
            $this->updated,
        );
    }

    public function withUpdated(DateTimeImmutable $updated): self
    {
        return new self(
            $this->handle,
            $this->title,
            $this->description,
            $this->sequence,
            $this->id,
            $this->revisionId,
            $updated,
        );
    }
}
