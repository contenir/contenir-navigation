<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Html;

use Contenir\Navigation\Html\HtmlSanitiser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function libxml_get_errors;
use function libxml_use_internal_errors;

#[Group('unit')]
final class HtmlSanitiserTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function htmlProvider(): array
    {
        return [
            'plain text'                         => ['Opening hours', 'Opening hours'],
            'the inline set'                     => [
                '<strong>Open</strong> <em>daily</em> <b>9</b>–<i>5</i><br><span class="note">Closed Mondays</span>',
                '<strong>Open</strong> <em>daily</em> <b>9</b>–<i>5</i><br><span class="note">Closed Mondays</span>',
            ],
            'an element outside the set'         => ['<p>Shop <u>now</u></p>', 'Shop now'],
            'a nested element outside the set'   => [
                '<div><p><strong>Shop</strong></p></div>',
                '<strong>Shop</strong>',
            ],
            'a script'                           => ['Shop<script>alert(1)</script> now', 'Shop now'],
            'a style'                            => ['<style>b{color:red}</style>Shop', 'Shop'],
            'an iframe'                          => ['<iframe src="https://example.com"></iframe>Shop', 'Shop'],
            'an svg'                             => ['<svg><script>alert(1)</script></svg>Shop', 'Shop'],
            'a script in capitals'               => ['<SCRIPT>alert(1)</SCRIPT>Shop', 'Shop'],
            'a script inside an allowed element' => [
                '<strong>Shop<script>alert(1)</script></strong>',
                '<strong>Shop</strong>',
            ],
            'a comment'                          => ['Shop<!-- hidden -->', 'Shop'],
            'a comment before more to clean'     => ['<!-- hidden --><u>Shop</u><script>x</script>', 'Shop'],
            'an event handler'                   => [
                '<strong onclick="alert(1)">Shop</strong>',
                '<strong>Shop</strong>',
            ],
            'a style attribute'                  => [
                '<span class="x" style="color:red">Shop</span>',
                '<span class="x">Shop</span>',
            ],
            'an attribute in capitals'           => ['<span CLASS="x">Shop</span>', '<span class="x">Shop</span>'],
            'a link'                             => [
                '<a href="/shop" title="Our shop">Shop</a>',
                '<a href="/shop" title="Our shop">Shop</a>',
            ],
            'a link in a new tab'                => [
                '<a href="https://example.com" target="_blank">Shop</a>',
                '<a href="https://example.com" target="_blank" rel="noopener noreferrer">Shop</a>',
            ],
            'a link to another frame'            => [
                '<a href="/shop" target="_top">Shop</a>',
                '<a href="/shop">Shop</a>',
            ],
            'a javascript link'                  => ['<a href="javascript:alert(1)">Shop</a>', '<a>Shop</a>'],
            'an email link'                      => [
                '<a href="hello@example.com">Email</a>',
                '<a href="mailto:hello@example.com">Email</a>',
            ],
            'a phone link'                       => [
                '<a href="03 5555 1234">Call</a>',
                '<a href="tel:0355551234">Call</a>',
            ],
            'a link that sets its own rel'       => [
                '<a href="/shop" rel="opener">Shop</a>',
                '<a href="/shop">Shop</a>',
            ],
            'accented text'                      => ['<em>Café</em> & bar', '<em>Café</em> &amp; bar'],
            'whitespace around'                  => ["  <em>Shop</em>\n", '<em>Shop</em>'],
            'nothing but tags'                   => ['<p> </p><script>x</script>', ''],
            'nothing'                            => ["  \n ", ''],
        ];
    }

    #[Test]
    public function keepsOnlyTheElementsAndAttributesItIsGiven(): void
    {
        $sanitiser = new HtmlSanitiser(['p' => ['class'], 'a' => ['href']]);

        static::assertSame(
            '<p class="lead">Shop <a href="/shop">now</a></p>',
            $sanitiser->clean('<p class="lead" id="x"><strong>Shop</strong> <a href="/shop" title="t">now</a></p>'),
        );
    }

    #[Test]
    #[DataProvider('htmlProvider')]
    public function keepsOnlyTheInlineSet(string $html, string $clean): void
    {
        static::assertSame($clean, (new HtmlSanitiser())->clean($html));
    }

    #[Test]
    public function leavesLibxmlAsItFoundIt(): void
    {
        $before = libxml_use_internal_errors(false);
        (new HtmlSanitiser())->clean('<b>unclosed <x');
        $during = libxml_use_internal_errors(true);
        (new HtmlSanitiser())->clean('<b>unclosed <x');
        $errors = libxml_get_errors();
        libxml_use_internal_errors($before);

        static::assertSame([false, []], [$during, $errors]);
    }
}
