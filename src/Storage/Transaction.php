<?php

declare(strict_types=1);

namespace Contenir\Navigation\Storage;

use Contenir\Navigation\Exception\StorageException;
use Override;
use PDO;
use PDOException;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Exception\ExceptionInterface;
use Throwable;

/**
 * A transaction on a php-db adapter. It joins one already open on the same
 * connection, whether php-db opened it or something else sharing its PDO
 * did, such as the CMS saving a record, so a menu item and the record it
 * links to are saved together or not at all. Only the outermost commits or
 * rolls back, since rolling back undoes the whole transaction.
 *
 * @api
 */
final readonly class Transaction implements TransactionInterface
{
    public function __construct(
        private AdapterInterface $adapter,
    ) {}

    /**
     * @mago-expect analysis:unhandled-thrown-type It rethrows what $work throws, which the caller declares.
     */
    #[Override]
    public function run(callable $work): mixed
    {
        $connection = $this->adapter->getDriver()->getConnection();
        $resource   = $connection->getResource();
        if ($connection->inTransaction() || $resource instanceof PDO && $resource->inTransaction()) {
            return $work();
        }

        try {
            $connection->beginTransaction();
        } catch (ExceptionInterface|PDOException $failure) {
            throw StorageException::failed($failure);
        }

        try {
            $result = $work();
        } catch (Throwable $failure) {
            $connection->rollback();

            throw $failure;
        }

        try {
            $connection->commit();
        } catch (ExceptionInterface|PDOException $failure) {
            throw StorageException::failed($failure);
        }

        return $result;
    }
}
