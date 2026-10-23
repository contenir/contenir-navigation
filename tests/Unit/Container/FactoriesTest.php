<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Container;

use Contenir\Navigation\Container\HtmlSanitiserFactory;
use Contenir\Navigation\Container\PhpDbNavigationRepositoryFactory;
use Contenir\Navigation\Container\PublisherFactory;
use Contenir\Navigation\Html\HtmlSanitiser;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use Contenir\Navigation\Storage\PhpDbNavigationRepository;
use Contenir\Navigation\Storage\Transaction;
use Contenir\Navigation\Tests\TestAsset\Clock\MutableClock;
use Contenir\Navigation\Tests\TestAsset\Container\ArrayContainer;
use Contenir\Navigation\Tests\TestAsset\Storage\InMemoryNavigationRepository;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;

#[Group('unit')]
final class FactoriesTest extends TestCase
{
    #[Test]
    public function makesThePublisherOverTheContainersRepository(): void
    {
        $adapter    = $this->createStub(AdapterInterface::class);
        $clock      = new MutableClock();
        $repository = new InMemoryNavigationRepository();
        $container  = new ArrayContainer([
            AdapterInterface::class              => $adapter,
            ClockInterface::class                => $clock,
            NavigationRepositoryInterface::class => $repository,
        ]);

        static::assertEquals(
            new Publisher($repository, new Transaction($adapter), $clock),
            (new PublisherFactory())($container),
        );
    }

    #[Test]
    public function makesTheRepositoryOverTheSiteDatabase(): void
    {
        $adapter   = $this->createStub(AdapterInterface::class);
        $clock     = new MutableClock();
        $container = new ArrayContainer([
            'config'                => ['contenir_navigation' => ['html' => ['b' => []]]],
            AdapterInterface::class => $adapter,
            ClockInterface::class   => $clock,
        ]);

        static::assertEquals(
            new PhpDbNavigationRepository($adapter, $clock, new HtmlSanitiser(['b' => []])),
            (new PhpDbNavigationRepositoryFactory())($container),
        );
    }

    #[Test]
    public function makesTheSanitiserFromTheSettings(): void
    {
        $container = new ArrayContainer(['config' => ['contenir_navigation' => ['html' => ['p' => []]]]]);

        static::assertEquals(new HtmlSanitiser(['p' => []]), (new HtmlSanitiserFactory())($container));
    }
}
