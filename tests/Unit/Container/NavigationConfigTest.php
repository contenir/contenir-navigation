<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Container;

use Contenir\Navigation\Clock\SystemClock;
use Contenir\Navigation\Container\NavigationConfig;
use Contenir\Navigation\Exception\ConfigurationException;
use Contenir\Navigation\Html\HtmlSanitiser;
use Contenir\Navigation\Tests\TestAsset\Clock\MutableClock;
use Contenir\Navigation\Tests\TestAsset\Container\ArrayContainer;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use stdClass;

#[Group('unit')]
final class NavigationConfigTest extends TestCase
{
    /**
     * @return array<string, array{mixed, string}>
     */
    public static function badHtmlProvider(): array
    {
        return [
            'not a list'                  => [
                'strong',
                'contenir_navigation.html must be a list of attributes by element',
            ],
            'an element without a name'   => [
                [['href']],
                'contenir_navigation.html must be a list of attributes by element',
            ],
            'attributes that are no list' => [
                ['a' => 'href'],
                'contenir_navigation.html must be a list of attributes by element',
            ],
            'an attribute not named'      => [
                ['a' => [1]],
                'contenir_navigation.html.a must be a list of attribute names',
            ],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function badNameProvider(): array
    {
        return [
            'empty'      => [''],
            'not a name' => [12],
        ];
    }

    #[Test]
    public function aClockTheContainerLacksIsTheSystemClock(): void
    {
        static::assertInstanceOf(SystemClock::class, NavigationConfig::fromContainer(new ArrayContainer())->clock());
    }

    #[Test]
    #[DataProvider('badNameProvider')]
    public function anEntryNameThatIsNotOneIsRefused(mixed $name): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('The setting contenir_navigation.adapter must be the name of a container entry');

        NavigationConfig::fromContainer(new ArrayContainer([
            'config' => ['contenir_navigation' => ['adapter' => $name]],
        ]))->adapter();
    }

    #[Test]
    public function anEntryThatIsNotAClockIsRefused(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('The container entry "' . ClockInterface::class . '" is not a PSR-20 clock');

        NavigationConfig::fromContainer(new ArrayContainer([ClockInterface::class => new stdClass()]))->clock();
    }

    #[Test]
    public function anEntryThatIsNotAnAdapterIsRefused(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('The container entry "db" is not a php-db adapter');

        NavigationConfig::fromContainer(new ArrayContainer([
            'config' => ['contenir_navigation' => ['adapter' => 'db']],
            'db'     => new stdClass(),
        ]))->adapter();
    }

    #[Test]
    public function configThatIsNotAListIsNone(): void
    {
        static::assertEquals(
            new HtmlSanitiser(),
            NavigationConfig::fromContainer(new ArrayContainer(['config' => 'none']))->sanitiser(),
        );
    }

    #[Test]
    #[DataProvider('badHtmlProvider')]
    public function htmlSettingsThatAreNotAttributesByElementAreRefused(mixed $html, string $message): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("The setting {$message}");

        NavigationConfig::fromContainer(new ArrayContainer([
            'config' => ['contenir_navigation' => ['html' => $html]],
        ]))->sanitiser();
    }

    #[Test]
    public function readsTheDefaultEntries(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $clock   = new MutableClock();
        $config  = NavigationConfig::fromContainer(new ArrayContainer([
            AdapterInterface::class => $adapter,
            ClockInterface::class   => $clock,
        ]));

        static::assertSame([$adapter, $clock], [$config->adapter(), $config->clock()]);
    }

    #[Test]
    public function readsTheEntriesItNames(): void
    {
        $adapter = $this->createStub(AdapterInterface::class);
        $clock   = new MutableClock();
        $config  = NavigationConfig::fromContainer(new ArrayContainer([
            'config'     => ['contenir_navigation' => ['adapter' => 'site.db', 'clock' => 'site.clock']],
            'site.db'    => $adapter,
            'site.clock' => $clock,
        ]));

        static::assertSame([$adapter, $clock], [$config->adapter(), $config->clock()]);
    }

    #[Test]
    public function sanitisesWithTheElementsItIsGiven(): void
    {
        $sanitiser = NavigationConfig::fromContainer(new ArrayContainer([
            'config' => ['contenir_navigation' => ['html' => ['p' => ['class']]]],
        ]))->sanitiser();

        static::assertEquals(new HtmlSanitiser(['p' => ['class']]), $sanitiser);
    }

    #[Test]
    public function sanitisesWithTheInlineSetByDefault(): void
    {
        static::assertEquals(new HtmlSanitiser(), NavigationConfig::fromContainer(new ArrayContainer())->sanitiser());
    }

    #[Test]
    public function settingsThatAreNotAListAreNone(): void
    {
        static::assertEquals(
            new HtmlSanitiser(),
            NavigationConfig::fromContainer(new ArrayContainer(['config' => [
                'contenir_navigation' => 'none',
            ]]))->sanitiser(),
        );
    }
}
