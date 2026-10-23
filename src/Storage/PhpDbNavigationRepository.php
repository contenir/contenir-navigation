<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Html\HtmlSanitiser;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Revision;
use Override;
use PhpDb\Adapter\AdapterInterface;
use Psr\Clock\ClockInterface;

/**
 * The site's menus in the navigation, navigation_item and
 * navigation_revision tables, which the migrations in schema/ create.
 * Changes that touch several rows run in one transaction, joining one the
 * caller already has open.
 *
 * @api
 *
 * @mago-expect lint:too-many-methods One method per method of the repository it implements.
 */
final readonly class PhpDbNavigationRepository implements NavigationRepositoryInterface
{
    private MenuTable $menus;

    private ItemTable $items;

    private RevisionTable $revisions;

    private TreeRows $trees;

    private TransactionInterface $transaction;

    public function __construct(
        AdapterInterface $adapter,
        private ClockInterface $clock,
        private HtmlSanitiser $sanitiser = new HtmlSanitiser(),
    ) {
        $database          = new Database($adapter);
        $this->menus       = new MenuTable($database);
        $this->items       = new ItemTable($database);
        $this->revisions   = new RevisionTable($database);
        $this->trees       = new TreeRows($database);
        $this->transaction = new Transaction($adapter);
    }

    #[Override]
    public function addRevision(Revision $revision): Revision
    {
        return $this->revisions->add($revision);
    }

    #[Override]
    public function deleteItem(int $id): void
    {
        $this->transaction->run(
            /**
             * @throws NotFoundException
             * @throws StorageException
             */
            function () use ($id): void {
                $this->menus->touch($this->items->delete($id), $this->clock->now());
            },
        );
    }

    #[Override]
    public function deleteNavigation(int $id): void
    {
        $this->transaction->run(
            /**
             * @throws NotFoundException
             * @throws StorageException
             */
            function () use ($id): void {
                $this->items->deleteFor($id);
                $this->revisions->deleteFor($id);
                $this->menus->delete($id);
            },
        );
    }

    #[Override]
    public function item(int $id): Item
    {
        return $this->items->item($id);
    }

    #[Override]
    public function items(int $navigationId): array
    {
        return $this->items->items($navigationId);
    }

    #[Override]
    public function liveRevisionId(string $handle): ?int
    {
        return $this->menus->liveRevisionId($handle);
    }

    #[Override]
    public function moveItem(int $id, ?int $parentId, int $position): void
    {
        $this->transaction->run(
            /**
             * @throws NotFoundException
             * @throws StorageException
             */
            function () use ($id, $parentId, $position): void {
                $this->menus->touch($this->items->move($id, $parentId, $position), $this->clock->now());
            },
        );
    }

    #[Override]
    public function navigation(int $id): Navigation
    {
        return $this->menus->navigation($id);
    }

    #[Override]
    public function navigationByHandle(string $handle): ?Navigation
    {
        return $this->menus->navigationByHandle($handle);
    }

    #[Override]
    public function navigations(): array
    {
        return $this->menus->navigations();
    }

    #[Override]
    public function replaceItems(int $navigationId, array $nodes): void
    {
        $this->transaction->run(
            /**
             * @throws NotFoundException
             * @throws StorageException
             */
            function () use ($navigationId, $nodes): void {
                $now = $this->clock->now();
                $this->trees->replace($navigationId, $nodes, $now);
                $this->menus->touch($navigationId, $now);
            },
        );
    }

    #[Override]
    public function revision(int $id): Revision
    {
        return $this->revisions->revision($id);
    }

    #[Override]
    public function revisions(int $navigationId): array
    {
        return $this->revisions->revisions($navigationId);
    }

    #[Override]
    public function saveItem(Item $item): Item
    {
        $clean = null === $item->html ? $item : $item->withHtml($this->sanitiser->clean($item->html));

        return $this->transaction->run(
            /**
             *
             * @throws NotFoundException
             *
             * @throws StorageException
             *
             */

            function () use ($clean): Item {
                $now   = $this->clock->now();
                $saved = $this->items->save($clean, $now);
                $this->menus->touch((int) $saved->navigationId, $now);

                return $saved;
            },
        );
    }

    #[Override]
    public function saveNavigation(Navigation $navigation): Navigation
    {
        return $this->menus->save($navigation, $this->clock->now());
    }

    #[Override]
    public function setLiveRevision(int $navigationId, int $revisionId): void
    {
        $this->menus->setLiveRevision($navigationId, $revisionId);
    }
}
