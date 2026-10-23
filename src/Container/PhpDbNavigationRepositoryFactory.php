<?php

declare(strict_types=1);

namespace Contenir\Navigation\Container;

use Contenir\Navigation\Exception\ConfigurationException;
use Contenir\Navigation\Storage\PhpDbNavigationRepository;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class PhpDbNavigationRepositoryFactory
{
    /**
     * @throws ConfigurationException
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): PhpDbNavigationRepository
    {
        $config = NavigationConfig::fromContainer($container);

        return new PhpDbNavigationRepository($config->adapter(), $config->clock(), $config->sanitiser());
    }
}
