<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Http\Session;

use Access\Http\Session\DatabaseSession;
use Cake\Database\Connection;
use Cake\Database\Exception\QueryException;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use Override;
use PDOException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Two requests of a session without a row both insert it, and neither of them fails.
 */
#[CoversClass(DatabaseSession::class)]
class DatabaseSessionTest extends TestCase
{
    private const ID = 'access-database-session-test';

    /**
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->deleteRow();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->deleteRow();
        $this->getTableLocator()->remove('Sessions');

        parent::tearDown();
    }

    /**
     * A session with no row gets one.
     *
     * @return void
     */
    public function testASessionWithoutARowGetsOne(): void
    {
        $handler = new DatabaseSession(['model' => 'Sessions']);

        $this->assertTrue($handler->write(self::ID, 'first'));
        $this->assertSame('first', $handler->read(self::ID));
    }

    /**
     * The request whose insert comes second writes over the row of the first.
     *
     * @return void
     */
    public function testTheRequestThatInsertsSecondWritesOverTheRowOfTheFirst(): void
    {
        $this->getTableLocator()->set('Sessions', new class (['alias' => 'Sessions', 'table' => 'sessions']) extends Table {
            public bool $raced = false;

            /**
             * The other request inserts the row between this one looking for it and inserting it.
             *
             * @param \Cake\Datasource\EntityInterface $entity The row.
             * @param array<string, mixed> $options How to save it.
             * @return \Cake\Datasource\EntityInterface|false
             */
            #[Override]
            public function save(EntityInterface $entity, array $options = []): EntityInterface|false
            {
                if ($this->raced) {
                    return parent::save($entity, $options);
                }

                $this->raced = true;
                $this->getConnection()->insertQuery('sessions', [
                    'id' => $entity->get('id'),
                    'data' => 'the other request',
                    'expires' => time() + 3600,
                ])->execute();

                return parent::save($entity, ['checkExisting' => false] + $options);
            }
        });

        $handler = new DatabaseSession(['model' => 'Sessions']);

        $this->assertTrue($handler->write(self::ID, 'this request'));
        $this->assertSame('this request', $handler->read(self::ID));
        $this->assertSame(1, $this->getTableLocator()->get('Sessions')->find()->where(['id' => self::ID])->count());
    }

    /**
     * Any other failure of the database is not taken for the race.
     *
     * @return void
     */
    public function testAnotherFailureIsNotTakenForTheRace(): void
    {
        $this->getTableLocator()->set('Sessions', new class (['alias' => 'Sessions', 'table' => 'sessions']) extends Table {
            /**
             * A column the row must have is missing.
             *
             * @param \Cake\Datasource\EntityInterface $entity The row.
             * @param array<string, mixed> $options How to save it.
             * @return \Cake\Datasource\EntityInterface|false
             */
            #[Override]
            public function save(EntityInterface $entity, array $options = []): EntityInterface|false
            {
                throw new QueryException('INSERT INTO sessions', new PDOException('not null violation', 23502));
            }
        });

        $handler = new DatabaseSession(['model' => 'Sessions']);

        $this->expectException(QueryException::class);
        $handler->write(self::ID, 'this request');
    }

    /**
     * Takes away the row of the session the tests write.
     *
     * @return void
     */
    private function deleteRow(): void
    {
        $connection = ConnectionManager::get('test');
        assert($connection instanceof Connection);
        $connection->deleteQuery('sessions')->where(['id' => self::ID])->execute();
    }
}
