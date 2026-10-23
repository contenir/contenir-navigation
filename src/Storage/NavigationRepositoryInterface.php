<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Menu\Revision;

/**
 * The site's menus: each menu, its draft items, and its published
 * revisions. Every change to a menu's items bumps the menu's updated time.
 *
 * @api
 *
 * @mago-expect lint:too-many-methods One method per thing the CMS and the site do with menus.
 */
interface NavigationRepositoryInterface
{
    /**
     * Stores a published revision, giving it its id.
     *
     * @throws StorageException
     */
    public function addRevision(Revision $revision): Revision;

    /**
     * Deletes an item and every item nested under it. Deleting an item never
     * deletes the record it links to.
     *
     * @throws NotFoundException When there is no such item.
     * @throws StorageException
     */
    public function deleteItem(int $id): void;

    /**
     * Deletes a menu with its items and revisions.
     *
     * @throws StorageException
     */
    public function deleteNavigation(int $id): void;

    /**
     * @throws NotFoundException When there is no such item.
     * @throws StorageException
     */
    public function item(int $id): Item;

    /**
     * A menu's draft items, in no particular order; Menu\Tree builds them
     * into the menu's tree.
     *
     * @return list<Item>
     *
     * @throws StorageException
     */
    public function items(int $navigationId): array;

    /**
     * The id of the revision live for the menu with $handle, or null when
     * there is no such menu or it has never been published. One small query,
     * for a site to key its cache on.
     *
     * @throws StorageException
     */
    public function liveRevisionId(string $handle): ?int;

    /**
     * Moves an item under $parentId, or to the top of its menu when null, at
     * $position among its new siblings, and closes the gap it leaves.
     *
     * @throws NotFoundException    When there is no such item or parent.
     * @throws InvalidItemException When the parent is in another menu, or is the item or one nested under it.
     * @throws StorageException
     */
    public function moveItem(int $id, ?int $parentId, int $position): void;

    /**
     * @throws NotFoundException When there is no such menu.
     * @throws StorageException
     */
    public function navigation(int $id): Navigation;

    /**
     * @throws StorageException
     */
    public function navigationByHandle(string $handle): ?Navigation;

    /**
     * Every menu, by sequence and then title.
     *
     * @return list<Navigation>
     *
     * @throws StorageException
     */
    public function navigations(): array;

    /**
     * Replaces a menu's draft items with $nodes, as discarding changes or
     * restoring a revision does. Items keep their ids where they are free.
     *
     * @param list<Node> $nodes
     *
     * @throws StorageException
     */
    public function replaceItems(int $navigationId, array $nodes): void;

    /**
     * @throws NotFoundException When there is no such revision.
     * @throws StorageException
     */
    public function revision(int $id): Revision;

    /**
     * A menu's revisions, newest first.
     *
     * @return list<Revision>
     *
     * @throws StorageException
     */
    public function revisions(int $navigationId): array;

    /**
     * Adds an item to its menu's draft, or saves its changes. A text item's
     * HTML is sanitised as it is saved.
     *
     * @throws NotFoundException    When the item's menu, or the item being changed, isn't there.
     * @throws InvalidItemException When the item names no menu, or its parent is in another menu.
     * @throws StorageException
     */
    public function saveItem(Item $item): Item;

    /**
     * Adds a menu, or saves its changes.
     *
     * @throws InvalidItemException When another menu already has its handle.
     * @throws NotFoundException    When the menu being changed isn't there.
     * @throws StorageException
     */
    public function saveNavigation(Navigation $navigation): Navigation;

    /**
     * Makes $revisionId the menu's live revision.
     *
     * @throws StorageException
     */
    public function setLiveRevision(int $navigationId, int $revisionId): void;
}
