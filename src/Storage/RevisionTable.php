<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\InvalidLinkException;
use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Menu\Revision;
use Contenir\Navigation\Menu\Snapshot;
use JsonException;

use function is_array;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

/**
 * The navigation_revision table: one row per publish, holding the menu's
 * whole tree as JSON.
 *
 * @internal
 */
final readonly class RevisionTable
{
    public const string TABLE = 'navigation_revision';

    public function __construct(
        private Database $database,
    ) {}

    /**
     * @throws StorageException
     *
     * @mago-expect analysis:mixed-assignment Decoded JSON is untyped; Snapshot checks each value it reads.
     */
    private static function hydrate(Row $row): Revision
    {
        $id           = $row->int('navigation_revision_id');
        $navigationId = $row->int('navigation_id');

        try {
            $items = json_decode($row->string('items'), associative: true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($items)) {
                throw StorageException::unreadableRevision($id, 'its items are not a list');
            }

            return new Revision(
                $navigationId,
                Snapshot::decode($items, $navigationId),
                $row->time('created'),
                $row->int('created_by'),
                $row->nullableString('note'),
                $id,
            );
        } catch (JsonException|InvalidItemException|InvalidLinkException $failure) {
            throw StorageException::unreadableRevision($id, $failure->getMessage());
        }
    }

    /**
     * @throws StorageException
     */
    public function add(Revision $revision): Revision
    {
        try {
            $items = json_encode(
                Snapshot::encode($revision->nodes),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $failure) {
            throw StorageException::failed($failure);
        }

        $id = $this->database->insert($this->database
            ->sql
            ->insert(self::TABLE)
            ->values([
                'navigation_id' => $revision->navigationId,
                'items'         => $items,
                'note'          => $revision->note,
                'created'       => DbTime::format($revision->created),
                'created_by'    => $revision->createdBy,
            ]));

        return $revision->withId($id);
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
    public function revision(int $id): Revision
    {
        $row = $this->database->row($this->database
            ->sql
            ->select(self::TABLE)
            ->where(['navigation_revision_id' => $id]));

        return null === $row ? throw NotFoundException::revision($id) : self::hydrate($row);
    }

    /**
     * @return list<Revision>
     *
     * @throws StorageException
     */
    public function revisions(int $navigationId): array
    {
        $revisions = [];
        $select    = $this->database
            ->sql
            ->select(self::TABLE)
            ->where(['navigation_id' => $navigationId])
            ->order(['created DESC', 'navigation_revision_id DESC']);
        foreach ($this->database->rows($select) as $row) {
            $revisions[] = self::hydrate($row);
        }

        return $revisions;
    }
}
