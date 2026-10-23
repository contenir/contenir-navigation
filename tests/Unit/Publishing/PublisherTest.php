<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Publishing;

use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Node;
use Contenir\Navigation\Menu\PublishedBy;
use Contenir\Navigation\Menu\Revision;
use Contenir\Navigation\Publishing\LiveNavigation;
use Contenir\Navigation\Publishing\Publisher;
use Contenir\Navigation\Tests\TestAsset\Clock\MutableClock;
use Contenir\Navigation\Tests\TestAsset\Factory\ItemFactory;
use Contenir\Navigation\Tests\TestAsset\Storage\InMemoryNavigationRepository;
use Contenir\Navigation\Tests\TestAsset\Storage\RecordingTransaction;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class PublisherTest extends TestCase
{
    private InMemoryNavigationRepository $repository;

    private RecordingTransaction $transaction;

    private MutableClock $clock;

    private Publisher $publisher;

    private PublishedBy $ann;

    #[Test]
    public function discardingAMenuNeverPublishedChangesNothing(): void
    {
        static::assertEquals(
            [false, [ItemFactory::text(1, 'Shop')]],
            [$this->publisher->discard(1), $this->repository->items(1)],
        );
    }

    #[Test]
    public function discardingReturnsTheDraftToTheLiveRevision(): void
    {
        $this->publisher->publish(1, $this->ann);
        $this->repository->items[1] = [ItemFactory::text(1, 'Renamed'), ItemFactory::text(2, 'Added')];

        static::assertSame(
            [true, 0],
            [$this->publisher->discard(1), $this->publisher->unpublishedChanges(1)],
        );
    }

    #[Test]
    public function eachChangeRunsInOneTransaction(): void
    {
        $revision = $this->publisher->publish(1, $this->ann);
        $this->publisher->discard(1);
        $this->publisher->restore((int) $revision->id);

        static::assertSame(3, $this->transaction->runs);
    }

    #[Test]
    public function liveIsNothingForAMenuNeverPublishedOrNotThere(): void
    {
        static::assertSame([null, null], [$this->publisher->live('header'), $this->publisher->live('footer')]);
    }

    #[Test]
    public function liveIsTheMenuWithItsLiveRevision(): void
    {
        $revision = $this->publisher->publish(1, $this->ann);

        static::assertEquals(
            new LiveNavigation($this->repository->navigation(1), $revision),
            $this->publisher->live('header'),
        );
    }

    #[Test]
    public function publishingAMenuThatIsNotThereFails(): void
    {
        $this->expectException(NotFoundException::class);

        $this->publisher->publish(9, $this->ann);
    }

    #[Test]
    public function publishingMakesTheDraftTreeLiveAsANewRevision(): void
    {
        $this->clock->set('2026-10-23 10:30:00');

        $revision = $this->publisher->publish(1, $this->ann, 'Added Shop');

        static::assertEquals(
            [
                new Revision(
                    1,
                    [new Node(ItemFactory::text(1, 'Shop'))],
                    $this->clock->now(),
                    $this->ann,
                    'Added Shop',
                    1,
                ),
                1,
            ],
            [$revision, $this->repository->navigation(1)->revisionId],
        );
    }

    #[Test]
    public function restoringCopiesARevisionIntoTheDraft(): void
    {
        $first                      = $this->publisher->publish(1, $this->ann);
        $this->repository->items[1] = [ItemFactory::text(5, 'Later')];
        $this->publisher->publish(1, $this->ann);

        $this->publisher->restore((int) $first->id);

        static::assertEquals([ItemFactory::text(1, 'Shop')], $this->repository->items(1));
    }

    #[Test]
    public function theDraftOfAMenuNeverPublishedIsAllChanges(): void
    {
        $this->repository->items[1] = [ItemFactory::text(1, 'Shop'), ItemFactory::text(2, 'News')];

        static::assertSame(2, $this->publisher->unpublishedChanges(1));
    }

    #[Test]
    public function unpublishedChangesCountsTheDraftAgainstTheLiveRevision(): void
    {
        $this->publisher->publish(1, $this->ann);
        $this->repository->items[1] = [ItemFactory::text(1, 'Renamed'), ItemFactory::text(2, 'Added')];

        static::assertSame(2, $this->publisher->unpublishedChanges(1));
    }

    #[Override]
    protected function setUp(): void
    {
        $this->repository  = new InMemoryNavigationRepository();
        $this->transaction = new RecordingTransaction();
        $this->clock       = new MutableClock('2026-10-23 09:00:00');
        $this->ann         = new PublishedBy(7, 'Ann Editor');
        $this->publisher   = new Publisher($this->repository, $this->transaction, $this->clock);
        $this->repository->saveNavigation(new Navigation('header', 'Header'));
        $this->repository->items[1] = [ItemFactory::text(1, 'Shop')];
    }
}
