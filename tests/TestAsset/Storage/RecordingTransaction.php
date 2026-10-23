<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\TestAsset\Storage;

use Contenir\Navigation\Storage\TransactionInterface;
use Override;

/**
 * Runs work at once, counting how many times it was asked to.
 */
final class RecordingTransaction implements TransactionInterface
{
    public int $runs = 0;

    #[Override]
    public function run(callable $work): mixed
    {
        $this->runs++;

        return $work();
    }
}
