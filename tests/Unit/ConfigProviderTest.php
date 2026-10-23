<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit;

use Contenir\Navigation\ConfigProvider;
use Contenir\Navigation\Container\HtmlSanitiserFactory;
use Contenir\Navigation\Container\PhpDbNavigationRepositoryFactory;
use Contenir\Navigation\Container\PublisherFactory;
use Contenir\Navigation\Html\HtmlSanitiser;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Schema\Version20261023000100CreateNavigation;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use Contenir\Navigation\Storage\PhpDbNavigationRepository;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use ReflectionClass;

use function dirname;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function defaultSettings(): void
    {
        static::assertSame(
            ['adapter' => AdapterInterface::class, 'clock' => ClockInterface::class, 'html' => HtmlSanitiser::INLINE],
            (new ConfigProvider())()['contenir_navigation'],
        );
    }

    #[Test]
    public function dependenciesCanBeReadOnTheirOwn(): void
    {
        static::assertSame((new ConfigProvider())()['dependencies'], (new ConfigProvider())->getDependencies());
    }

    #[Test]
    public function listsTheMigrations(): void
    {
        static::assertSame(
            ['schema_migrations' => [
                (new ReflectionClass(Version20261023000100CreateNavigation::class))->getNamespaceName() =>
                    ConfigProvider::schemaPath(),
            ]],
            (new ConfigProvider())()['contenir'],
        );
    }

    #[Test]
    public function registersTheServices(): void
    {
        static::assertSame(
            [
                'aliases'   => [NavigationRepositoryInterface::class => PhpDbNavigationRepository::class],
                'factories' => [
                    HtmlSanitiser::class             => HtmlSanitiserFactory::class,
                    PhpDbNavigationRepository::class => PhpDbNavigationRepositoryFactory::class,
                    Publisher::class                 => PublisherFactory::class,
                ],
            ],
            (new ConfigProvider())->getDependencies(),
        );
    }

    #[Test]
    public function theMigrationsAreInTheSchemaFolder(): void
    {
        static::assertSame(dirname(__DIR__, levels: 2) . '/schema', ConfigProvider::schemaPath());
    }
}
