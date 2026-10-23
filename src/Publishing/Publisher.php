<?php

declare(strict_types=1);

namespace Contenir\Navigation\Publishing;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Menu\Changes;
use Contenir\Navigation\Menu\Revision;
use Contenir\Navigation\Menu\Tree;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use Contenir\Navigation\Storage\TransactionInterface;
use Psr\Clock\ClockInterface;

/**
 * Publishes menus. A menu's items are its draft, which editors change
 * freely; publishing writes the whole tree into a new revision and makes it
 * live, and the site reads only that. Discarding changes rebuilds the draft
 * from the live revision, and restoring an older revision copies it into
 * the draft to publish again. Each runs in one transaction.
 *
 * @api
 */
final readonly class Publisher
{
    public function __construct(
        private NavigationRepositoryInterface $repository,
        private TransactionInterface $transaction,
        private ClockInterface $clock,
    ) {}

    /**
     * Returns a menu's draft to its live revision. False, changing nothing,
     * for a menu never published.
     *
     * @throws NotFoundException When there is no such menu, or its live revision is gone.
     * @throws StorageException
     */
    public function discard(int $navigationId): bool
    {
        return $this->transaction->run(
            /**
             * @throws NotFoundException
             * @throws StorageException
             */
            function () use ($navigationId): bool {
                $revisionId = $this->repository->navigation($navigationId)->revisionId;
                if (null === $revisionId) {
                    return false;
                }

                $this->repository->replaceItems($navigationId, $this->repository->revision($revisionId)->nodes);

                return true;
            },
        );
    }

    /**
     * The menu with $handle as the site shows it, or null when there is no
     * such menu or it has never been published.
     *
     * @throws NotFoundException When its live revision is gone.
     * @throws StorageException
     */
    public function live(string $handle): ?LiveNavigation
    {
        $navigation = $this->repository->navigationByHandle($handle);
        if (null === $navigation || null === $navigation->revisionId) {
            return null;
        }

        return new LiveNavigation($navigation, $this->repository->revision($navigation->revisionId));
    }

    /**
     * Makes a menu's draft live as a new revision, with an optional note
     * saying what changed.
     *
     * @throws NotFoundException    When there is no such menu.
     * @throws InvalidItemException When the note is longer than 255 characters.
     * @throws StorageException
     */
    public function publish(int $navigationId, int $publishedBy, ?string $note = null): Revision
    {
        return $this->transaction->run(
            /**
             * @throws NotFoundException
             * @throws StorageException
             */
            function () use ($navigationId, $publishedBy, $note): Revision {
                $this->repository->navigation($navigationId);
                $revision = $this->repository->addRevision(new Revision(
                    $navigationId,
                    Tree::build($this->repository->items($navigationId)),
                    $this->clock->now(),
                    $publishedBy,
                    $note,
                ));
                $this->repository->setLiveRevision($navigationId, (int) $revision->id);

                return $revision;
            },
        );
    }

    /**
     * Copies an older revision into its menu's draft, ready to publish.
     *
     * @throws NotFoundException When there is no such revision.
     * @throws StorageException
     */
    public function restore(int $revisionId): void
    {
        $this->transaction->run(
            /**
             * @throws NotFoundException
             * @throws StorageException
             */
            function () use ($revisionId): void {
                $revision = $this->repository->revision($revisionId);
                $this->repository->replaceItems($revision->navigationId, $revision->nodes);
            },
        );
    }

    /**
     * How many changes a menu's draft holds against its live revision: every
     * item, for a menu never published.
     *
     * @throws NotFoundException When there is no such menu, or its live revision is gone.
     * @throws StorageException
     */
    public function unpublishedChanges(int $navigationId): int
    {
        $revisionId = $this->repository->navigation($navigationId)->revisionId;

        return Changes::count(
            Tree::build($this->repository->items($navigationId)),
            null === $revisionId ? [] : $this->repository->revision($revisionId)->nodes,
        );
    }
}
