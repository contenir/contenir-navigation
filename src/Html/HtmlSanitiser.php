<?php

declare(strict_types=1);

namespace Contenir\Navigation\Html;

use DOMDocument;

use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function sprintf;
use function trim;

use const LIBXML_NONET;

/**
 * Keeps a text item's HTML to a small inline set: by default strong, em, b,
 * i, span with a class, br, and a with an href, title and target. Any other
 * element is unwrapped, keeping its text, except those whose content is
 * code or embedded documents, such as script, style and iframe, which go
 * with everything in them. Comments and attributes not allowed are dropped.
 * A link's href must be one a menu item could hold, and is kept as
 * Link\Url holds it, so hello@example.com becomes a mailto: link; a link
 * opening a new tab is given rel="noopener noreferrer".
 *
 * @api
 */
final readonly class HtmlSanitiser
{
    /**
     * Allowed elements, each with the attributes it may keep.
     *
     * @var array<string, list<string>>
     */
    public const array INLINE = [
        'a'      => ['href', 'title', 'target'],
        'b'      => [],
        'br'     => [],
        'em'     => [],
        'i'      => [],
        'span'   => ['class'],
        'strong' => [],
    ];

    private const string WRAPPER = 'contenir-sanitise';

    private Cleaner $cleaner;

    /**
     * @param array<string, list<string>> $allowed Allowed elements, each with the attributes it may keep
     */
    public function __construct(array $allowed = self::INLINE)
    {
        $this->cleaner = new Cleaner($allowed);
    }

    /**
     * $html with only the allowed elements and attributes left, or an empty
     * string when nothing but whitespace is.
     *
     * @mago-expect analysis:possibly-false-operand saveHTML() fails only for a node outside its document, which these never are.
     */
    public function clean(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $errors   = libxml_use_internal_errors(true);
        $document->loadHTML(sprintf('<?xml encoding="UTF-8"?><%1$s>%2$s</%1$s>', self::WRAPPER, $html), LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($errors);

        $wrapper = $document->getElementsByTagName(self::WRAPPER)->item(0);
        if (null === $wrapper) {
            return '';
        }

        $this->cleaner->clean($wrapper);

        $clean = '';
        foreach (Nodes::children($wrapper) as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }
}
