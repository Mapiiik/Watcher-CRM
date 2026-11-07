<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Migrations;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Migrations\Migrations;
use Override;

/**
 * The migration of the recovery codes, run for real on the test database.
 *
 * The suite runs in UTC, where reading a time in the wrong zone changes nothing, so the zone of
 * the instance is set to another one and put back.
 */
class RecoveryCodesKeepTheirTimeZoneTest extends TestCase
{
    /**
     * The version of the migration.
     */
    private const VERSION = 20261107140000;

    /**
     * The zone of the instance, as the application is told in `APP_DEFAULT_TIMEZONE`.
     */
    private const ZONE = 'Europe/Prague';

    /**
     * A recovery code of this test.
     */
    private const CODE_ID = 'f1e2d3c4-b5a6-4978-8877-665544332211';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = ['app.Users'];

    /**
     * The zone as the test found it.
     */
    private string $zone = 'UTC';

    private Migrations $migrations;

    private Connection $connection;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->zone = date_default_timezone_get();
        date_default_timezone_set(self::ZONE);

        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('test');
        $this->connection = $connection;
        $this->migrations = new Migrations(['connection' => 'test']);
    }

    /**
     * Whatever happened, the database goes back to what the rest of the suite expects.
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->connection->execute('DELETE FROM recovery_codes WHERE id = ?', [self::CODE_ID]);
        $this->migrations->migrate();
        date_default_timezone_set($this->zone);

        parent::tearDown();
    }

    /**
     * A time written before the change reads the same in the zone of the instance after it, in
     * winter and in summer, which are an hour apart.
     *
     * @return void
     */
    public function testATimeWrittenBeforeReadsTheSameAfter(): void
    {
        $this->rollBack();
        $this->assertSame('timestamp without time zone', $this->typeOf('used'));
        $this->assertSame('timestamp without time zone', $this->typeOf('created'));
        $this->aCode(used: '2026-07-01 12:00:00', created: '2026-01-15 08:30:00');

        $this->migrations->migrate();

        $this->assertSame('timestamp with time zone', $this->typeOf('used'));
        $this->assertSame('timestamp with time zone', $this->typeOf('created'));
        $this->assertSame(
            ['2026-07-01 12:00:00', '2026-01-15 08:30:00'],
            $this->readIn(self::ZONE),
        );
        $this->assertSame(
            ['2026-07-01 10:00:00', '2026-01-15 07:30:00'],
            $this->readIn('UTC'),
            'The instant is the local one, not the same digits in UTC.',
        );
    }

    /**
     * A column that has its zone already says which moment it is, and reading it again from the
     * zone of the instance would move it. Some databases are like that from the start.
     *
     * @return void
     */
    public function testAColumnWithItsZoneIsLeftAlone(): void
    {
        $this->rollBack();
        foreach (['used', 'created'] as $column) {
            $this->connection->execute(sprintf(
                "ALTER TABLE recovery_codes ALTER COLUMN %s TYPE timestamptz USING %s AT TIME ZONE 'UTC'",
                $column,
                $column,
            ));
        }
        $this->aCode(used: '2026-07-01 12:00:00+00', created: '2026-01-15 08:30:00+00');

        $this->migrations->migrate();

        $this->assertSame(['2026-07-01 12:00:00', '2026-01-15 08:30:00'], $this->readIn('UTC'));
    }

    /**
     * Going back gives the application the local time it wrote before.
     *
     * @return void
     */
    public function testGoingBackKeepsTheLocalTime(): void
    {
        $this->aCode(used: '2026-07-01 10:00:00+00', created: '2026-01-15 07:30:00+00');

        $this->rollBack();

        $this->assertSame('timestamp without time zone', $this->typeOf('used'));
        $row = $this->connection
            ->execute('SELECT used::text AS used, created::text AS created FROM recovery_codes WHERE id = ?', [self::CODE_ID])
            ->fetch('assoc');
        $this->assertIsArray($row);
        $this->assertSame('2026-07-01 12:00:00', $row['used']);
        $this->assertSame('2026-01-15 08:30:00', $row['created']);
    }

    /**
     * Back to what stood before the migration and whatever came after it.
     *
     * @return void
     */
    private function rollBack(): void
    {
        $versions = array_map(fn(array $row): int => (int)$row['id'], $this->migrations->status());
        sort($versions);
        $position = array_search(self::VERSION, $versions, true);
        $this->assertIsInt($position);

        $this->migrations->rollback(['target' => $versions[$position - 1]]);
    }

    /**
     * A used recovery code with the two times as they would be stored.
     *
     * @param string $used When it was used.
     * @param string $created When it was made.
     * @return void
     */
    private function aCode(string $used, string $created): void
    {
        $user = $this->connection->execute('SELECT id FROM users LIMIT 1')->fetch('assoc');
        $this->assertIsArray($user);

        $this->connection->execute(
            'INSERT INTO recovery_codes (id, user_id, code_hash, used, created) VALUES (?, ?, ?, ?, ?)',
            [self::CODE_ID, $user['id'], 'a hash', $used, $created],
        );
    }

    /**
     * The two times as they read in a zone.
     *
     * @param string $zone The zone.
     * @return list<string>
     */
    private function readIn(string $zone): array
    {
        $row = $this->connection
            ->execute(
                'SELECT (used AT TIME ZONE ?)::text AS used, (created AT TIME ZONE ?)::text AS created'
                . ' FROM recovery_codes WHERE id = ?',
                [$zone, $zone, self::CODE_ID],
            )
            ->fetch('assoc');
        $this->assertIsArray($row);

        return [$row['used'], $row['created']];
    }

    /**
     * What the database says the column is.
     *
     * @param string $column The column of the recovery codes.
     * @return string
     */
    private function typeOf(string $column): string
    {
        $row = $this->connection
            ->execute(
                "SELECT data_type FROM information_schema.columns
                WHERE table_schema = current_schema() AND table_name = 'recovery_codes' AND column_name = ?",
                [$column],
            )
            ->fetch('assoc');
        $this->assertIsArray($row);

        return (string)$row['data_type'];
    }
}
