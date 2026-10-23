<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Integration\Storage;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Display;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\ItemKind;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Menu\Revision;
use Contenir\Navigation\Menu\Tree;
use Contenir\Navigation\Storage\PhpDbNavigationRepository;
use Contenir\Navigation\Tests\TestAsset\Clock\MutableClock;
use Contenir\Navigation\Tests\Trait\TestDatabaseTrait;
use DateTimeImmutable;
use Override;
use PhpDb\Adapter\Adapter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function count;
use function usort;

#[Group('integration')]
#[Group('repository')]
final class PhpDbNavigationRepositoryTest extends TestCase
{
    use TestDatabaseTrait;

    private MutableClock $clock;

    private PhpDbNavigationRepository $repository;

    #[Test]
    public function aMenuCanKeepItsOwnHandle(): void
    {
        $menu = $this->repository->saveNavigation(new Navigation('header', 'Header'));

        static::assertSame(
            'Main',
            $this->repository->saveNavigation(new Navigation('header', 'Main', id: $menu->id))->title,
        );
    }

    #[Test]
    public function aMenuRowThatCannotBeAMenuIsAStorageFailure(): void
    {
        $this->adapter->query(
            "INSERT INTO navigation (handle, title, sequence, updated) VALUES ('Bad Handle', 'Bad', 0, '2026-10-23 09:00:00')",
            Adapter::QUERY_MODE_EXECUTE,
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('A menu handle "Bad Handle" is not valid');

        $this->repository->navigations();
    }

    #[Test]
    public function aMenuThatIsNotThereIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('There is no menu 9');

        $this->repository->navigation(9);
    }

    #[Test]
    public function aNewMenuIsSavedWithItsIdAndTime(): void
    {
        $menu = $this->repository->saveNavigation(new Navigation('header', 'Header', 'Across the top', 2));

        static::assertEquals(
            new Navigation('header', 'Header', 'Across the top', 2, $menu->id, null, $this->clock->now()),
            $this->repository->navigation((int) $menu->id),
        );
    }

    #[Test]
    public function anItemNeedsAMenu(): void
    {
        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('A menu item needs a menu');

        $this->repository->saveItem(Item::text('Shop'));
    }

    #[Test]
    public function anItemsActiveSwitchIsStoredAsOneOrZero(): void
    {
        $menu = $this->menu();
        $this->repository->saveItem(Item::text('On')->withPlacement($menu, null, 0));
        $this->repository->saveItem(
            Item::text('Off')->withPresentation('Off', active: false)->withPlacement($menu, null, 1),
        );

        $active = [];
        foreach ($this->adapter->query(
            'SELECT active FROM navigation_item ORDER BY navigation_item_id',
            Adapter::QUERY_MODE_EXECUTE,
        ) as $row) {
            $active[] = (int) $row['active'];
        }

        static::assertSame([[1, 0], [true, false]], [
            $active,
            array_map(static fn(Item $item): bool => $item->active, $this->sorted($this->repository->items($menu))),
        ]);
    }

    #[Test]
    public function anItemsParentMustBeInTheSameMenu(): void
    {
        $other  = $this->menu('footer');
        $parent = $this->repository->saveItem(Item::text('Shop')->withPlacement($other, null, 0));

        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('An item\'s parent must be in the same menu');

        $this->repository->saveItem(Item::text('Sale')->withPlacement($this->menu(), $parent->id, 0));
    }

    #[Test]
    public function anItemThatIsNotThereIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('There is no menu item 9');

        $this->repository->item(9);
    }

    #[Test]
    public function anotherMenusHandleIsRefused(): void
    {
        $this->menu('header');

        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('Another menu already has the handle "header"');

        $this->repository->saveNavigation(new Navigation('header', 'Second header'));
    }

    #[Test]
    public function aRevisionIsFoundByItsIdAndKeptToItsMenu(): void
    {
        $header = $this->menu();
        $footer = $this->menu('footer');
        $this->repository->addRevision(new Revision($header, [], $this->clock->now(), 1, 'Header'));
        $second = $this->repository->addRevision(new Revision($footer, [], $this->clock->now(), 1, 'Footer'));

        static::assertSame(
            ['Footer', ['Header']],
            [
                $this->repository->revision((int) $second->id)->note,
                array_map(
                    static fn(Revision $revision): ?string => $revision->note,
                    $this->repository->revisions($header),
                ),
            ],
        );
    }

    #[Test]
    public function aRevisionKeepsItsTreeAsReadableJson(): void
    {
        $menu = $this->menu();
        $this->repository->addRevision(new Revision(
            $menu,
            [new Node(Item::url(Url::parse('/café/menu'), 'Café'))],
            $this->clock->now(),
            1,
        ));

        $items = '';
        foreach ($this->adapter->query('SELECT items FROM navigation_revision', Adapter::QUERY_MODE_EXECUTE) as $row) {
            $items = (string) $row['items'];
        }

        static::assertStringContainsString('"url":"/café/menu","fragment":null,"label":"Café"', $items);
    }

    #[Test]
    public function aRevisionThatCannotBeReadSaysWhich(): void
    {
        $menu = $this->menu();
        $this->adapter->query(
            "INSERT INTO navigation_revision (navigation_id, items, created, created_by) VALUES ({$menu}, '{\"broken', '2026-10-23 09:00:00', 1)",
            Adapter::QUERY_MODE_EXECUTE,
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Revision 1 could not be read');

        $this->repository->revision(1);
    }

    #[Test]
    public function aRevisionThatCannotBeWrittenAsJsonIsAStorageFailure(): void
    {
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Malformed UTF-8');

        $this->repository->addRevision(
            new Revision($this->menu(), [new Node(Item::text("\xB1\x31"))], $this->clock->now(), 1),
        );
    }

    #[Test]
    public function aRevisionThatIsNotThereIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('There is no menu revision 9');

        $this->repository->revision(9);
    }

    #[Test]
    public function aRevisionWhoseItemsAreNotAListSaysWhich(): void
    {
        $menu = $this->menu();
        $this->adapter->query(
            "INSERT INTO navigation_revision (navigation_id, items, created, created_by) VALUES ({$menu}, '\"x\"', '2026-10-23 09:00:00', 1)",
            Adapter::QUERY_MODE_EXECUTE,
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('Revision 1 could not be read: its items are not a list');

        $this->repository->revision(1);
    }

    #[Test]
    public function aRevisionWithAnItemOrAddressThatCannotBeReadSaysWhich(): void
    {
        $menu = $this->menu();
        $this->adapter->query(
            "INSERT INTO navigation_revision (navigation_id, items, created, created_by) VALUES ({$menu}, '[{\"label\":\"x\"}]', '2026-10-23 09:00:00', 1), ({$menu}, '[{\"kind\":\"url\",\"url\":\"data:x\",\"label\":\"x\"}]', '2026-10-23 09:00:00', 1)",
            Adapter::QUERY_MODE_EXECUTE,
        );

        $failures = [];
        foreach ([1, 2] as $id) {
            try {
                $this->repository->revision($id);
            } catch (StorageException $failure) {
                $failures[] = $failure->getMessage();
            }
        }

        static::assertSame(
            [
                'Revision 1 could not be read: A revision item has no kind',
                'Revision 2 could not be read: A menu can\'t link to a data: address',
            ],
            $failures,
        );
    }

    #[Test]
    public function aRowAnItemCannotHoldIsAStorageFailure(): void
    {
        $menu = $this->menu();
        $this->adapter->query(
            "INSERT INTO navigation_item (navigation_id, kind, display, created, updated) VALUES ({$menu}, 'text', 'label', '2026-10-23 09:00:00', '2026-10-23 09:00:00')",
            Adapter::QUERY_MODE_EXECUTE,
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('A text item has a label and no link');

        $this->repository->items($menu);
    }

    #[Test]
    public function aRowWithAnUnknownKindOrDisplayIsAStorageFailure(): void
    {
        $menu = $this->menu();
        $this->adapter->query(
            "INSERT INTO navigation_item (navigation_id, kind, label, display, created, updated) VALUES ({$menu}, 'text', 'Shop', 'sideways', '2026-10-23 09:00:00', '2026-10-23 09:00:00'), ({$menu}, 'widget', 'Shop', 'label', '2026-10-23 09:00:00', '2026-10-23 09:00:00')",
            Adapter::QUERY_MODE_EXECUTE,
        );

        $failures = [];
        foreach ([1, 2] as $id) {
            try {
                $this->repository->item($id);
            } catch (StorageException $failure) {
                $failures[] = $failure->getMessage();
            }
        }

        static::assertSame(
            ['The "display" column does not hold a display', 'The "kind" column does not hold an item kind'],
            $failures,
        );
    }

    #[Test]
    public function aStoredAddressAMenuCanNoLongerHoldIsAStorageFailure(): void
    {
        $menu = $this->menu();
        $this->adapter->query(
            "INSERT INTO navigation_item (navigation_id, kind, url, label, display, created, updated) VALUES ({$menu}, 'url', 'javascript:alert(1)', 'x', 'label', '2026-10-23 09:00:00', '2026-10-23 09:00:00')",
            Adapter::QUERY_MODE_EXECUTE,
        );

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('A menu can\'t link to a javascript: address');

        $this->repository->items($menu);
    }

    #[Test]
    public function changesToOneMenuLeaveTheOthers(): void
    {
        $header   = $this->menu();
        $footer   = $this->menu('footer');
        $revision = $this->repository->addRevision(new Revision($header, [], $this->clock->now(), 1));
        $this->clock->advance(60);

        $this->repository->saveNavigation(new Navigation('header', 'Renamed', id: $header));
        $this->repository->setLiveRevision($header, (int) $revision->id);
        $this->add($header, 'Shop');

        static::assertEquals(
            new Navigation('footer', 'Menu', id: $footer, updated: new DateTimeImmutable('2026-10-23 09:00:00')),
            $this->repository->navigation($footer),
        );
    }

    #[Test]
    public function changingAnItemKeepsWhenItWasCreated(): void
    {
        $menu    = $this->menu();
        $created = $this->clock->now();
        $item    = $this->repository->saveItem(Item::text('Shop')->withPlacement($menu, null, 0));
        $this->clock->advance(60);

        $saved = $this->repository->saveItem($item->withPresentation('Store'));

        static::assertEquals(
            ['Store', $created, $this->clock->now(), $this->clock->now()],
            [$saved->label, $saved->created, $saved->updated, $this->repository->navigation($menu)->updated],
        );
    }

    #[Test]
    public function changingAnItemThatIsNotThereFails(): void
    {
        $this->expectException(NotFoundException::class);

        $this->repository->saveItem(new Item(ItemKind::Text, label: 'Shop', id: 9, navigationId: $this->menu()));
    }

    #[Test]
    public function changingOneItemLeavesTheOthers(): void
    {
        $menu  = $this->menu();
        $first = $this->add($menu, 'First');
        $this->add($menu, 'Second', position: 1);

        $this->repository->saveItem($first->withPresentation('Renamed'));

        static::assertSame([['Renamed', 0, []], ['Second', 1, []]], $this->shape($menu));
    }

    #[Test]
    public function deletingAMenuDeletesItsItemsAndRevisions(): void
    {
        $menu = $this->menu();
        $this->repository->saveItem(Item::text('Shop')->withPlacement($menu, null, 0));
        $this->repository->addRevision(new Revision($menu, [], $this->clock->now(), 1));

        $this->repository->deleteNavigation($menu);

        static::assertSame([[], [], null], [
            $this->repository->items($menu),
            $this->repository->revisions($menu),
            $this->repository->navigationByHandle('header'),
        ]);
    }

    #[Test]
    public function deletingAMenuLeavesTheOthers(): void
    {
        $header = $this->menu();
        $footer = $this->menu('footer');
        $this->add($footer, 'Footer item');
        $this->repository->addRevision(new Revision($footer, [], $this->clock->now(), 1));

        $this->repository->deleteNavigation($header);

        static::assertSame(
            [1, 1, 'footer'],
            [
                count($this->repository->items($footer)),
                count($this->repository->revisions($footer)),
                $this->repository->navigation($footer)->handle,
            ],
        );
    }

    #[Test]
    public function deletingAnItemDeletesEverythingNestedUnderIt(): void
    {
        $menu   = $this->menu();
        $parent = $this->add($menu, 'Parent');
        $child  = $this->add($menu, 'Child', $parent->id);
        $this->add($menu, 'Grandchild', $child->id);
        $this->add($menu, 'Second child', $parent->id, 1);
        $this->add($menu, 'Kept', position: 1);

        $this->repository->deleteItem((int) $parent->id);

        static::assertSame([['Kept', 0, []]], $this->shape($menu));
    }

    #[Test]
    public function deletingAnItemDeletesWhatIsUnderItAndClosesTheGap(): void
    {
        $menu   = $this->menu();
        $first  = $this->add($menu, 'First');
        $second = $this->add($menu, 'Second', position: 1);
        $this->add($menu, 'Child', $second->id);
        $this->add($menu, 'Third', position: 2);
        $this->clock->advance(60);

        $this->repository->deleteItem((int) $second->id);

        static::assertEquals(
            [[['First', 0, []], ['Third', 1, []]], $this->clock->now()],
            [$this->shape($menu), $this->repository->navigation($menu)->updated],
        );
        static::assertSame(0, $first->sequence);
    }

    #[Test]
    public function keepsEveryKindOfItemAsItWasSaved(): void
    {
        $menu  = $this->menu();
        $items = [
            new Item(
                ItemKind::Record,
                record: new RecordTarget('news', 'launch'),
                fragment: 'gallery',
                label: 'Launch',
                display: Display::IconLabel,
                icon: 'star',
                target: Item::NEW_TAB,
                class: 'nav__news',
                active: false,
            ),
            Item::url(Url::parse('hello@example.com'), 'Email us'),
            Item::text('Hours', '<strong>Open</strong> daily'),
        ];

        $saved = [];
        foreach ($items as $position => $item) {
            $saved[] = $this->repository->saveItem($item->withPlacement($menu, null, $position));
        }

        static::assertEquals($saved, $this->sorted($this->repository->items($menu)));
    }

    #[Test]
    public function listsTheMenusBySequenceThenTitle(): void
    {
        $this->repository->saveNavigation(new Navigation('footer', 'Footer', sequence: 1));
        $this->repository->saveNavigation(new Navigation('social', 'Social', sequence: 0));
        $this->repository->saveNavigation(new Navigation('header', 'Header', sequence: 0));

        static::assertSame(
            ['header', 'social', 'footer'],
            array_map(static fn(Navigation $menu): string => $menu->handle, $this->repository->navigations()),
        );
    }

    #[Test]
    public function movingAnItemAlongItsSiblingsReordersThem(): void
    {
        $menu = $this->menu();
        $this->add($menu, 'First');
        $this->add($menu, 'Second', position: 1);
        $third = $this->add($menu, 'Third', position: 2);
        $this->clock->advance(60);

        $this->repository->moveItem((int) $third->id, null, 0);

        static::assertEquals(
            [[['Third', 0, []], ['First', 1, []], ['Second', 2, []]], $this->clock->now()],
            [$this->shape($menu), $this->repository->navigation($menu)->updated],
        );
    }

    #[Test]
    public function movingAnItemPastTheEndPutsItLast(): void
    {
        $menu  = $this->menu();
        $first = $this->add($menu, 'First');
        $this->add($menu, 'Second', position: 1);

        $this->repository->moveItem((int) $first->id, null, 9);

        static::assertSame([['Second', 0, []], ['First', 1, []]], $this->shape($menu));
    }

    #[Test]
    public function movingAnItemToABadPositionPutsItFirst(): void
    {
        $menu = $this->menu();
        $this->add($menu, 'First');
        $second = $this->add($menu, 'Second', position: 1);

        $this->repository->moveItem((int) $second->id, null, -3);

        static::assertSame([['Second', 0, []], ['First', 1, []]], $this->shape($menu));
    }

    #[Test]
    public function movingAnItemToANegativePositionPutsItFirst(): void
    {
        $menu = $this->menu();
        $this->add($menu, 'First');
        $this->add($menu, 'Second', position: 1);
        $third = $this->add($menu, 'Third', position: 2);

        $this->repository->moveItem((int) $third->id, null, -3);

        static::assertSame([['Third', 0, []], ['First', 1, []], ['Second', 2, []]], $this->shape($menu));
    }

    #[Test]
    public function movingAnItemUnderAnotherClosesItsGap(): void
    {
        $menu   = $this->menu();
        $first  = $this->add($menu, 'First');
        $second = $this->add($menu, 'Second', position: 1);
        $this->add($menu, 'Third', position: 2);
        $this->add($menu, 'Child', $first->id);

        $this->repository->moveItem((int) $second->id, $first->id, 0);

        static::assertSame(
            [['First', 0, [['Second', 0, []], ['Child', 1, []]]], ['Third', 1, []]],
            $this->shape($menu),
        );
    }

    #[Test]
    public function movingAnItemUnderItselfOrWhatIsUnderItIsRefused(): void
    {
        $menu   = $this->menu();
        $parent = $this->add($menu, 'Parent');
        $child  = $this->add($menu, 'Child', $parent->id);

        $refusals = [];
        foreach ([$parent->id, $child->id] as $under) {
            try {
                $this->repository->moveItem((int) $parent->id, $under, 0);
            } catch (InvalidItemException $refusal) {
                $refusals[] = $refusal->getMessage();
            }
        }

        static::assertSame(['An item can\'t be moved under itself', 'An item can\'t be moved under itself'], $refusals);
    }

    #[Test]
    public function movingAnItemUnderOneInAnotherMenuIsRefused(): void
    {
        $item  = $this->add($this->menu(), 'Shop');
        $other = $this->add($this->menu('footer'), 'Elsewhere');

        $this->expectException(InvalidItemException::class);
        $this->expectExceptionMessage('An item\'s parent must be in the same menu');

        $this->repository->moveItem((int) $item->id, $other->id, 0);
    }

    #[Test]
    public function movingAnItemWithinItsParentLeavesTheTopAlone(): void
    {
        $menu   = $this->menu();
        $parent = $this->add($menu, 'Parent');
        $this->add($menu, 'Top', position: 1);
        $first = $this->add($menu, 'One', $parent->id);
        $this->add($menu, 'Two', $parent->id, 1);

        $this->repository->moveItem((int) $first->id, $parent->id, 1);

        static::assertSame([['Parent', 0, [['Two', 0, []], ['One', 1, []]]], ['Top', 1, []]], $this->shape($menu));
    }

    #[Test]
    public function replacingTheItemsGivesANewIdWhereAnotherMenuHasOne(): void
    {
        $menu  = $this->menu();
        $taken = $this->add($this->menu('footer'), 'Footer item');
        $tree  = [new Node(new Item(ItemKind::Text, label: 'Parent', id: $taken->id), [new Node(Item::text('Child'))])];

        $this->repository->replaceItems($menu, $tree);

        static::assertSame(
            [false, [['Parent', 0, [['Child', 0, []]]]]],
            [$this->repository->items($menu)[0]->id === $taken->id, $this->shape($menu)],
        );
    }

    #[Test]
    public function replacingTheItemsKeepsTheirIds(): void
    {
        $menu  = $this->menu();
        $first = $this->add($menu, 'First');
        $child = $this->add($menu, 'Child', $first->id);
        $tree  = Tree::build($this->repository->items($menu));
        $this->repository->deleteItem((int) $first->id);
        $this->clock->advance(60);

        $this->repository->replaceItems($menu, $tree);

        static::assertEquals(
            [[(int) $first->id, (int) $child->id], [['First', 0, [['Child', 0, []]]]], $this->clock->now()],
            [
                array_map(
                    static fn(Item $item): int => (int) $item->id,
                    $this->sorted($this->repository->items($menu)),
                ),
                $this->shape($menu),
                $this->repository->navigation($menu)->updated,
            ],
        );
    }

    #[Test]
    public function revisionsAreNewestFirstByWhenTheyWerePublished(): void
    {
        $menu = $this->menu();
        $this->repository->addRevision(
            new Revision($menu, [], new DateTimeImmutable('2026-10-23 12:00:00'), 1, 'Later'),
        );
        $this->repository->addRevision(
            new Revision($menu, [], new DateTimeImmutable('2026-10-23 08:00:00'), 1, 'Earlier'),
        );

        static::assertSame(
            ['Later', 'Earlier'],
            array_map(static fn(Revision $revision): ?string => $revision->note, $this->repository->revisions($menu)),
        );
    }

    #[Test]
    public function revisionsKeepTheirTreeNoteAndPublisherNewestFirst(): void
    {
        $menu  = $this->menu();
        $item  = $this->add($menu, 'Shop');
        $nodes = Tree::build($this->repository->items($menu));
        $first = $this->repository->addRevision(new Revision($menu, $nodes, $this->clock->now(), 3, 'First'));
        $this->clock->advance(60);
        $second = $this->repository->addRevision(new Revision($menu, [], $this->clock->now(), 4));

        static::assertEquals(
            [
                [[$second->id, null, 4], [$first->id, 'First', 3]],
                [new Node(new Item(ItemKind::Text, label: 'Shop', id: $item->id, navigationId: $menu))],
            ],
            [
                array_map(
                    static fn(Revision $revision): array => [$revision->id, $revision->note, $revision->createdBy],
                    $this->repository->revisions($menu),
                ),
                $this->repository->revision((int) $first->id)->nodes,
            ],
        );
    }

    #[Test]
    public function savingAnItemSanitisesItsHtmlAndBumpsItsMenu(): void
    {
        $menu = $this->menu();
        $this->clock->advance(60);

        $item = $this->repository->saveItem(Item::text(
            'Hours',
            '<p>Open <script>x</script><em>daily</em></p>',
        )->withPlacement($menu, null, 0));

        static::assertEquals(
            ['Open <em>daily</em>', $this->clock->now()],
            [$item->html, $this->repository->navigation($menu)->updated],
        );
    }

    #[Test]
    public function savingAnItemsHtmlToNothingLeavesNone(): void
    {
        $item = $this->repository->saveItem(Item::text('Hours', '<script>x</script>')->withPlacement(
            $this->menu(),
            null,
            0,
        ));

        static::assertNull($item->html);
    }

    #[Test]
    public function theLiveRevisionIsTheOneSetForTheMenu(): void
    {
        $menu     = $this->menu();
        $revision = $this->repository->addRevision(new Revision($menu, [], $this->clock->now(), 1));

        $before = $this->repository->liveRevisionId('header');
        $this->repository->setLiveRevision($menu, (int) $revision->id);

        static::assertSame(
            [null, $revision->id, null, $revision->id],
            [
                $before,
                $this->repository->liveRevisionId('header'),
                $this->repository->liveRevisionId('footer'),
                $this->repository->navigation($menu)->revisionId,
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->clock      = new MutableClock('2026-10-23 09:00:00');
        $this->repository = new PhpDbNavigationRepository($this->adapter, $this->clock);
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
    }

    private function add(int $menu, string $label, ?int $parentId = null, int $position = 0): Item
    {
        return $this->repository->saveItem(Item::text($label)->withPlacement($menu, $parentId, $position));
    }

    private function menu(string $handle = 'header'): int
    {
        return (int) $this->repository->saveNavigation(new Navigation($handle, 'Menu'))->id;
    }

    /**
     * The menu's tree as each item's label, sequence and children.
     *
     * @return list<array{string|null, int, list<mixed>}>
     */
    private function shape(int $menu): array
    {
        $shape = static function (array $nodes) use (&$shape): array {
            return array_map(
                static fn(Node $node): array => [$node->item->label, $node->item->sequence, $shape($node->children)],
                $nodes,
            );
        };

        return $shape(Tree::build($this->repository->items($menu)));
    }

    /**
     * @param list<Item> $items
     *
     * @return list<Item>
     */
    private function sorted(array $items): array
    {
        usort($items, static fn(Item $a, Item $b): int => $a->id <=> $b->id);

        return $items;
    }
}
