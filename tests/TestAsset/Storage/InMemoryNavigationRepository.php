<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\TestAsset\Storage;

use Contenir\Navigation\Exception\NotFoundException;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Menu\Revision;
use Contenir\Navigation\Menu\Tree;
use Contenir\Navigation\Storage\NavigationRepositoryInterface;
use LogicException;
use Override;

use function array_filter;
use function array_reverse;
use function array_values;
use function count;

/**
 * Menus held in memory, for tests of what uses the repository. Only what
 * those tests need is kept: menus, their items and their revisions.
 */
final class InMemoryNavigationRepository implements NavigationRepositoryInterface
{
    /**
     * @var array<int, Navigation>
     */
    public array $navigations = [];

    /**
     * @var array<int, list<Item>> By menu
     */
    public array $items = [];

    /**
     * @var array<int, Revision>
     */
    public array $revisions = [];

    #[Override]
    public function addRevision(Revision $revision): Revision
    {
        $saved                             = $revision->withId(count($this->revisions) + 1);
        $this->revisions[(int) $saved->id] = $saved;

        return $saved;
    }

    #[Override]
    public function deleteItem(int $id): void
    {
        throw new LogicException('Not needed by these tests');
    }

    #[Override]
    public function deleteNavigation(int $id): void
    {
        throw new LogicException('Not needed by these tests');
    }

    #[Override]
    public function item(int $id): Item
    {
        throw new LogicException('Not needed by these tests');
    }

    #[Override]
    public function items(int $navigationId): array
    {
        return $this->items[$navigationId] ?? [];
    }

    #[Override]
    public function liveRevisionId(string $handle): ?int
    {
        return $this->navigationByHandle($handle)?->revisionId;
    }

    #[Override]
    public function moveItem(int $id, ?int $parentId, int $position): void
    {
        throw new LogicException('Not needed by these tests');
    }

    #[Override]
    public function navigation(int $id): Navigation
    {
        return $this->navigations[$id] ?? throw NotFoundException::navigation($id);
    }

    #[Override]
    public function navigationByHandle(string $handle): ?Navigation
    {
        foreach ($this->navigations as $navigation) {
            if ($handle === $navigation->handle) {
                return $navigation;
            }
        }

        return null;
    }

    #[Override]
    public function navigations(): array
    {
        return array_values($this->navigations);
    }

    #[Override]
    public function replaceItems(int $navigationId, array $nodes): void
    {
        $this->items[$navigationId] = Tree::flatten($nodes, $navigationId);
    }

    #[Override]
    public function revision(int $id): Revision
    {
        return $this->revisions[$id] ?? throw NotFoundException::revision($id);
    }

    #[Override]
    public function revisions(int $navigationId): array
    {
        return array_reverse(array_values(array_filter(
            $this->revisions,
            static fn(Revision $revision): bool => $navigationId === $revision->navigationId,
        )));
    }

    #[Override]
    public function saveItem(Item $item): Item
    {
        throw new LogicException('Not needed by these tests');
    }

    #[Override]
    public function saveNavigation(Navigation $navigation): Navigation
    {
        $saved                               = $navigation->withId($navigation->id ?? (count($this->navigations) + 1));
        $this->navigations[(int) $saved->id] = $saved;

        return $saved;
    }

    #[Override]
    public function setLiveRevision(int $navigationId, int $revisionId): void
    {
        $this->navigations[$navigationId] = $this->navigation($navigationId)->withRevision($revisionId);
    }
}
