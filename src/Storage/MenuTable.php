<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Menu\Navigation;
use DateTimeImmutable;

use function sprintf;

/**
 * The navigation table: one row per menu.
 *
 * @internal
 */
final readonly class MenuTable
{
    public const string TABLE = 'navigation';

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
     * @throws StorageException
     */
    public function liveRevisionId(string $handle): ?int
    {
        return $this->database
            ->row($this->database
                ->sql
                ->select(self::TABLE)
                ->columns(['navigation_revision_id'])
                ->where(['handle' => $handle]))
            ?->nullableInt('navigation_revision_id');
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
     * @throws StorageException
     */
    public function navigationByHandle(string $handle): ?Navigation
    {
        $row = $this->database->row($this->database->sql->select(self::TABLE)->where(['handle' => $handle]));

        return null === $row ? null : self::hydrate($row);
    }

    /**
     * @return list<Navigation>
     *
     * @throws StorageException
     */
    public function navigations(): array
    {
        $menus = [];
        foreach ($this->database->rows(
            $this->database
                ->sql
                ->select(self::TABLE)
                ->order([
                    'sequence ASC',
                    'title ASC',
                    'navigation_id ASC',
                ]),
        ) as $row) {
            $menus[] = self::hydrate($row);
        }

        return $menus;
    }

    /**
     * @throws InvalidItemException When another menu has its handle.
     * @throws NotFoundException    When the menu being changed isn't there.
     * @throws StorageException
     */
    public function save(Navigation $navigation, DateTimeImmutable $now): Navigation
    {
        $holder = $this->navigationByHandle($navigation->handle);
        if (null !== $holder && $holder->id !== $navigation->id) {
            throw InvalidItemException::because(sprintf(
                'Another menu already has the handle "%s"',
                $navigation->handle,
            ));
        }

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
