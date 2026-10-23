<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Menu\Navigation;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;
use function strlen;

#[Group('unit')]
final class NavigationTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'a handle in capitals'      => ['Header', 'Header', 'A menu handle "Header" is not valid'],
            'a handle with a space'     => ['main menu', 'Main', 'A menu handle "main menu" is not valid'],
            'a handle starting a digit' => ['1st', 'First', 'A menu handle "1st" is not valid'],
            'a handle too long'         => [
                'h' . str_repeat('a', times: 64),
                'Header',
                'A menu handle "h' . str_repeat('a', times: 64) . '" is not valid',
            ],
            'an empty handle'           => ['', 'Header', 'A menu handle "" is not valid'],
            'a blank title'             => ['header', '  ', 'A menu title "  " is not valid'],
            'a title too long'          => [
                'header',
                str_repeat('é', times: 256),
                'A menu title "' . str_repeat('é', times: 256) . '" is not valid',
            ],
        ];
    }

    #[Test]
    public function aNewMenuComesFirstUnlessItSaysOtherwise(): void
    {
        static::assertSame(0, (new Navigation('header', 'Header'))->sequence);
    }

    #[Test]
    public function aNewMenuIsNotLiveUntilItHasARevision(): void
    {
        $menu = new Navigation('header', 'Header');

        static::assertSame([false, true], [$menu->isLive(), $menu->withRevision(4)->isLive()]);
    }

    #[Test]
    #[DataProvider('invalidProvider')]
    public function refusesAHandleOrTitleThatBreaksItsRule(string $handle, string $title, string $message): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage($message);

        new Navigation($handle, $title);
    }

    #[Test]
    public function takesAHandleAndTitleAtTheirLongest(): void
    {
        $menu = new Navigation('h' . str_repeat('a', times: 63), str_repeat('é', times: 255));

        static::assertSame(64, strlen($menu->handle));
    }

    #[Test]
    public function withersChangeOnlyWhatTheyName(): void
    {
        $updated = new DateTimeImmutable('2026-10-23 09:00:00');
        $menu    = (new Navigation('footer', 'Footer', 'At the foot of every page', 2))->withId(5)
            ->withRevision(9)
            ->withUpdated($updated);

        static::assertEquals(new Navigation('footer', 'Footer', 'At the foot of every page', 2, 5, 9, $updated), $menu);
    }
}
