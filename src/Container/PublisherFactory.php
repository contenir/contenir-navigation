<?php

declare(strict_types=1);

namespace Contenir\Navigation\Container;

use Contenir\Navigation\Exception\ConfigurationException;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use Contenir\Navigation\Storage\Transaction;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class PublisherFactory
{
    /**
     * @throws ConfigurationException
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): Publisher
    {
        $config = NavigationConfig::fromContainer($container);
        return new Publisher(
            $container->get(NavigationRepositoryInterface::class),
            new Transaction($config->adapter()),
            $config->clock(),
        );
    }
}
