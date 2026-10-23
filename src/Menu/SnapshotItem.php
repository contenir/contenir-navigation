<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Exception\InvalidLinkException;
use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;

use function is_bool;
use function is_int;
use function is_string;

/**
 * Reads one item back from a snapshot's fields.
 *
 * @internal
 */
final class SnapshotItem
{
    /**
     * @param array<array-key, mixed> $values
     *
     * @throws InvalidItemException
     * @throws InvalidLinkException
     */
    public static function read(array $values, int $navigationId, ?int $parentId, int $position): Item
    {
        $section = self::string($values, 'record_section');
        $record  = self::string($values, 'record_id');
        $url     = self::string($values, 'url');
        $kind    = ItemKind::tryFrom(self::string($values, 'kind') ?? '');

        return new Item(
            $kind ?? throw InvalidItemException::because('A revision item has no kind'),
            record: null === $section || null === $record ? null : new RecordTarget($section, $record),
            url: null === $url ? null : Url::parse($url),
            fragment: self::string($values, 'fragment'),
            label: self::string($values, 'label'),
            html: self::string($values, 'html'),
            display: Display::tryFrom(self::string($values, 'display') ?? '') ?? Display::Label,
            icon: self::string($values, 'icon'),
            target: self::string($values, 'target'),
            class: self::string($values, 'class'),
            active: ! is_bool($values['active'] ?? null) || $values['active'],
            id: is_int($values['id'] ?? null) ? $values['id'] : null,
            navigationId: $navigationId,
            parentId: $parentId,
            sequence: $position,
        );
    }

    /**
     * @param array<array-key, mixed> $values
     */
    private static function string(array $values, string $key): ?string
    {
        return is_string($values[$key] ?? null) ? $values[$key] : null;
    }
}
