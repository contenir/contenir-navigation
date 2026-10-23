<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\InvalidLinkException;
use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Menu\Display;
use Contenir\Navigation\Menu\Item;
use Contenir\Navigation\Menu\ItemKind;
use DateTimeImmutable;

/**
 * Menu items as navigation_item rows hold them, and back.
 *
 * @internal
 */
final class ItemRows
{
    /**
     * @throws StorageException When the row holds something an item can't.
     */
    public static function item(Row $row): Item
    {
        $section = $row->nullableString('record_section');
        $record  = $row->nullableString('record_id');
        $url     = $row->nullableString('url');

        try {
            return new Item(
                ItemKind::tryFrom($row->string('kind')) ?? throw StorageException::unreadable('kind', 'an item kind'),
                record: null === $section || null === $record ? null : new RecordTarget($section, $record),
                url: null === $url ? null : Url::parse($url),
                fragment: $row->nullableString('fragment'),
                label: $row->nullableString('label'),
                html: $row->nullableString('html'),
                display: Display::tryFrom($row->string('display')) ?? throw StorageException::unreadable(
                    'display',
                    'a display',
                ),
                icon: $row->nullableString('icon'),
                target: $row->nullableString('target'),
                class: $row->nullableString('class'),
                active: 0 !== $row->int('active'),
                id: $row->int('navigation_item_id'),
                navigationId: $row->int('navigation_id'),
                parentId: $row->nullableInt('parent_id'),
                sequence: $row->int('sequence'),
                created: $row->time('created'),
                updated: $row->time('updated'),
            );
        } catch (InvalidItemException|InvalidLinkException $failure) {
            throw StorageException::failed($failure);
        }
    }

    /**
     * The row's columns for $item, saved at $now; a new item is created then
     * too.
     *
     * @return array<string, scalar|null>
     */
    public static function values(Item $item, int $navigationId, DateTimeImmutable $now): array
    {
        return [
            'navigation_id'  => $navigationId,
            'parent_id'      => $item->parentId,
            'sequence'       => $item->sequence,
            'kind'           => $item->kind->value,
            'record_section' => $item->record?->section,
            'record_id'      => $item->record?->id,
            'url'            => $item->url?->href,
            'fragment'       => $item->fragment,
            'label'          => $item->label,
            'html'           => $item->html,
            'display'        => $item->display->value,
            'icon'           => $item->icon,
            'target'         => $item->target,
            'class'          => $item->class,
            'active'         => $item->active ? 1 : 0,
            'created'        => DbTime::format($item->created ?? $now),
            'updated'        => DbTime::format($now),
        ];
    }
}
