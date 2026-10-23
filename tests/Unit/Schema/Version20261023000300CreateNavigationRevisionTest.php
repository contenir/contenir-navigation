<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Schema;

use Contenir\Navigation\Schema\Version20261023000300CreateNavigationRevision;
use Contenir\Navigation\Tests\Trait\AdapterPlatformStubTrait;
use PhpDb\Migration\SchemaInspector;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class Version20261023000300CreateNavigationRevisionTest extends TestCase
{
    use AdapterPlatformStubTrait;

    #[Test]
    public function createsTheTableOnMysql(): void
    {
        static::assertSame(
            [
                'CREATE TABLE `navigation_revision` ('
                    . ' `navigation_revision_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                    . ' `navigation_id` INT UNSIGNED NOT NULL,'
                    . ' `items` JSON NOT NULL,'
                    . ' `note` VARCHAR(255) NULL,'
                    . ' `created` DATETIME NOT NULL,'
                    . ' `created_by` INT UNSIGNED NOT NULL,'
                    . ' PRIMARY KEY (`navigation_revision_id`),'
                    . ' KEY `navigation_revision_history` (`navigation_id`, `created`)'
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
                'CREATE TABLE "navigation_revision" ('
                    . ' "navigation_revision_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,'
                    . ' "navigation_id" INTEGER NOT NULL,'
                    . ' "items" TEXT NOT NULL,'
                    . ' "note" VARCHAR(255) NULL,'
                    . ' "created" DATETIME NOT NULL,'
                    . ' "created_by" INTEGER NOT NULL'
                    . ')',
                'CREATE INDEX IF NOT EXISTS "navigation_revision_history" ON "navigation_revision" ("navigation_id", "created")',
            ],
            $this->preview('SQLite', tableExists: false),
        );
    }

    #[Test]
    public function describesItself(): void
    {
        $migration = new Version20261023000300CreateNavigationRevision();

        static::assertSame(
            ['20261023000300', 'Create the navigation_revision table'],
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
                'CREATE INDEX IF NOT EXISTS "navigation_revision_history" ON "navigation_revision" ("navigation_id", "created")',
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
        $inspector->method('tableExists')->willReturnMap([['navigation_revision', $tableExists]]);

        return (new Version20261023000300CreateNavigationRevision())->preview(
            $this->adapterNamed($platform),
            $inspector,
        );
    }
}
