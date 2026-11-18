<?php
declare(strict_types=1);

namespace App\Test\TestCase\Migrations;

use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Migrations\Migrations;
use Override;

/**
 * The consents made a yes or a no, run for real on the test database.
 */
class NewCustomersReceiveWhatTheConsentGivesWithoutATickTest extends TestCase
{
    /**
     * The version of the migration.
     */
    private const VERSION = 20261118120000;

    /**
     * A customer of the fixtures.
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
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
        $this->migrations->migrate();

        parent::tearDown();
    }

    /**
     * Nothing on file becomes what the paper consent says holds where nothing is ticked.
     *
     * @return void
     */
    public function testNothingOnFileBecomesWhatThePaperSays(): void
    {
        $this->rollBack();
        $this->connection->execute(
            'UPDATE customers SET agree_gdpr = NULL, agree_mailing_billing = NULL, agree_mailing_outages = NULL,'
            . ' agree_mailing_commercial = NULL WHERE id = ?',
            [self::CUSTOMER_ID],
        );

        $this->assertTrue($this->migrations->migrate());

        $this->assertSame([
            'agree_gdpr' => false,
            'agree_mailing_billing' => true,
            'agree_mailing_outages' => true,
            'agree_mailing_commercial' => false,
        ], $this->consents());
    }

    /**
     * A yes or a no on file stays, whichever way it goes against the defaults.
     *
     * @return void
     */
    public function testAnAnswerOnFileStays(): void
    {
        $this->rollBack();
        $this->connection->execute(
            'UPDATE customers SET agree_gdpr = true, agree_mailing_billing = false, agree_mailing_outages = false,'
            . ' agree_mailing_commercial = true WHERE id = ?',
            [self::CUSTOMER_ID],
        );

        $this->migrations->migrate();

        $this->assertSame([
            'agree_gdpr' => true,
            'agree_mailing_billing' => false,
            'agree_mailing_outages' => false,
            'agree_mailing_commercial' => true,
        ], $this->consents());
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
     * The consents of the customer as the database holds them.
     *
     * @return array<string, bool|null>
     */
    private function consents(): array
    {
        $row = $this->connection
            ->execute(
                'SELECT agree_gdpr, agree_mailing_billing, agree_mailing_outages, agree_mailing_commercial'
                . ' FROM customers WHERE id = ?',
                [self::CUSTOMER_ID],
            )
            ->fetch('assoc');
        $this->assertIsArray($row);

        return array_map(fn(mixed $value): ?bool => $value === null ? null : (bool)$value, $row);
    }
}
