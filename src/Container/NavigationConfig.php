<?php

declare(strict_types=1);

namespace Contenir\Navigation\Container;

use Contenir\Navigation\Clock\SystemClock;
use Contenir\Navigation\Exception\ConfigurationException;
use Contenir\Navigation\Html\HtmlSanitiser;
use PhpDb\Adapter\AdapterInterface;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use function is_array;
use function is_string;

/**
 * The settings under contenir_navigation in the container's config:
 *
 * - `adapter`: the container entry for the site database (default
 *   {@see AdapterInterface}).
 * - `clock`: the container entry for a PSR-20 clock, used when the
 *   container has it; otherwise the system clock in UTC.
 * - `html`: the elements a text item's HTML may hold, each with the
 *   attributes it may keep (default {@see HtmlSanitiser::INLINE}).
 *
 * @internal
 */
final readonly class NavigationConfig
{
    public const string KEY = 'contenir_navigation';

    /**
     * @param array<array-key, mixed> $settings
     */
    private function __construct(
        private ContainerInterface $container,
        private array $settings,
    ) {}

    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment The config service is untyped; its shape is checked here.
     */
    public static function fromContainer(ContainerInterface $container): self
    {
        $config     = $container->has('config') ? $container->get('config') : [];
        $navigation = is_array($config) ? $config[self::KEY] ?? [] : [];

        return new self($container, is_array($navigation) ? $navigation : []);
    }

    /**
     * @throws ConfigurationException
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment A container's services are untyped; the adapter is checked here.
     */
    public function adapter(): AdapterInterface
    {
        $name    = $this->name('adapter', AdapterInterface::class);
        $adapter = $this->container->get($name);

        return $adapter instanceof AdapterInterface
            ? $adapter
            : throw ConfigurationException::wrongService($name, 'php-db adapter');
    }

    /**
     * @throws ConfigurationException
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment A container's services are untyped; the clock is checked here.
     */
    public function clock(): ClockInterface
    {
        $name = $this->name('clock', ClockInterface::class);
        if (! $this->container->has($name)) {
            return new SystemClock();
        }

        $clock = $this->container->get($name);

        return $clock instanceof ClockInterface
            ? $clock
            : throw ConfigurationException::wrongService($name, 'PSR-20 clock');
    }

    /**
     * @throws ConfigurationException
     */
    public function sanitiser(): HtmlSanitiser
    {
        return new HtmlSanitiser(HtmlSettings::allowed(
            $this->settings['html'] ?? HtmlSanitiser::INLINE,
            self::KEY . '.html',
        ));
    }

    /**
     * @throws ConfigurationException
     *
     * @mago-expect analysis:mixed-assignment Settings are untyped; the name is checked here.
     */
    private function name(string $key, string $default): string
    {
        $name = $this->settings[$key] ?? $default;

        return is_string($name) && '' !== $name
            ? $name
            : throw ConfigurationException::invalidSetting(self::KEY . ".{$key}", 'the name of a container entry');
    }
}
