<?php

declare(strict_types=1);

namespace Contenir\Navigation\Tests\Integration\Storage;

use Contenir\Navigation\Exception\StorageException;
use Contenir\Navigation\Menu\Navigation;
use Contenir\Navigation\Storage\PhpDbNavigationRepository;
use Contenir\Navigation\Storage\Transaction;
use Contenir\Navigation\Tests\TestAsset\Clock\MutableClock;
use Contenir\Navigation\Tests\Trait\TestDatabaseTrait;
use Override;
use PDO;
use PDOException;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Adapter\Exception\RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException as WorkFailure;
use Throwable;

#[Group('integration')]
final class TransactionTest extends TestCase
{
    use TestDatabaseTrait;

    private PhpDbNavigationRepository $repository;

    /**
     * @return array<string, array{string, Throwable}>
     */
    public static function failureProvider(): array
    {
        return [
            'PDO failing to begin'     => ['beginTransaction', new PDOException('gone away')],
            'php-db failing to begin'  => ['beginTransaction', new RuntimeException('gone away')],
            'PDO failing to commit'    => ['commit', new PDOException('gone away')],
            'php-db failing to commit' => ['commit', new RuntimeException('gone away')],
        ];
    }

    #[Test]
    #[DataProvider('failureProvider')]
    public function aTransactionThatCannotBeginOrCommitIsAStorageFailure(string $step, Throwable $failure): void
    {
        $connection = $this->createStub(ConnectionInterface::class);
        $connection->method($step)->willThrowException($failure);

        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('The navigation tables could not be read or written: gone away');

        (new Transaction($this->over($connection)))->run(static fn(): int => 1);
    }

    #[Test]
    public function commitsWhatTheWorkWroteAndReturnsItsResult(): void
    {
        $result = (new Transaction($this->adapter))->run(function (): string {
            $this->repository->saveNavigation(new Navigation('header', 'Header'));

            return 'done';
        });

        static::assertSame(['done', true, false], [$result, $this->exists('header'), $this->open()]);
    }

    #[Test]
    public function joinsATransactionSomethingElseOpenedOnTheSamePdo(): void
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        (new Transaction($this->adapter))->run(function (): void {
            $this->repository->saveNavigation(new Navigation('header', 'Header'));
        });
        $joined = $pdo->inTransaction();
        $pdo->rollBack();

        static::assertSame([true, false], [$joined, $this->exists('header')]);
    }

    #[Test]
    public function nestsInsideAnotherOfItsOwn(): void
    {
        $transaction = new Transaction($this->adapter);

        $transaction->run(function () use ($transaction): void {
            $transaction->run(function (): void {
                $this->repository->saveNavigation(new Navigation('header', 'Header'));
            });
        });

        static::assertSame([true, false], [$this->exists('header'), $this->open()]);
    }

    #[Test]
    public function rollsBackAndRethrowsWhenTheWorkFails(): void
    {
        $failure = null;
        try {
            (new Transaction($this->adapter))->run(function (): void {
                $this->repository->saveNavigation(new Navigation('header', 'Header'));

                throw new WorkFailure('The work failed');
            });
        } catch (WorkFailure $caught) {
            $failure = $caught->getMessage();
        }

        static::assertSame(['The work failed', false, false], [$failure, $this->exists('header'), $this->open()]);
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->repository = new PhpDbNavigationRepository($this->adapter, new MutableClock());
    }

    #[Override]
    protected function tearDown(): void
    {
        if ($this->connected && $this->pdo()->inTransaction()) {
            $this->pdo()->rollBack();
        }

        $this->tearDownDatabase();
    }

    private function exists(string $handle): bool
    {
        return null !== $this->repository->navigationByHandle($handle);
    }

    private function open(): bool
    {
        return $this->pdo()->inTransaction();
    }

    /**
     * An adapter over $connection alone.
     */
    private function over(ConnectionInterface $connection): AdapterInterface
    {
        $driver = $this->createStub(DriverInterface::class);
        $driver->method('getConnection')->willReturn($connection);
        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getDriver')->willReturn($driver);

        return $adapter;
    }

    private function pdo(): PDO
    {
        $pdo = $this->adapter->getDriver()->getConnection()->getResource();
        static::assertInstanceOf(PDO::class, $pdo);

        return $pdo;
    }
}
