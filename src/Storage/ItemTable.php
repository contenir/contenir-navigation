<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Menu\Family;
use Contenir\Navigation\Menu\Item;
use DateTimeImmutable;

use function array_map;
use function array_splice;
use function count;
use function in_array;
use function max;
use function min;

/**
 * The navigation_item table: one row per element of a menu's draft.
 *
 * @internal
 */
final readonly class ItemTable
{
    public const string TABLE = 'navigation_item';

    public const string ID = 'navigation_item_id';

    public function __construct(
        private Database $database,
    ) {}

    /**
     * Deletes the item and everything nested under it, closing the gap it
     * leaves among its siblings.
     *
     * @return int The menu it was in
     *
     * @throws NotFoundException
     * @throws StorageException
     */
    public function delete(int $id): int
    {
        $item   = $this->item($id);
        $menu   = (int) $item->navigationId;
        $doomed = [$id, ...Family::descendants($this->items($menu), $id)];
        $this->database->affectedRows($this->database->sql->delete(self::TABLE)->where([self::ID => $doomed]));
        $this->place(Family::siblings($this->items($menu), $item->parentId, $id), $item->parentId);

        return $menu;
    }

    /**
     * @throws StorageException
     */
    public function deleteFor(int $navigationId): void
    {
        $this->database->affectedRows($this->database
            ->sql
            ->delete(self::TABLE)
            ->where(['navigation_id' => $navigationId]));
    }

    /**
     * @throws NotFoundException
     * @throws StorageException
     */
    public function item(int $id): Item
    {
        $row = $this->database->row($this->database->sql->select(self::TABLE)->where([self::ID => $id]));

        return null === $row ? throw NotFoundException::item($id) : ItemRows::item($row);
    }

    /**
     * @return list<Item>
     *
     * @throws StorageException
     */
    public function items(int $navigationId): array
    {
        return array_map(
            ItemRows::item(...),
            $this->database->rows($this->database->sql->select(self::TABLE)->where(['navigation_id' => $navigationId])),
        );
    }

    /**
     * @return int The menu it is in
     *
     * @throws NotFoundException
     * @throws InvalidItemException
     * @throws StorageException
     */
    public function move(int $id, ?int $parentId, int $position): int
    {
        $item  = $this->item($id);
        $menu  = (int) $item->navigationId;
        $items = $this->items($menu);
        if (null !== $parentId) {
            $this->checkParent($menu, $parentId);
            if ($parentId === $id || in_array($parentId, Family::descendants($items, $id), strict: true)) {
                throw InvalidItemException::because('An item can\'t be moved under itself');
            }
        }

        $siblings = Family::siblings($items, $parentId, $id);
        array_splice($siblings, max(0, min($position, count($siblings))), length: 0, replacement: [$id]);
        $this->place($siblings, $parentId);
        if ($item->parentId !== $parentId) {
            $this->place(Family::siblings($items, $item->parentId, $id), $item->parentId);
        }

        return $menu;
    }

    /**
     * @throws NotFoundException
     * @throws InvalidItemException
     * @throws StorageException
     */
    public function save(Item $item, DateTimeImmutable $now): Item
    {
        $menu = $item->navigationId ?? throw InvalidItemException::because('A menu item needs a menu');
        if (null !== $item->parentId) {
            $this->checkParent($menu, $item->parentId);
        }

        if (null === $item->id) {
            return $this->item($this->database->insert(
                $this->database->sql->insert(self::TABLE)->values(ItemRows::values($item, $menu, $now)),
            ));
        }

        $saved  = $this->item($item->id);
        $values = ItemRows::values($item->withTimes($saved->created ?? $now, $now), $menu, $now);
        $this->database->affectedRows($this->database
            ->sql
            ->update(self::TABLE)
            ->set($values)
            ->where([self::ID => $item->id]));

        return $this->item($item->id);
    }

    /**
     * @throws NotFoundException
     * @throws InvalidItemException
     * @throws StorageException
     */
    private function checkParent(int $navigationId, int $parentId): void
    {
        if ($this->item($parentId)->navigationId !== $navigationId) {
            throw InvalidItemException::because('An item\'s parent must be in the same menu');
        }
    }

    /**
     * Gives the items their positions under $parentId, in the order given.
     *
     * @param list<int> $ids
     *
     * @throws StorageException
     */
    private function place(array $ids, ?int $parentId): void
    {
        foreach ($ids as $sequence => $id) {
            $this->database->affectedRows(
                $this->database
                    ->sql
                    ->update(self::TABLE)
                    ->set(['parent_id' => $parentId, 'sequence' => $sequence])
                    ->where([self::ID => $id]),
            );
        }
    }
}
