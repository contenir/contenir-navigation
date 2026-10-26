<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Schema;

use Contenir\Navigation\Schema\Version20261026000100ShareNavigationHandles;
use Contenir\Navigation\Tests\Trait\AdapterPlatformStubTrait;
use PhpDb\Migration\SchemaInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class Version20261026000100ShareNavigationHandlesTest extends TestCase
{
    use AdapterPlatformStubTrait;

    /**
     * @return array<string, array{bool, bool, list<string>}>
     */
    public static function mysqlProvider(): array
    {
        return [
            'the unique index, no lookup yet'  => [
                true,
                false,
                [
                    'CREATE INDEX `navigation_lookup` ON `navigation` (`handle`)',
                    'ALTER TABLE `navigation` DROP INDEX `navigation_handle`',
                ],
            ],
            'already shared'                   => [false, true, []],
            'the lookup, the unique index too' => [
                true,
                true,
                ['ALTER TABLE `navigation` DROP INDEX `navigation_handle`'],
            ],
        ];
    }

    #[Test]
    public function describesItself(): void
    {
        $migration = new Version20261026000100ShareNavigationHandles();

        static::assertSame(
            ['20261026000100', 'Let menus share a handle'],
            [$migration->getVersion(), $migration->getDescription()],
        );
    }

    /**
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('mysqlProvider')]
    public function swapsTheUniqueIndexForALookupOnMysqlOnlyAsFarAsNeeded(
        bool $unique,
        bool $lookup,
        array $expected,
    ): void {
        static::assertSame($expected, $this->preview('MySQL', $unique, $lookup));
    }

    #[Test]
    public function swapsTheUniqueIndexForALookupOnSqlite(): void
    {
        static::assertSame(
            [
                'CREATE INDEX IF NOT EXISTS "navigation_lookup" ON "navigation" ("handle")',
                'DROP INDEX IF EXISTS "navigation_handle"',
            ],
            $this->preview('SQLite', unique: true, lookup: false),
        );
    }

    /**
     * @return array<string>
     */
    private function preview(string $platform, bool $unique, bool $lookup): array
    {
        $inspector = $this->createStub(SchemaInspector::class);
        $inspector->method('indexExists')
            ->willReturnMap([
                ['navigation', 'navigation_handle', $unique],
                ['navigation', 'navigation_lookup', $lookup],
            ]);

        return (new Version20261026000100ShareNavigationHandles())->preview($this->adapterNamed($platform), $inspector);
    }
}
