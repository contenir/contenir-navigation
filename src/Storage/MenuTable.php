<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Menu\Navigation;
use DateTimeImmutable;

/**
 * The navigation table: one row per menu.
 *
 * @internal
 *
 * @mago-expect lint:too-many-methods One method per thing the repository does with the menus' rows.
 */
final readonly class MenuTable
{
    public const string TABLE = 'navigation';

    /** Menu order, as the Menus screen lists them. */
    private const array ORDER = ['sequence ASC', 'title ASC', 'navigation_id ASC'];

    public function __construct(
        private Database $database,
    ) {}

    /**
     * @throws StorageException
     */
    private static function hydrate(Row $row): Navigation
    {
        try {
            return new Navigation(
                $row->string('handle'),
                $row->string('title'),
                $row->nullableString('description'),
                $row->int('sequence'),
                $row->int('navigation_id'),
                $row->nullableInt('navigation_revision_id'),
                $row->time('updated'),
            );
        } catch (InvalidItemException $failure) {
            throw StorageException::failed($failure);
        }
    }

    /**
     * @throws StorageException
     */
    public function delete(int $id): void
    {
        $this->database->affectedRows($this->database->sql->delete(self::TABLE)->where(['navigation_id' => $id]));
    }

    /**
     * The live revision of the first published menu with $handle, in menu order.
     *
     * @throws StorageException
     */
    public function liveRevisionId(string $handle): ?int
    {
        foreach ($this->database->rows(
            $this->database
                ->sql
                ->select(self::TABLE)
                ->columns(['navigation_revision_id'])
                ->where(['handle' => $handle])
                ->order(self::ORDER),
        ) as $row) {
            $revisionId = $row->nullableInt('navigation_revision_id');
            if (null !== $revisionId) {
                return $revisionId;
            }
        }

        return null;
    }

    /**
     * @throws NotFoundException
     * @throws StorageException
     */
    public function navigation(int $id): Navigation
    {
        $row = $this->database->row($this->database->sql->select(self::TABLE)->where(['navigation_id' => $id]));

        return null === $row ? throw NotFoundException::navigation($id) : self::hydrate($row);
    }

    /**
     * The menu the site shows for $handle: the first published one in menu order, or the first of
     * them when none is published.
     *
     * @throws StorageException
     */
    public function navigationByHandle(string $handle): ?Navigation
    {
        $menus = $this->navigationsByHandle($handle);
        foreach ($menus as $menu) {
            if (null !== $menu->revisionId) {
                return $menu;
            }
        }

        return $menus[0] ?? null;
    }

    /**
     * @return list<Navigation>
     *
     * @throws StorageException
     */
    public function navigations(): array
    {
        $menus = [];
        foreach ($this->database->rows($this->database->sql->select(self::TABLE)->order(self::ORDER)) as $row) {
            $menus[] = self::hydrate($row);
        }

        return $menus;
    }

    /**
     * Every menu with $handle, in menu order.
     *
     * @return list<Navigation>
     *
     * @throws StorageException
     */
    public function navigationsByHandle(string $handle): array
    {
        $menus = [];
        foreach ($this->database->rows(
            $this->database->sql->select(self::TABLE)->where(['handle' => $handle])->order(self::ORDER),
        ) as $row) {
            $menus[] = self::hydrate($row);
        }

        return $menus;
    }

    /**
     * Adds or changes a menu. Its handle may be another menu's too.
     *
     * @throws NotFoundException When the menu being changed isn't there.
     * @throws StorageException
     */
    public function save(Navigation $navigation, DateTimeImmutable $now): Navigation
    {
        $values = [
            'handle'      => $navigation->handle,
            'title'       => $navigation->title,
            'description' => $navigation->description,
            'sequence'    => $navigation->sequence,
            'updated'     => DbTime::format($now),
        ];

        if (null === $navigation->id) {
            $id = $this->database->insert($this->database->sql->insert(self::TABLE)->values($values));

            return $this->navigation($id);
        }

        $this->database->affectedRows(
            $this->database->sql->update(self::TABLE)->set($values)->where(['navigation_id' => $navigation->id]),
        );

        return $this->navigation($navigation->id);
    }

    /**
     * @throws StorageException
     */
    public function setLiveRevision(int $id, int $revisionId): void
    {
        $this->database->affectedRows(
            $this->database
                ->sql
                ->update(self::TABLE)
                ->set(['navigation_revision_id' => $revisionId])
                ->where(['navigation_id' => $id]),
        );
    }

    /**
     * Bumps a menu's updated time, as any change to its draft does.
     *
     * @throws StorageException
     */
    public function touch(int $id, DateTimeImmutable $now): void
    {
        $this->database->affectedRows(
            $this->database
                ->sql
                ->update(self::TABLE)
                ->set(['updated' => DbTime::format($now)])
                ->where(['navigation_id' => $id]),
        );
    }
}
