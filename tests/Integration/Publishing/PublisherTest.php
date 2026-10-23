<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Integration\Publishing;

use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Menu\PublishedBy;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Storage\PhpDbNavigationRepository;
use Contenir\Navigation\Storage\Transaction;
use Contenir\Navigation\Tests\TestAsset\Clock\MutableClock;
use Contenir\Navigation\Tests\Trait\TestDatabaseTrait;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function usort;

/**
 * Publishing against the real tables: what the site reads is the published
 * tree, whatever happens to the draft afterwards.
 */
#[Group('integration')]
final class PublisherTest extends TestCase
{
    use TestDatabaseTrait;

    private PhpDbNavigationRepository $repository;

    private Publisher $publisher;

    private int $menu;

    #[Test]
    public function discardingBringsBackTheItemsAsTheyWerePublished(): void
    {
        $this->publisher->publish($this->menu, new PublishedBy(1, 'Ann Editor'));
        $shop = $this->repository->items($this->menu)[0];
        $this->repository->saveItem($shop->withPresentation('Renamed'));
        $this->repository->saveItem(Item::text('Added')->withPlacement($this->menu, null, 1));

        $this->publisher->discard($this->menu);

        static::assertSame(['Shop', 'About'], $this->labels());
    }

    #[Test]
    public function publishingAMenuThatIsNotThereWritesNoRevision(): void
    {
        $failure = null;
        try {
            $this->publisher->publish(99);
        } catch (NotFoundException $caught) {
            $failure = $caught->getMessage();
        }

        static::assertSame(['There is no menu 99', []], [$failure, $this->repository->revisions(99)]);
    }

    #[Test]
    public function theSiteReadsThePublishedTreeNotTheDraft(): void
    {
        $this->publisher->publish($this->menu, new PublishedBy(1, 'Ann Editor'), 'First menu');
        $this->repository->saveItem(Item::text('Draft only')->withPlacement($this->menu, null, 5));

        $live  = $this->publisher->live('header');
        $nodes = $live?->nodes() ?? [];

        static::assertSame(
            [['Shop'], ['About'], 'First menu', 1],
            [
                array_map(static fn(Node $node): ?string => $node->item->label, $nodes),
                array_map(static fn(Node $node): ?string => $node->item->label, $nodes[0]->children ?? []),
                $live?->revision->note,
                $this->publisher->unpublishedChanges($this->menu),
            ],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpDatabase();
        $clock            = new MutableClock('2026-10-23 09:00:00');
        $this->repository = new PhpDbNavigationRepository($this->adapter, $clock);
        $this->publisher  = new Publisher($this->repository, new Transaction($this->adapter), $clock);
        $this->menu       = (int) $this->repository->saveNavigation(new Navigation('header', 'Header'))->id;
        $shop             = $this->repository->saveItem(Item::text('Shop')->withPlacement($this->menu, null, 0));
        $this->repository->saveItem(Item::record(new RecordTarget('page', 2), label: 'About')->withPlacement(
            $this->menu,
            $shop->id,
            0,
        ));
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
    }

    /**
     * Every item's label, in id order.
     *
     * @return list<string|null>
     */
    private function labels(): array
    {
        $items = $this->repository->items($this->menu);
        usort($items, static fn(Item $a, Item $b): int => $a->id <=> $b->id);

        return array_map(static fn(Item $item): ?string => $item->label, $items);
    }
}
