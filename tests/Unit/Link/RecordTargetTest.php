<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Link;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Link\RecordTarget;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

#[Group('unit')]
final class RecordTargetTest extends TestCase
{
    /**
     * @return array<string, array{string, string|int}>
     */
    public static function invalidProvider(): array
    {
        return [
            'no section'      => ['', 12],
            'a blank section' => ['  ', 12],
            'no id'           => ['page', ''],
            'a blank id'      => ['page', ' '],
            'an id too long'  => ['page', str_repeat('a', times: RecordTarget::LONGEST_ID + 1)],
        ];
    }

    #[Test]
    public function holdsAnIdOfItsLongestLength(): void
    {
        $id = str_repeat('a', times: RecordTarget::LONGEST_ID);

        static::assertSame($id, (new RecordTarget('page', $id))->id);
    }

    #[Test]
    public function holdsItsIdAsText(): void
    {
        $record = new RecordTarget('page', 12);

        static::assertSame(['page', '12'], [$record->section, $record->id]);
    }

    #[Test]
    #[DataProvider('invalidProvider')]
    public function needsASectionAndAnId(string $section, string|int $id): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('it needs a section and an id of at most 64 characters');

        new RecordTarget($section, $id);
    }
}
