<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Unit\Link;

use Contenir\Navigation\Exception\InvalidLinkException;
use Contenir\Navigation\Link\Url;
use Contenir\Navigation\Link\UrlKind;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_repeat;

#[Group('unit')]
final class UrlTest extends TestCase
{
    /**
     * @return array<string, array{string, string, UrlKind, string}>
     */
    public static function linkProvider(): array
    {
        return [
            'a path'                        => [
                '/about/our-team?tab=2#staff',
                '/about/our-team?tab=2#staff',
                UrlKind::Path,
                '/about/our-team?tab=2#staff',
            ],
            'the home page'                 => ['/', '/', UrlKind::Path, '/'],
            'a path with spaces around'     => ['  /contact  ', '/contact', UrlKind::Path, '/contact'],
            'a full link'                   => [
                'https://shop.example.com',
                'https://shop.example.com',
                UrlKind::External,
                'https://shop.example.com',
            ],
            'a plain http link'             => [
                'http://example.com/a',
                'http://example.com/a',
                UrlKind::External,
                'http://example.com/a',
            ],
            'a scheme in capitals'          => [
                'HTTPS://example.com',
                'HTTPS://example.com',
                UrlKind::External,
                'HTTPS://example.com',
            ],
            'a host without a scheme'       => [
                'shop.example.com',
                'https://shop.example.com',
                UrlKind::External,
                'https://shop.example.com',
            ],
            'a host with a path'            => [
                'example.com/shop?item=2',
                'https://example.com/shop?item=2',
                UrlKind::External,
                'https://example.com/shop?item=2',
            ],
            'a host with a port'            => [
                'example.com:8080/shop',
                'https://example.com:8080/shop',
                UrlKind::External,
                'https://example.com:8080/shop',
            ],
            'a protocol-relative link'      => [
                '//cdn.example.com/file.pdf',
                'https://cdn.example.com/file.pdf',
                UrlKind::External,
                'https://cdn.example.com/file.pdf',
            ],
            'a fragment'                    => ['#booking', '#booking', UrlKind::Fragment, '#booking'],
            'an email address'              => [
                'hello@example.com',
                'mailto:hello@example.com',
                UrlKind::Email,
                'hello@example.com',
            ],
            'a mailto link'                 => [
                'mailto:hello@example.com',
                'mailto:hello@example.com',
                UrlKind::Email,
                'hello@example.com',
            ],
            'a mailto link in capitals'     => [
                'MAILTO:hello@example.com',
                'mailto:hello@example.com',
                UrlKind::Email,
                'hello@example.com',
            ],
            'a local phone number'          => ['03 5555 1234', 'tel:0355551234', UrlKind::Phone, '0355551234'],
            'an international phone number' => [
                '+61 (3) 5555-1234',
                'tel:+61355551234',
                UrlKind::Phone,
                '+61355551234',
            ],
            'a dotted phone number'         => ['03.5555.1234', 'tel:0355551234', UrlKind::Phone, '0355551234'],
            'a tel link'                    => [
                'tel:+61 3 5555 1234',
                'tel:+61355551234',
                UrlKind::Phone,
                '+61355551234',
            ],
            'a tel link with a space'       => [
                'tel: +61 3 5555 1234',
                'tel:+61355551234',
                UrlKind::Phone,
                '+61355551234',
            ],
            'a short phone number'          => ['131 450', 'tel:131450', UrlKind::Phone, '131450'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function refusedProvider(): array
    {
        return [
            'nothing'                     => ['   ', 'A link needs an address'],
            'javascript'                  => ['javascript:alert(1)', 'A menu can\'t link to a javascript: address'],
            'javascript in capitals'      => ['JavaScript:alert(1)', 'A menu can\'t link to a javascript: address'],
            'data'                        => ['data:text/html,<b>hi</b>', 'A menu can\'t link to a data: address'],
            'a file'                      => ['file:///etc/passwd', 'A menu can\'t link to a file: address'],
            'a word'                      => ['about', '"about" isn\'t a link'],
            'a fragment alone'            => ['#', '"#" isn\'t a link'],
            'a fragment with a space'     => ['#our team', '"#our team" isn\'t a link'],
            'a path with a space'         => ['/our team', '"/our team" isn\'t a link'],
            'a full link with a space'    => ['https://exa mple.com', '"https://exa mple.com" isn\'t a link'],
            'a mailto without an address' => ['mailto:hello', '"mailto:hello" isn\'t a link'],
            'a tel without a number'      => ['tel:call-us', '"tel:call-us" isn\'t a link'],
            'too few digits'              => ['555 12', '"555 12" isn\'t a link'],
            'a tel with too few digits'   => ['tel:12345', '"tel:12345" isn\'t a link'],
            'a host without a domain'     => ['localhost:8080', 'A menu can\'t link to a localhost: address'],
        ];
    }

    #[Test]
    public function aLinkAsLongAsItsColumnIsKept(): void
    {
        $path = '/' . str_repeat('a', times: Url::LONGEST - 1);

        static::assertSame($path, Url::parse($path)->href);
    }

    #[Test]
    public function aLinkTooLongForItsColumnIsRefused(): void
    {
        $this->expectException(InvalidLinkException::class);
        $this->expectExceptionMessage('"/' . str_repeat('a', times: 39) . '…" isn\'t a link');

        Url::parse('/' . str_repeat('a', times: Url::LONGEST));
    }

    #[Test]
    #[DataProvider('linkProvider')]
    public function classifiesWhatAnEditorTyped(string $input, string $href, UrlKind $kind, string $display): void
    {
        $url = Url::parse($input);

        static::assertSame([$href, $kind, $display], [$url->href, $url->kind, $url->display()]);
    }

    #[Test]
    #[DataProvider('linkProvider')]
    public function readsBackWhatItHolds(string $input): void
    {
        $url = Url::parse($input);

        static::assertEquals($url, Url::parse($url->href));
    }

    #[Test]
    #[DataProvider('refusedProvider')]
    public function refusesWhatIsNotALinkAMenuCanHold(string $input, string $message): void
    {
        $this->expectException(InvalidLinkException::class);
        $this->expectExceptionMessage($message);

        Url::parse($input);
    }

    #[Test]
    public function suggestsANewTabOnlyForAnotherSite(): void
    {
        static::assertSame(
            [true, false, false, false, false],
            [
                Url::parse('https://example.com')->suggestsNewTab(),
                Url::parse('/about')->suggestsNewTab(),
                Url::parse('#booking')->suggestsNewTab(),
                Url::parse('hello@example.com')->suggestsNewTab(),
                Url::parse('03 5555 1234')->suggestsNewTab(),
            ],
        );
    }
}
