<?php

declare(strict_types=1);

namespace Contenir\Navigation\Menu;

use Contenir\Navigation\Exception\InvalidItemException;
use Contenir\Navigation\Link\RecordTarget;
use Contenir\Navigation\Link\Url;
use DateTimeImmutable;

use function get_object_vars;
use function trim;

/**
 * One element of a menu: a link to a record, a link to a URL, or text, with
 * its place in the menu and how it is drawn.
 *
 * A record item may add a fragment to its record's URL and override the
 * record's title with a label. A URL item and a text item need a label; a
 * text item may also hold HTML, which is sanitised before it is saved. An
 * item drawn with an icon names an icon token. Target is null, or _blank to
 * open in a new tab; class holds extra classes for the site's markup; an
 * inactive item is hidden without being deleted.
 *
 * @api
 */
final readonly class Item
{
    public const int LONGEST_TEXT = ItemRules::LONGEST_TEXT;

    public const string NEW_TAB = '_blank';

    public ?string $label;

    public ?string $fragment;

    public ?string $class;

    public ?string $html;

    /**
     * @throws InvalidItemException When the item breaks one of its rules.
     *
     * @mago-expect lint:excessive-parameter-list One parameter per column of a navigation_item row.
     */
    public function __construct(
        public ItemKind $kind,
        public ?RecordTarget $record = null,
        public ?Url $url = null,
        ?string $fragment = null,
        ?string $label = null,
        ?string $html = null,
        public Display $display = Display::Label,
        public ?string $icon = null,
        public ?string $target = null,
        ?string $class = null,
        public bool $active = true,
        public ?int $id = null,
        public ?int $navigationId = null,
        public ?int $parentId = null,
        public int $sequence = 0,
        public ?DateTimeImmutable $created = null,
        public ?DateTimeImmutable $updated = null,
    ) {
        $this->label    = ItemRules::text('A label', $label);
        $this->fragment = ItemRules::fragment($fragment);
        $this->class    = ItemRules::classes($class);
        $this->html     = null === $html || '' === trim($html) ? null : $html;

        ItemLink::check($this);
        ItemRules::drawing($display, $icon, $target);
    }

    /**
     * A link to a record, drawn with the record's title unless $label
     * overrides it.
     *
     * @throws InvalidItemException
     */
    public static function record(RecordTarget $record, ?string $fragment = null, ?string $label = null): self
    {
        return new self(ItemKind::Record, record: $record, fragment: $fragment, label: $label);
    }

    /**
     * Text with no link, which may hold sanitised HTML.
     *
     * @throws InvalidItemException
     */
    public static function text(string $label, ?string $html = null): self
    {
        return new self(ItemKind::Text, label: $label, html: $html);
    }

    /**
     * A link to an address, opening in a new tab when it leads to another
     * site.
     *
     * @throws InvalidItemException
     */
    public static function url(Url $url, string $label): self
    {
        return new self(ItemKind::Url, url: $url, label: $label, target: $url->suggestsNewTab() ? self::NEW_TAB : null);
    }

    /**
     * @throws InvalidItemException
     */
    public function withHtml(?string $html): self
    {
        return $this->with(['html' => $html]);
    }

    /**
     * The same item linking to $link instead, keeping its label, how it is
     * drawn and its place. A fragment is kept only with a record.
     *
     * @throws InvalidItemException
     */
    public function withLinkTo(RecordTarget|Url $link, ?string $fragment = null): self
    {
        $record = $link instanceof RecordTarget;

        return $this->with([
            'kind'     => $record ? ItemKind::Record : ItemKind::Url,
            'record'   => $record ? $link : null,
            'url'      => $record ? null : $link,
            'fragment' => $record ? $fragment : null,
            'html'     => null,
        ]);
    }

    /**
     * @throws InvalidItemException
     */
    public function withPlacement(int $navigationId, ?int $parentId, int $sequence): self
    {
        return $this->with(['navigationId' => $navigationId, 'parentId' => $parentId, 'sequence' => $sequence]);
    }

    /**
     * The same item drawn another way, as its edit panel sets it.
     *
     * @throws InvalidItemException
     *
     * @mago-expect lint:excessive-parameter-list One parameter per field of the item's edit panel.
     */
    public function withPresentation(
        ?string $label,
        Display $display = Display::Label,
        ?string $icon = null,
        ?string $target = null,
        ?string $class = null,
        bool $active = true,
    ): self {
        return $this->with([
            'label'   => $label,
            'display' => $display,
            'icon'    => $icon,
            'target'  => $target,
            'class'   => $class,
            'active'  => $active,
        ]);
    }

    /**
     * @throws InvalidItemException
     */
    public function withTimes(DateTimeImmutable $created, DateTimeImmutable $updated): self
    {
        return $this->with(['created' => $created, 'updated' => $updated]);
    }

    /**
     * A copy with $changes made, checked again.
     *
     * @param array<string, mixed> $changes By constructor parameter
     *
     * @throws InvalidItemException
     *
     * @mago-expect analysis:mixed-argument The changes are checked by the constructor's types.
     */
    private function with(array $changes): self
    {
        return new self(...[...get_object_vars($this), ...$changes]);
    }
}
