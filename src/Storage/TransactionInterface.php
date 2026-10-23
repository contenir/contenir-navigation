<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\StorageException;

/**
 * Runs work in one transaction on the site's database.
 *
 * @api
 */
interface TransactionInterface
{
    /**
     * Runs $work inside a transaction, committing when it returns and rolling
     * back when it throws, rethrowing what $work threw. When a transaction is
     * already open on the connection, $work joins it, and whoever opened it
     * commits.
     *
     * @template T
     *
     * @param callable(): T $work
     *
     * @return T
     *
     * @throws StorageException When the transaction can't be opened or committed.
     */
    public function run(callable $work): mixed;
}
