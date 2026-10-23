<?php

declare(strict_types=1);

namespace Contenir\Navigation\Link;

use Contenir\Navigation\Exception\InvalidLinkException;

use function filter_var;
use function in_array;
use function preg_match;
use function preg_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;
use function trim;

use const FILTER_VALIDATE_EMAIL;
use const FILTER_VALIDATE_URL;

/**
 * An address a menu item links to, classified from what an editor typed.
 * An email address or a phone number is typed as it is, with no prefix, and
 * held as a mailto: or tel: link, the number with everything but its digits
 * and a leading + stripped; display() shows it without the prefix again. A
 * host name typed without a scheme, such as shop.example.com, is held as an
 * https: link. Links that run code or reach the visitor's files, such as
 * javascript:, data: or file:, are refused, as is anything else that has no
 * clear meaning.
 *
 * @api
 */
final readonly class Url
{
    /**
     * The longest address a URL item's column holds.
     */
    public const int LONGEST = 2083;

    private const string SCHEME = '/^([a-z][a-z0-9+.-]*):/i';

    private const string HOST = '/^[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}(:\d+)?([\/?#]\S*)?$/iD';

    private const string PHONE = '/^\+?[0-9 ().-]+$/D';

    private const string NO_SPACE = '/^\S+$/D';

    /**
     * The fewest digits a phone number has.
     */
    private const int SHORTEST_PHONE = 6;

    private function __construct(
        public string $href,
        public UrlKind $kind,
    ) {}

    /**
     * Reads an address as an editor typed it.
     *
     * @throws InvalidLinkException When it is empty, refused or can't be read.
     */
    public static function parse(string $input): self
    {
        $value = trim($input);
        if ('' === $value) {
            throw InvalidLinkException::empty();
        }

        if (strlen($value) > self::LONGEST) {
            throw InvalidLinkException::unreadable(substr($value, offset: 0, length: 40) . '…');
        }

        $matches = [];
        if (1 === preg_match(self::SCHEME, $value, $matches) && ! self::isHostAndPort($value)) {
            return self::withScheme($value, strtolower($matches[1] ?? ''));
        }

        return match (true) {
            str_starts_with($value, '#') => self::fragment($value),
            str_starts_with($value, '/') => self::path($value),
            false !== filter_var($value, FILTER_VALIDATE_EMAIL) => new self("mailto:{$value}", UrlKind::Email),
            1 === preg_match(self::PHONE, $value) => self::phone($value, $value),
            1 === preg_match(self::HOST, $value) => self::external("https://{$value}", $value),
            default => throw InvalidLinkException::unreadable($value),
        };
    }

    /**
     * @throws InvalidLinkException
     */
    private static function external(string $href, string $input): self
    {
        if (false === filter_var($href, FILTER_VALIDATE_URL)) {
            throw InvalidLinkException::unreadable($input);
        }

        return new self($href, UrlKind::External);
    }

    /**
     * @throws InvalidLinkException
     */
    private static function fragment(string $value): self
    {
        if ('#' === $value || 1 !== preg_match(self::NO_SPACE, $value)) {
            throw InvalidLinkException::unreadable($value);
        }

        return new self($value, UrlKind::Fragment);
    }

    /**
     * Whether a value such as localhost:8080 is a host and port rather than
     * a scheme.
     */
    private static function isHostAndPort(string $value): bool
    {
        return 1 === preg_match(self::HOST, $value);
    }

    /**
     * @throws InvalidLinkException
     */
    private static function path(string $value): self
    {
        if (str_starts_with($value, '//')) {
            return self::external("https:{$value}", $value);
        }

        if (1 !== preg_match(self::NO_SPACE, $value)) {
            throw InvalidLinkException::unreadable($value);
        }

        return new self($value, UrlKind::Path);
    }

    /**
     * @throws InvalidLinkException
     */
    private static function phone(string $number, string $input): self
    {
        $digits = (string) preg_replace('/\D/', replacement: '', subject: $number);
        if (strlen($digits) < self::SHORTEST_PHONE) {
            throw InvalidLinkException::unreadable($input);
        }

        $plus = str_starts_with($number, '+') ? '+' : '';

        return new self("tel:{$plus}{$digits}", UrlKind::Phone);
    }

    /**
     * @throws InvalidLinkException
     */
    private static function withScheme(string $value, string $scheme): self
    {
        $rest = trim(substr($value, strlen($scheme) + 1));

        return match (true) {
            in_array($scheme, ['http', 'https'], strict: true) => self::external($value, $value),
            'mailto' === $scheme && false !== filter_var($rest, FILTER_VALIDATE_EMAIL) => new self(
                "mailto:{$rest}",
                UrlKind::Email,
            ),
            'tel' === $scheme && 1 === preg_match(self::PHONE, $rest) => self::phone($rest, $value),
            in_array($scheme, ['mailto', 'tel'], strict: true) => throw InvalidLinkException::unreadable($value),
            default => throw InvalidLinkException::refusedScheme($scheme),
        };
    }

    /**
     * What the CMS shows the editor: the address as it is held, without the
     * mailto: or tel: an email address or phone number is held with.
     */
    public function display(): string
    {
        return match ($this->kind) {
            UrlKind::Email => substr($this->href, strlen('mailto:')),
            UrlKind::Phone => substr($this->href, strlen('tel:')),
            default        => $this->href,
        };
    }

    /**
     * Whether a link opening this address should suggest a new tab: one to
     * another site.
     */
    public function suggestsNewTab(): bool
    {
        return UrlKind::External === $this->kind;
    }
}
