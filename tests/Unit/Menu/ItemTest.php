<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Display;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\ItemKind;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

#[Group('unit')]
final class ItemTest extends TestCase
{
    /**
     * @return array<string, array{callable(): Item, string}>
     */
    public static function invalidProvider(): array
    {
        $page = new RecordTarget('page', 1);
        $url  = Url::parse('/contact');

        return [
            'a record item with no record'   => [
                static fn(): Item => new Item(ItemKind::Record),
                'A record item links to a record alone',
            ],
            'a record item with a URL too'   => [
                static fn(): Item => new Item(ItemKind::Record, record: $page, url: $url),
                'A record item links to a record alone',
            ],
            'a record item with HTML'        => [
                static fn(): Item => new Item(ItemKind::Record, record: $page, html: '<b>x</b>'),
                'A record item links to a record alone',
            ],
            'a URL item with no URL'         => [
                static fn(): Item => new Item(ItemKind::Url, label: 'Contact'),
                'A URL item links to an address alone, with a label',
            ],
            'a URL item with no label'       => [
                static fn(): Item => new Item(ItemKind::Url, url: $url),
                'A URL item links to an address alone, with a label',
            ],
            'a URL item with a record too'   => [
                static fn(): Item => new Item(ItemKind::Url, record: $page, url: $url, label: 'Contact'),
                'A URL item links to an address alone, with a label',
            ],
            'a URL item with a fragment'     => [
                static fn(): Item => new Item(ItemKind::Url, url: $url, fragment: 'form', label: 'Contact'),
                'A URL item links to an address alone, with a label',
            ],
            'a URL item with HTML'           => [
                static fn(): Item => new Item(ItemKind::Url, url: $url, label: 'Contact', html: '<b>x</b>'),
                'A URL item links to an address alone, with a label',
            ],
            'a text item with no label'      => [
                static fn(): Item => new Item(ItemKind::Text),
                'A text item has a label and no link',
            ],
            'a text item with a blank label' => [
                static fn(): Item => Item::text('   '),
                'A text item has a label and no link',
            ],
            'a text item with a record'      => [
                static fn(): Item => new Item(ItemKind::Text, record: $page, label: 'Shop'),
                'A text item has a label and no link',
            ],
            'a text item with a URL'         => [
                static fn(): Item => new Item(ItemKind::Text, url: $url, label: 'Shop'),
                'A text item has a label and no link',
            ],
            'a text item with a fragment'    => [
                static fn(): Item => new Item(ItemKind::Text, fragment: 'top', label: 'Shop'),
                'A text item has a label and no link',
            ],
            'a text item with a target'      => [
                static fn(): Item => new Item(ItemKind::Text, label: 'Shop', target: '_blank'),
                'A text item has a label and no link',
            ],
            'an icon display with no icon'   => [
                static fn(): Item => new Item(ItemKind::Text, label: 'Shop', display: Display::Icon),
                'An item drawn with an icon needs an icon token',
            ],
            'an icon with label and no icon' => [
                static fn(): Item => new Item(ItemKind::Text, label: 'Shop', display: Display::IconLabel),
                'An item drawn with an icon needs an icon token',
            ],
            'an icon token in capitals'      => [
                static fn(): Item => new Item(ItemKind::Text, label: 'Shop', icon: 'Cart'),
                'An icon token "Cart" is not valid',
            ],
            'an icon token with a space'     => [
                static fn(): Item => new Item(ItemKind::Text, label: 'Shop', icon: 'shopping cart'),
                'An icon token "shopping cart" is not valid',
            ],
            'an icon token starting a dash'  => [
                static fn(): Item => new Item(ItemKind::Text, label: 'Shop', icon: '-cart'),
                'An icon token "-cart" is not valid',
            ],
            'an icon token too long'         => [
                static fn(): Item => new Item(ItemKind::Text, label: 'Shop', icon: 'c' . str_repeat('a', times: 64)),
                'is not valid: use 1 to 64',
            ],
            'a target other than a new tab'  => [
                static fn(): Item => Item::record($page)->withPresentation(null, target: '_self'),
                'A target "_self" is not valid',
            ],
            'classes with a comma'           => [
                static fn(): Item => Item::record($page)->withPresentation(null, class: 'a,b'),
                'Classes "a,b" is not valid',
            ],
            'classes with two spaces'        => [
                static fn(): Item => Item::record($page)->withPresentation(null, class: 'a  b'),
                'Classes "a  b" is not valid',
            ],
            'a class starting a digit'       => [
                static fn(): Item => Item::record($page)->withPresentation(null, class: '2col'),
                'Classes "2col" is not valid',
            ],
            'a fragment with a space'        => [
                static fn(): Item => Item::record($page, 'our team'),
                'A fragment "our team" is not valid',
            ],
            'a fragment with a second hash'  => [
                static fn(): Item => Item::record($page, '#a#b'),
                'A fragment "a#b" is not valid',
            ],
            'a label too long'               => [
                static fn(): Item => Item::text(str_repeat('é', times: 256)),
                'A label "' . str_repeat('é', times: 256) . '" is not valid: it can be at most 255 characters',
            ],
        ];
    }

    #[Test]
    public function aBlankFragmentLabelOrClassIsNone(): void
    {
        $item = new Item(
            ItemKind::Record,
            record: new RecordTarget('page', 1),
            fragment: ' # ',
            label: '  ',
            class: ' ',
        );

        static::assertSame([null, null, null], [$item->fragment, $item->label, $item->class]);
    }

    #[Test]
    public function aFragmentLosesItsHash(): void
    {
        static::assertSame('staff', Item::record(new RecordTarget('page', 1), ' #staff ')->fragment);
    }

    #[Test]
    public function aLabelAtItsLongestIsKept(): void
    {
        static::assertSame(str_repeat('é', times: 255), Item::text(str_repeat('é', times: 255))->label);
    }

    #[Test]
    public function aRecordItemIsANewActiveLabelWithNoPlaceYet(): void
    {
        $item = Item::record(new RecordTarget('news', 'launch'), label: ' Launch ');

        static::assertEquals(
            new Item(ItemKind::Record, record: new RecordTarget('news', 'launch'), label: 'Launch'),
            $item,
        );
    }

    #[Test]
    public function aTextItemKeepsItsHtmlButNotBlankHtml(): void
    {
        static::assertSame(
            ['<strong>Shop</strong>', null],
            [Item::text('Shop', '<strong>Shop</strong>')->html, Item::text('Shop', "  \n")->html],
        );
    }

    #[Test]
    public function aUrlItemToAnotherSiteOpensInANewTab(): void
    {
        static::assertSame(
            [Item::NEW_TAB, null],
            [
                Item::url(Url::parse('https://example.com'), 'Shop')->target,
                Item::url(Url::parse('/shop'), 'Shop')->target,
            ],
        );
    }

    #[Test]
    public function classesAndIconTokensTheirRulesAllowAreKept(): void
    {
        $item = Item::text('Cart')->withPresentation(
            'Cart',
            Display::IconLabel,
            'fa:shopping_cart-2.0',
            class: 'nav__item -wide',
        );

        static::assertSame(['fa:shopping_cart-2.0', 'nav__item -wide'], [$item->icon, $item->class]);
    }

    /**
     * @param callable(): Item $make
     */
    #[Test]
    #[DataProvider('invalidProvider')]
    public function refusesAnItemThatBreaksARule(callable $make, string $message): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage($message);

        $make();
    }

    #[Test]
    public function withHtmlChangesOnlyTheHtml(): void
    {
        static::assertEquals(
            Item::text('Shop', '<em>Shop</em>'),
            Item::text('Shop', '<b>x</b>')->withHtml('<em>Shop</em>'),
        );
    }

    #[Test]
    public function withLinkToARecordKeepsTheFragmentAndDropsTheUrl(): void
    {
        $item = Item::url(Url::parse('/contact'), 'Contact')->withLinkTo(new RecordTarget('page', 3), 'form');

        static::assertEquals(
            new Item(ItemKind::Record, record: new RecordTarget('page', 3), fragment: 'form', label: 'Contact'),
            $item,
        );
    }

    #[Test]
    public function withLinkToAUrlDropsTheRecordAndFragment(): void
    {
        $item = Item::record(new RecordTarget('page', 3), 'form', 'Contact')->withLinkTo(
            Url::parse('/contact'),
            'ignored',
        );

        static::assertEquals(new Item(ItemKind::Url, url: Url::parse('/contact'), label: 'Contact'), $item);
    }

    #[Test]
    public function withPlacementPutsItInAMenu(): void
    {
        $item = Item::text('Shop')->withPlacement(2, 7, 3);

        static::assertSame([2, 7, 3], [$item->navigationId, $item->parentId, $item->sequence]);
    }

    #[Test]
    public function withPresentationDefaultsToAnActiveLabel(): void
    {
        $item = Item::text('Shop')
            ->withPresentation('Shop', Display::Icon, 'cart', class: 'x')
            ->withPresentation('Store');

        static::assertEquals(Item::text('Store'), $item);
    }

    #[Test]
    public function withPresentationSetsEveryFieldOfTheEditPanel(): void
    {
        $item = Item::record(new RecordTarget('page', 1))
            ->withPresentation('Home', Display::Icon, 'home', Item::NEW_TAB, 'nav__home', active: false);

        static::assertEquals(
            new Item(
                ItemKind::Record,
                record: new RecordTarget('page', 1),
                label: 'Home',
                display: Display::Icon,
                icon: 'home',
                target: Item::NEW_TAB,
                class: 'nav__home',
                active: false,
            ),
            $item,
        );
    }

    #[Test]
    public function withTimesSetsWhenItWasCreatedAndUpdated(): void
    {
        $created = new DateTimeImmutable('2026-10-20 09:00:00');
        $updated = new DateTimeImmutable('2026-10-23 09:00:00');
        $item    = Item::text('Shop')->withTimes($created, $updated);

        static::assertSame([$created, $updated], [$item->created, $item->updated]);
    }
}
