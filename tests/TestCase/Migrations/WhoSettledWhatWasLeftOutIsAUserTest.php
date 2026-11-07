<?php
declare(strict_types=1);

namespace App\Test\TestCase\Migrations;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Migrations\Migrations;
use Override;

/**
 * The key on who settled what was left out, run for real on the test database.
 */
class WhoSettledWhatWasLeftOutIsAUserTest extends TestCase
{
    /**
     * The version of the migration.
     */
    private const VERSION = 20261107130000;

    /**
     * The papers of the fixtures.
     */
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

    /**
     * An account nobody has.
     */
    private const GONE = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.CustomerProposals',
        'app.ContractProposals',
    ];

    private Migrations $migrations;

    private Connection $connection;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

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
        $this->connection->execute('UPDATE contract_proposals SET left_out_settled_by = NULL');
        $this->migrations->migrate();

        parent::tearDown();
    }

    /**
     * A value naming an account that is gone would stop the key from being added, so it goes.
     *
     * @return void
     */
    public function testAnAccountThatIsGoneIsTakenOff(): void
    {
        $this->rollBack();
        $this->settledBy(self::GONE);

        $this->assertTrue($this->migrations->migrate());

        $this->assertNull($this->settler());
    }

    /**
     * An account that is there stays named.
     *
     * @return void
     */
    public function testAnAccountThatIsThereStays(): void
    {
        $user = $this->aUser();
        $this->rollBack();
        $this->settledBy($user);

        $this->migrations->migrate();

        $this->assertSame($user, $this->settler());
    }

    /**
     * An account that goes afterwards is taken off with it, and nothing stands in its way.
     *
     * @return void
     */
    public function testAnAccountGoingLaterClearsTheName(): void
    {
        $user = $this->aUser();
        $this->settledBy($user);

        $this->connection->execute('DELETE FROM users WHERE id = ?', [$user]);

        $this->assertNull($this->settler());
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
     * A new account with nothing behind it.
     *
     * @return string
     */
    private function aUser(): string
    {
        $id = 'bbbbbbbb-cccc-4ddd-8eee-ffffffffffff';
        $this->connection->execute(
            "INSERT INTO users (id, nid, username, password, role, active, is_superuser, created, modified)
            VALUES (?, 9000, 'somebody-settling', 'a hash', 'user', true, false, now(), now())",
            [$id],
        );

        return $id;
    }

    /**
     * Says who saw to what was left out.
     *
     * @param string $user The account.
     * @return void
     */
    private function settledBy(string $user): void
    {
        $this->connection->execute(
            'UPDATE contract_proposals SET left_out_settled_by = ? WHERE id = ?',
            [$user, self::PROPOSAL_ID],
        );
    }

    /**
     * Who the papers say saw to it.
     *
     * @return string|null
     */
    private function settler(): ?string
    {
        $row = $this->connection
            ->execute('SELECT left_out_settled_by FROM contract_proposals WHERE id = ?', [self::PROPOSAL_ID])
            ->fetch('assoc');
        $this->assertIsArray($row);

        return $row['left_out_settled_by'];
    }
}
