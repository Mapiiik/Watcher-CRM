<?php
declare(strict_types=1);

namespace Radius\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;
use Override;
use Radius\Model\Enum\AccountType;

/**
 * ConnectionHistoryAccountsFixture
 *
 * A readable account for the connection history source, the shared accounts
 * fixture carries filler text where a username belongs.
 */
class ConnectionHistoryAccountsFixture extends TestFixture
{
    /**
     * RADIUS keeps its own database, and so does the test suite.
     */
    public string $connection = 'test_radius';

    /**
     * Table alias, which is where the table name comes from
     */
    public string $tableAlias = 'Radius.Accounts';

    /**
     * Init method
     *
     * @return void
     */
    #[Override]
    public function init(): void
    {
        $this->records = [
            [
                'id' => 'ab8f2c14-6d3e-4b91-9f0a-7c25d8e41b63',
                'username' => 'tester',
                'password' => 'secret',
                'type' => AccountType::PPPoE,
                'active' => 1,
                'customer_id' => '403bab0e-52cd-4a8e-83f8-43c2457d0481',
                'contract_id' => '7f76dc3f-a11b-4109-958b-4b0382545a66',
                'created' => '2025-06-01 08:00:00',
                'created_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
                'modified' => '2026-07-20 10:00:00',
                'modified_by' => '11edb519-be76-4d66-aea0-34188d31eae1',
            ],
        ];
        parent::init();
    }
}
