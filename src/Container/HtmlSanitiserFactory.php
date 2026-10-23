<?php

declare(strict_types=1);

namespace Contenir\Navigation\Container;

use Contenir\Navigation\Exception\ConfigurationException;
use Contenir\Navigation\Html\HtmlSanitiser;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * @api
 */
final class HtmlSanitiserFactory
{
    /**
     * @throws ConfigurationException
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container): HtmlSanitiser
    {
        return NavigationConfig::fromContainer($container)->sanitiser();
    }
}
