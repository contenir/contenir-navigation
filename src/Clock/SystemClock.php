<?php

declare(strict_types=1);

namespace Contenir\Navigation\Clock;

use DateTimeImmutable;
use DateTimeZone;
use Override;
use Psr\Clock\ClockInterface;

/**
 * The time now, in UTC, for a container that has no clock of its own.
 *
 * @api
 */
final readonly class SystemClock implements ClockInterface
{
    /**
     * @mago-expect analysis:unhandled-thrown-type "UTC" is always a valid time zone.
     */
    #[Override]
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
