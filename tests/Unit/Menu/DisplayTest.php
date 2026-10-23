<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Menu;

use Contenir\Navigation\Menu\Display;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class DisplayTest extends TestCase
{
    /**
     * @return array<string, array{Display, bool}>
     */
    public static function displayProvider(): array
    {
        return [
            'a label'            => [Display::Label, false],
            'an icon'            => [Display::Icon, true],
            'an icon with label' => [Display::IconLabel, true],
        ];
    }

    #[Test]
    #[DataProvider('displayProvider')]
    public function showsAnIconUnlessItIsALabelAlone(Display $display, bool $icon): void
    {
        static::assertSame($icon, $display->showsIcon());
    }
}
