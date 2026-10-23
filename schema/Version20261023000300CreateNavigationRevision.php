<?php

declare(strict_types=1);

namespace Contenir\Navigation\Schema;

use Override;
use PhpDb\Migration\AbstractMigration;
use UnexpectedValueException;

/**
 * The `navigation_revision` table: one row per publish of a menu, holding its whole item tree.
 *
 * @internal Run by the host's migration runner.
 *
 * @mago-expect analysis:missing-constructor AbstractMigration sets its adapter and inspector in up() and preview().
 */
final class Version20261023000300CreateNavigationRevision extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Create the navigation_revision table';
    }

    #[Override]
    public function getVersion(): string
    {
        return '20261023000300';
    }

    /**
     * @throws UnexpectedValueException On a database other than MySQL or SQLite.
     */
    #[Override]
    protected function define(): void
    {
        $exists = $this->inspector->tableExists('navigation_revision');

        if (Platform::isMysql($this->adapter)) {
            $this->executeSqlIf(
                ! $exists,
                'CREATE TABLE `navigation_revision` ('
                    . ' `navigation_revision_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                    . ' `navigation_id` INT UNSIGNED NOT NULL,'
                    . ' `items` JSON NOT NULL,'
                    . ' `note` VARCHAR(255) NULL,'
                    . ' `created` DATETIME NOT NULL,'
                    . ' `created_by` VARCHAR(64) NULL,'
                    . ' `created_by_name` VARCHAR(255) NULL,'
                    . ' PRIMARY KEY (`navigation_revision_id`),'
                    . ' KEY `navigation_revision_history` (`navigation_id`, `created`)'
                    . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'Create table "navigation_revision"',
                'Table "navigation_revision" already exists',
            );

            return;
        }

        $this->executeSqlIf(
            ! $exists,
            'CREATE TABLE "navigation_revision" ('
                . ' "navigation_revision_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,'
                . ' "navigation_id" INTEGER NOT NULL,'
                . ' "items" TEXT NOT NULL,'
                . ' "note" VARCHAR(255) NULL,'
                . ' "created" DATETIME NOT NULL,'
                . ' "created_by" VARCHAR(64) NULL,'
                . ' "created_by_name" VARCHAR(255) NULL'
                . ')',
            'Create table "navigation_revision"',
            'Table "navigation_revision" already exists',
        );
        $this->executeSql(
            'CREATE INDEX IF NOT EXISTS "navigation_revision_history" ON "navigation_revision" ("navigation_id", "created")',
            'Index "navigation_revision.navigation_revision_history"',
        );
    }
}
