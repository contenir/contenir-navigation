<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\TestAsset\Container;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

final class ContainerException extends RuntimeException implements ContainerExceptionInterface {}
