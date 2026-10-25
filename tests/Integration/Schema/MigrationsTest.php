<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Integration\Schema;

use Contenir\Navigation\Tests\TestAsset\Database\TestDatabase;
use Contenir\Navigation\Tests\Trait\TestDatabaseTrait;
use Override;
use PhpDb\Migration\SchemaInspector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function array_values;
use function in_array;
use function sort;

#[Group('integration')]
final class MigrationsTest extends TestCase
{
    use TestDatabaseTrait;

    /**
     * @return array<string, array{string, list<string>}>
     */
    public static function tableProvider(): array
    {
        return [
            'menus'     => [
                'navigation',
                ['description', 'handle', 'navigation_id', 'navigation_revision_id', 'sequence', 'title', 'updated'],
            ],
            'items'     => [
                'navigation_item',
                [
                    'active',
                    'class',
                    'created',
                    'display',
                    'fragment',
                    'html',
                    'icon',
                    'kind',
                    'label',
                    'navigation_id',
                    'navigation_item_id',
                    'parent_id',
                    'record_id',
                    'record_section',
                    'sequence',
                    'target',
                    'updated',
                    'url',
                ],
            ],
            'revisions' => [
                'navigation_revision',
                [
                    'created',
                    'created_by',
                    'created_by_name',
                    'items',
                    'navigation_id',
                    'navigation_revision_id',
                    'note',
                ],
            ],
        ];
    }

    /**
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('tableProvider')]
    public function createsEachTableWithItsColumns(string $table, array $expected): void
    {
        $columns = array_map(
            static fn(array $column): string => (string) $column['name'],
            array_values((new SchemaInspector($this->adapter, TestDatabase::metadata($this->adapter)))->getColumns(
                $table,
            )),
        );
        sort($columns);

        static::assertSame($expected, $columns);
    }

    #[Test]
    public function keepsAPlainIndexOnTheHandleSoMenusCanShareOne(): void
    {
        $indexes = $this->indexes('navigation');

        static::assertSame(
            [true, false],
            [
                in_array('navigation_lookup', $indexes, strict: true),
                in_array('navigation_handle', $indexes, strict: true),
            ],
        );
    }

    #[Test]
    public function runningThemAgainChangesNothing(): void
    {
        static::assertSame([], TestDatabase::migrations($this->adapter)->runPending());
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpDatabase();
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
    }

    /**
     * The names of $table's indexes, as the database lists them.
     *
     * @return list<string>
     */
    private function indexes(string $table): array
    {
        $mysql = 'MySQL' === $this->adapter->getPlatform()->getName();
        $rows  = $this->adapter
            ->executeQuery($this->adapter->prepareQuery(
                $mysql ? "SHOW INDEX FROM `{$table}`" : "PRAGMA index_list(\"{$table}\")",
            ))
            ->getQueryResult()
            ->toArray();

        return array_values(array_map(
            static fn(array $row): string => (string) $row[$mysql ? 'Key_name' : 'name'],
            $rows,
        ));
    }
}
