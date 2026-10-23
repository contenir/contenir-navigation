<?php

declare(strict_types=1);

namespace Contenir\Navigation\Schema;

use Override;
use PhpDb\Migration\AbstractMigration;
use UnexpectedValueException;

/**
 * The `navigation` table: one row per menu, such as header or footer.
 *
 * @internal Run by the host's migration runner.
 *
 * @mago-expect analysis:missing-constructor AbstractMigration sets its adapter and inspector in up() and preview().
 */
final class Version20261023000100CreateNavigation extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Create the navigation table';
    }

    #[Override]
    public function getVersion(): string
    {
        return '20261023000100';
    }

    /**
     * @throws UnexpectedValueException On a database other than MySQL or SQLite.
     */
    #[Override]
    protected function define(): void
    {
        $exists = $this->inspector->tableExists('navigation');

        if (Platform::isMysql($this->adapter)) {
            $this->executeSqlIf(
                ! $exists,
                'CREATE TABLE `navigation` ('
                    . ' `navigation_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                    . ' `handle` VARCHAR(64) NOT NULL,'
                    . ' `title` VARCHAR(255) NOT NULL,'
                    . ' `description` TEXT NULL,'
                    . ' `sequence` INT NOT NULL DEFAULT 0,'
                    . ' `navigation_revision_id` INT UNSIGNED NULL,'
                    . ' `updated` DATETIME NOT NULL,'
                    . ' PRIMARY KEY (`navigation_id`),'
                    . ' UNIQUE KEY `navigation_handle` (`handle`)'
                    . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'Create table "navigation"',
                'Table "navigation" already exists',
            );

            return;
        }

        $this->executeSqlIf(
            ! $exists,
            'CREATE TABLE "navigation" ('
                . ' "navigation_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,'
                . ' "handle" VARCHAR(64) NOT NULL,'
                . ' "title" VARCHAR(255) NOT NULL,'
                . ' "description" TEXT NULL,'
                . ' "sequence" INTEGER NOT NULL DEFAULT 0,'
                . ' "navigation_revision_id" INTEGER NULL,'
                . ' "updated" DATETIME NOT NULL'
                . ')',
            'Create table "navigation"',
            'Table "navigation" already exists',
        );
        $this->executeSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS "navigation_handle" ON "navigation" ("handle")',
            'Index "navigation.navigation_handle"',
        );
    }
}
