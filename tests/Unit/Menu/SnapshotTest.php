<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\InvalidLinkException;
use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Display;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\ItemKind;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Menu\Snapshot;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SnapshotTest extends TestCase
{
    /**
     * A record item in menu 3 holding an email item.
     *
     * @return list<Node>
     */
    private static function tree(): array
    {
        $team = new Item(
            ItemKind::Record,
            record: new RecordTarget('page', 4),
            fragment: 'staff',
            label: 'Team',
            display: Display::IconLabel,
            icon: 'people',
            target: Item::NEW_TAB,
            class: 'nav__team',
            active: false,
            id: 1,
            navigationId: 3,
            sequence: 0,
        );
        $email = new Item(
            ItemKind::Url,
            url: Url::parse('hello@example.com'),
            label: 'Email us',
            id: 2,
            navigationId: 3,
            parentId: 1,
        );

        return [new Node($team, [new Node($email)])];
    }

    #[Test]
    public function aMissingDisplayOrActiveFlagReadsAsAnActiveLabel(): void
    {
        $nodes = Snapshot::decode([[
            'kind'    => 'text',
            'label'   => 'Shop',
            'display' => 'sideways',
            'active'  => 'no',
        ]], 1);

        static::assertEquals([new Node(Item::text('Shop')->withPlacement(1, null, 0))], $nodes);
    }

    #[Test]
    public function anItemThatIsNotAListOfFieldsIsRefused(): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('A revision item is not a list of fields');

        Snapshot::decode(['Shop'], 1);
    }

    #[Test]
    public function anItemWithAnAddressItCanNoLongerHoldIsRefused(): void
    {
        $this->expectException(InvalidLinkException::class);

        Snapshot::decode([['kind' => 'url', 'url' => 'javascript:alert(1)', 'label' => 'x']], 1);
    }

    #[Test]
    public function anItemWithNoKindIsRefused(): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('A revision item has no kind');

        Snapshot::decode([['label' => 'Shop']], 1);
    }

    #[Test]
    public function encodesEveryFieldWithItsChildren(): void
    {
        static::assertSame(
            [
                [
                    'id'             => 1,
                    'kind'           => 'record',
                    'record_section' => 'page',
                    'record_id'      => '4',
                    'url'            => null,
                    'fragment'       => 'staff',
                    'label'          => 'Team',
                    'html'           => null,
                    'display'        => 'icon_label',
                    'icon'           => 'people',
                    'target'         => '_blank',
                    'class'          => 'nav__team',
                    'active'         => false,
                    'children'       => [
                        [
                            'id'             => 2,
                            'kind'           => 'url',
                            'record_section' => null,
                            'record_id'      => null,
                            'url'            => 'mailto:hello@example.com',
                            'fragment'       => null,
                            'label'          => 'Email us',
                            'html'           => null,
                            'display'        => 'label',
                            'icon'           => null,
                            'target'         => null,
                            'class'          => null,
                            'active'         => true,
                            'children'       => [],
                        ],
                    ],
                ],
            ],
            Snapshot::encode(self::tree()),
        );
    }

    #[Test]
    public function readsATextItemsHtmlAndAnItemWithNoId(): void
    {
        $nodes = Snapshot::decode([['kind' => 'text', 'label' => 'Shop', 'html' => '<b>Shop</b>', 'id' => '7']], 2);

        static::assertEquals([new Node(Item::text('Shop', '<b>Shop</b>')->withPlacement(2, null, 0))], $nodes);
    }

    #[Test]
    public function readsBackWhatItEncodedAsItemsOfTheMenu(): void
    {
        static::assertEquals(self::tree(), Snapshot::decode(Snapshot::encode(self::tree()), 3));
    }

    #[Test]
    public function readsChildrenThatAreNotAListAsNone(): void
    {
        $nodes = Snapshot::decode([['kind' => 'text', 'label' => 'Shop', 'children' => 'none']], 2);

        static::assertSame([], $nodes[0]->children);
    }

    #[Test]
    public function readsEachItemAtItsPosition(): void
    {
        $nodes = Snapshot::decode([['kind' => 'text', 'label' => 'One'], ['kind' => 'text', 'label' => 'Two']], 2);

        static::assertSame([0, 1], [$nodes[0]->item->sequence, $nodes[1]->item->sequence]);
    }
}
