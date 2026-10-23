<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Schema;

use Contenir\Navigation\Schema\Version20261023000100CreateNavigation;
use Contenir\Navigation\Tests\Trait\AdapterPlatformStubTrait;
use PhpDb\Migration\SchemaInspector;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class Version20261023000100CreateNavigationTest extends TestCase
{
    use AdapterPlatformStubTrait;

    #[Test]
    public function createsTheTableOnMysql(): void
    {
        static::assertSame(
            [
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
            ],
            $this->preview('MySQL', tableExists: false),
        );
    }

    #[Test]
    public function createsTheTableOnSqlite(): void
    {
        static::assertSame(
            [
                'CREATE TABLE "navigation" ('
                    . ' "navigation_id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,'
                    . ' "handle" VARCHAR(64) NOT NULL,'
                    . ' "title" VARCHAR(255) NOT NULL,'
                    . ' "description" TEXT NULL,'
                    . ' "sequence" INTEGER NOT NULL DEFAULT 0,'
                    . ' "navigation_revision_id" INTEGER NULL,'
                    . ' "updated" DATETIME NOT NULL'
                    . ')',
                'CREATE UNIQUE INDEX IF NOT EXISTS "navigation_handle" ON "navigation" ("handle")',
            ],
            $this->preview('SQLite', tableExists: false),
        );
    }

    #[Test]
    public function describesItself(): void
    {
        $migration = new Version20261023000100CreateNavigation();

        static::assertSame(
            ['20261023000100', 'Create the navigation table'],
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
                'CREATE UNIQUE INDEX IF NOT EXISTS "navigation_handle" ON "navigation" ("handle")',
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
        $inspector->method('tableExists')->willReturnMap([['navigation', $tableExists]]);

        return (new Version20261023000100CreateNavigation())->preview($this->adapterNamed($platform), $inspector);
    }
}
