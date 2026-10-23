<?php

declare(strict_types=1);

namespace Contenir\Navigation;

use Contenir\Navigation\Container\HtmlSanitiserFactory;
use Contenir\Navigation\Container\NavigationConfig;
use Contenir\Navigation\Container\PhpDbNavigationRepositoryFactory;
use Contenir\Navigation\Container\PublisherFactory;
use Contenir\Navigation\Html\HtmlSanitiser;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use Contenir\Navigation\Storage\PhpDbNavigationRepository;
use PhpDb\Adapter\AdapterInterface;
use Psr\Clock\ClockInterface;

use function dirname;

/**
 * Services and settings for a PSR-11 container that reads Laminas-style
 * configuration. The settings under `contenir_navigation` are described by
 * {@see NavigationConfig}.
 *
 * The navigation tables are created by the migrations in `schema/`, listed
 * under `contenir.schema_migrations` for the host's migration runner.
 *
 * @api
 */
final class ConfigProvider
{
    public const string SCHEMA_NAMESPACE = __NAMESPACE__ . '\\Schema';

    /**
     * The directory of the navigation migrations, for a host that runs them
     * with peptolab/phpdb-migration under {@see SCHEMA_NAMESPACE}.
     */
    public static function schemaPath(): string
    {
        return dirname(__DIR__) . '/schema';
    }

    /**
     * @return array{aliases: array<string, string>, factories: array<string, class-string>}
     */
    public function getDependencies(): array
    {
        return [
            'aliases'   => [
                NavigationRepositoryInterface::class => PhpDbNavigationRepository::class,
            ],
            'factories' => [
                HtmlSanitiser::class             => HtmlSanitiserFactory::class,
                PhpDbNavigationRepository::class => PhpDbNavigationRepositoryFactory::class,
                Publisher::class                 => PublisherFactory::class,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies'        => $this->getDependencies(),
            NavigationConfig::KEY => [
                'adapter' => AdapterInterface::class,
                'clock'   => ClockInterface::class,
                'html'    => HtmlSanitiser::INLINE,
            ],
            'contenir'            => [
                'schema_migrations' => [
                    self::SCHEMA_NAMESPACE => self::schemaPath(),
                ],
            ],
        ];
    }
}
