<?php

declare(strict_types=1);

namespace Contenir\Navigation\Html;

use DOMElement;
use DOMNode;
use DOMText;

use function array_key_exists;
use function in_array;

/**
 * Walks parsed HTML, keeping text and the allowed elements, unwrapping other
 * elements and dropping comments and the elements whose content is code or
 * an embedded document.
 *
 * @internal
 */
final readonly class Cleaner
{
    /**
     * Elements dropped with everything inside them.
     */
    private const array DROPPED = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'template',
        'noscript',
        'svg',
        'math',
    ];

    /**
     * @param array<string, list<string>> $allowed Allowed elements, each with the attributes it may keep
     */
    public function __construct(
        private array $allowed,
    ) {}

    public function clean(DOMNode $parent): void
    {
        foreach (Nodes::children($parent) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement || in_array($child->tagName, self::DROPPED, strict: true)) {
                $parent->removeChild($child);
                continue;
            }

            $this->element($parent, $child);
        }
    }

    private function element(DOMNode $parent, DOMElement $element): void
    {
        $this->clean($element);

        $tag = $element->tagName;
        if (array_key_exists($tag, $this->allowed)) {
            Attributes::clean($element, $this->allowed[$tag]);

            return;
        }

        foreach (Nodes::children($element) as $child) {
            $parent->insertBefore($child, $element);
        }

        $parent->removeChild($element);
    }
}
