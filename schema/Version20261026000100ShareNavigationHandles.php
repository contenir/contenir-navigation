<?php

declare(strict_types=1);

namespace Contenir\Navigation\Schema;

use Override;
use PhpDb\Migration\AbstractMigration;
use UnexpectedValueException;

/**
 * Lets two menus share a handle: the unique index on `navigation.handle` gives way to a plain one,
 * which still serves the lookup a site makes by handle.
 *
 * @internal Run by the host's migration runner.
 *
 * @mago-expect analysis:missing-constructor AbstractMigration sets its adapter and inspector in up() and preview().
 */
final class Version20261026000100ShareNavigationHandles extends AbstractMigration
{
    #[Override]
    public function getDescription(): string
    {
        return 'Let menus share a handle';
    }

    #[Override]
    public function getVersion(): string
    {
        return '20261026000100';
    }

    /**
     * @throws UnexpectedValueException On a database other than MySQL or SQLite.
     */
    #[Override]
    protected function define(): void
    {
        $unique = $this->inspector->indexExists('navigation', 'navigation_handle');
        $lookup = $this->inspector->indexExists('navigation', 'navigation_lookup');

        if (Platform::isMysql($this->adapter)) {
            $this->executeSqlIf(
                ! $lookup,
                'CREATE INDEX `navigation_lookup` ON `navigation` (`handle`)',
                'Index "navigation.navigation_lookup"',
                'Index "navigation.navigation_lookup" already exists',
            );
            $this->executeSqlIf(
                $unique,
                'ALTER TABLE `navigation` DROP INDEX `navigation_handle`',
                'Drop unique index "navigation.navigation_handle"',
                'Unique index "navigation.navigation_handle" already dropped',
            );

            return;
        }

        $this->executeSql(
            'CREATE INDEX IF NOT EXISTS "navigation_lookup" ON "navigation" ("handle")',
            'Index "navigation.navigation_lookup"',
        );
        $this->executeSql(
            'DROP INDEX IF EXISTS "navigation_handle"',
            'Drop unique index "navigation.navigation_handle"',
        );
    }
}
