<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Schema;

use Contenir\Navigation\Schema\Version20261023000200CreateNavigationItem;
use Contenir\Navigation\Tests\Trait\AdapterPlatformStubTrait;
use PhpDb\Migration\SchemaInspector;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class Version20261023000200CreateNavigationItemTest extends TestCase
{
    use AdapterPlatformStubTrait;

    #[Test]
    public function createsTheTableOnMysql(): void
    {
        static::assertSame(
            [
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
            ],
            $this->preview('MySQL', tableExists: false),
        );
    }

    #[Test]
    public function createsTheTableOnSqlite(): void
    {
        static::assertSame(
            [
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
                'CREATE INDEX IF NOT EXISTS "navigation_item_tree" ON "navigation_item" ("navigation_id", "parent_id", "sequence")',
                'CREATE INDEX IF NOT EXISTS "navigation_item_record" ON "navigation_item" ("record_section", "record_id")',
            ],
            $this->preview('SQLite', tableExists: false),
        );
    }

    #[Test]
    public function describesItself(): void
    {
        $migration = new Version20261023000200CreateNavigationItem();

        static::assertSame(
            ['20261023000200', 'Create the navigation_item table'],
            [$migration->getVersion(), $migration->getDescription()],
        );
    }

    #[Test]
    public function leavesAnExistingMysqlTable(): void
    {
        static::assertSame([], $this->preview('MySQL', tableExists: true));
    }

    #[Test]
    public function onlyEnsuresTheIndexesOfAnExistingSqliteTable(): void
    {
        static::assertSame(
            [
                'CREATE INDEX IF NOT EXISTS "navigation_item_tree" ON "navigation_item" ("navigation_id", "parent_id", "sequence")',
                'CREATE INDEX IF NOT EXISTS "navigation_item_record" ON "navigation_item" ("record_section", "record_id")',
            ],
            $this->preview('SQLite', tableExists: true),
        );
    }

    /**
     * @return array<string>
     */
    private function preview(string $platform, bool $tableExists): array
    {
        $inspector = $this->createStub(SchemaInspector::class);
        $inspector->method('tableExists')->willReturnMap([['navigation_item', $tableExists]]);

        return (new Version20261023000200CreateNavigationItem())->preview($this->adapterNamed($platform), $inspector);
    }
}
