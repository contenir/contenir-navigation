<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Menu\PublishedBy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function mb_strlen;
use function str_repeat;
use function strlen;

#[Group('unit')]
final class PublishedByTest extends TestCase
{
    /**
     * @return array<string, array{string|int, string, string}>
     */
    public static function invalidProvider(): array
    {
        return [
            'no id'           => ['', 'Ann Editor', 'A publisher id "" is not valid'],
            'a blank id'      => [' ', 'Ann Editor', 'A publisher id " " is not valid'],
            'an id too long'  => [
                str_repeat('a', times: 65),
                'Ann Editor',
                'A publisher id "' . str_repeat('a', times: 65) . '" is not valid',
            ],
            'no name'         => [7, '', 'A publisher name "" is not valid'],
            'a blank name'    => [7, '  ', 'A publisher name "  " is not valid'],
            'a name too long' => [
                7,
                str_repeat('é', times: 256),
                'A publisher name "' . str_repeat('é', times: 256) . '" is not valid',
            ],
        ];
    }

    #[Test]
    public function holdsAnIdAndNameAtTheirLongest(): void
    {
        $publisher = new PublishedBy(str_repeat('a', times: 64), str_repeat('é', times: 255));

        static::assertSame([64, 255], [strlen($publisher->id), mb_strlen($publisher->name)]);
    }

    #[Test]
    public function holdsItsIdAsText(): void
    {
        $publisher = new PublishedBy(7, 'Ann Editor');

        static::assertSame(['7', 'Ann Editor'], [$publisher->id, $publisher->name]);
    }

    #[Test]
    #[DataProvider('invalidProvider')]
    public function needsAnIdAndAName(string|int $id, string $name, string $message): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage($message);

        new PublishedBy($id, $name);
    }
}
