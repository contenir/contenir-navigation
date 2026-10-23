<?php

declare(strict_types=1);

namespace Contenir\Navigation\Schema;

use Override;
use PhpDb\Migration\AbstractMigration;
use UnexpectedValueException;

/**
 * The `navigation_item` table: one row per element of a menu's draft.
 *
 * @internal Run by the host's migration runner.
 *
 * @mago-expect analysis:missing-constructor AbstractMigration sets its adapter and inspector in up() and preview().
 */
final class Version20261023000200CreateNavigationItem extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Create the navigation_item table';
    }

    #[Override]
    public function getVersion(): string
    {
        return '20261023000200';
    }

    /**
     * @throws UnexpectedValueException On a database other than MySQL or SQLite.
     */
    #[Override]
    protected function define(): void
    {
        $exists = $this->inspector->tableExists('navigation_item');

        if (Platform::isMysql($this->adapter)) {
            $this->executeSqlIf(
                ! $exists,
                'CREATE TABLE `navigation_item` ('
                    . ' `navigation_item_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                    . ' `navigation_id` INT UNSIGNED NOT NULL,'
                    . ' `parent_id` INT UNSIGNED NULL,'
                    . ' `sequence` INT NOT NULL DEFAULT 0,'
                    . ' `kind` VARCHAR(16) NOT NULL,'
                    . ' `record_section` VARCHAR(255) NULL,'
                    . ' `record_id` VARCHAR(64) NULL,'
                    . ' `url` VARCHAR(2083) NULL,'
                    . ' `fragment` VARCHAR(255) NULL,'
                    . ' `label` VARCHAR(255) NULL,'
                    . ' `html` TEXT NULL,'
                    . " `display` VARCHAR(16) NOT NULL DEFAULT 'label',"
                    . ' `icon` VARCHAR(64) NULL,'
                    . ' `target` VARCHAR(16) NULL,'
                    . ' `class` VARCHAR(255) NULL,'
                    . ' `active` TINYINT(1) NOT NULL DEFAULT 1,'
                    . ' `created` DATETIME NOT NULL,'
                    . ' `updated` DATETIME NOT NULL,'
                    . ' PRIMARY KEY (`navigation_item_id`),'
                    . ' KEY `navigation_item_tree` (`navigation_id`, `parent_id`, `sequence`),'
                    . ' KEY `navigation_item_record` (`record_section`, `record_id`)'
                    . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
                'Create table "navigation_item"',
                'Table "navigation_item" already exists',
            );

            return;
        }

        $this->executeSqlIf(
            ! $exists,
            'CREATE TABLE "navigation_item" ('
                . ' "navigation_item_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,'
                . ' "navigation_id" INTEGER NOT NULL,'
                . ' "parent_id" INTEGER NULL,'
                . ' "sequence" INTEGER NOT NULL DEFAULT 0,'
                . ' "kind" VARCHAR(16) NOT NULL,'
                . ' "record_section" VARCHAR(255) NULL,'
                . ' "record_id" VARCHAR(64) NULL,'
                . ' "url" VARCHAR(2083) NULL,'
                . ' "fragment" VARCHAR(255) NULL,'
                . ' "label" VARCHAR(255) NULL,'
                . ' "html" TEXT NULL,'
                . " \"display\" VARCHAR(16) NOT NULL DEFAULT 'label',"
                . ' "icon" VARCHAR(64) NULL,'
                . ' "target" VARCHAR(16) NULL,'
                . ' "class" VARCHAR(255) NULL,'
                . ' "active" INTEGER NOT NULL DEFAULT 1,'
                . ' "created" DATETIME NOT NULL,'
                . ' "updated" DATETIME NOT NULL'
                . ')',
            'Create table "navigation_item"',
            'Table "navigation_item" already exists',
        );
        $this->executeSql(
            'CREATE INDEX IF NOT EXISTS "navigation_item_tree" ON "navigation_item" ("navigation_id", "parent_id", "sequence")',
            'Index "navigation_item.navigation_item_tree"',
        );
        $this->executeSql(
            'CREATE INDEX IF NOT EXISTS "navigation_item_record" ON "navigation_item" ("record_section", "record_id")',
            'Index "navigation_item.navigation_item_record"',
        );
    }
}
