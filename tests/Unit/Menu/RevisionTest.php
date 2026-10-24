<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Menu\PublishedBy;
use Contenir\Navigation\Menu\Revision;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

#[Group('unit')]
final class RevisionTest extends TestCase
{
    #[Test]
    public function aBlankNoteIsNone(): void
    {
        static::assertNull((new Revision(1, [], new DateTimeImmutable(), null, '   '))->note);
    }

    #[Test]
    public function aNoteAtItsLongestIsKeptTrimmed(): void
    {
        $note = str_repeat('é', times: 255);

        static::assertSame($note, (new Revision(1, [], new DateTimeImmutable(), null, " {$note} "))->note);
    }

    #[Test]
    public function aNoteTooLongIsRefused(): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('it can be at most 255 characters');

        new Revision(1, [], new DateTimeImmutable(), null, str_repeat('é', times: 256));
    }

    #[Test]
    public function withIdKeepsEverythingElse(): void
    {
        $created = new DateTimeImmutable('2026-10-23 09:00:00');

        static::assertEquals(
            new Revision(2, [], $created, new PublishedBy(5, 'Ann Editor'), 'Added Shop', 8),
            (new Revision(2, [], $created, new PublishedBy(5, 'Ann Editor'), 'Added Shop'))->withId(8),
        );
    }
}
