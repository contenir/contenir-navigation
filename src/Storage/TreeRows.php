<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Menu\Node;
use DateTimeImmutable;

/**
 * Writes a whole tree into a menu's navigation_item rows, as discarding
 * changes or restoring a revision does, keeping each item's id unless a row
 * of another menu has it.
 *
 * @internal
 */
final readonly class TreeRows
{
    public function __construct(
        private Database $database,
    ) {}

    /**
     * Replaces the menu's rows with the tree.
     *
     * @param list<Node> $nodes
     *
     * @throws StorageException
     */
    public function replace(int $navigationId, array $nodes, DateTimeImmutable $now): void
    {
        $this->database->affectedRows(
            $this->database->sql->delete(ItemTable::TABLE)->where(['navigation_id' => $navigationId]),
        );
        $this->insert($navigationId, $nodes, null, $now);
    }

    /**
     * @param list<Node> $nodes
     *
     * @throws StorageException
     */
    private function insert(int $navigationId, array $nodes, ?int $parentId, DateTimeImmutable $now): void
    {
        foreach ($nodes as $position => $node) {
            try {
                $item = $node->item->withPlacement($navigationId, $parentId, $position);
            } catch (InvalidItemException $failure) {
                throw StorageException::failed($failure);
            }

            $values = ItemRows::values($item, $navigationId, $now);
            if (null !== $item->id && ! $this->taken($item->id)) {
                $values[ItemTable::ID] = $item->id;
            }

            $id = $this->database->insert($this->database->sql->insert(ItemTable::TABLE)->values($values));
            $this->insert($navigationId, $node->children, $id, $now);
        }
    }

    /**
     * @throws StorageException
     */
    private function taken(int $id): bool
    {
        return null !== $this->database->row(
            $this->database->sql->select(ItemTable::TABLE)->columns([ItemTable::ID])->where([ItemTable::ID => $id]),
        );
    }
}
