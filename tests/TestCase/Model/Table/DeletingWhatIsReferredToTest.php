<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Enum\FirstSeenSource;
use App\Model\Enum\HistoricalConnectionSource;
use App\Model\Table\ContractsTable;
use App\Model\Table\ContractVersionsTable;
use App\Model\Table\CustomersTable;
use App\Model\Table\ServicesTable;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * A record that others point at is refused with an answer. The foreign keys would refuse it too,
 * but by raising, which reaches the operator as an error page.
 */
#[CoversClass(ContractsTable::class)]
#[CoversClass(ContractVersionsTable::class)]
#[CoversClass(CustomersTable::class)]
#[CoversClass(ServicesTable::class)]
class DeletingWhatIsReferredToTest extends TestCase
{
    /**
     * A customer of the fixtures with nothing of theirs in the tables loaded here.
     */
    private const FREE_CUSTOMER_ID = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    /**
     * A contract of the fixtures with nothing of its own in the tables loaded here.
     */
    private const FREE_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

    /**
     * A service of the fixtures with nothing of its own in the tables loaded here.
     */
    private const FREE_SERVICE_ID = '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2';

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
        'app.ConnectionProfiles',
        'app.Services',
        'app.CustomerMessages',
        'app.CustomerProposals',
        'app.ContractProposals',
        'app.ServiceOverrides',
        'app.HistoricalConnections',
        'app.Labels',
        'app.CustomerLabels',
        'plugin.Bookkeeping.Invoices',
        'plugin.Files.DocumentationTypes',
        'plugin.Files.Documentations',
    ];

    /**
     * The fixtures name that customer as the technician of every contract, which is a case of its
     * own further down.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->fetchTable('Contracts')->updateAll(
            ['installation_technician_id' => null, 'uninstallation_technician_id' => null],
            ['1 = 1'],
        );
    }

    /**
     * Without this nothing here says the rules are what stood in the way.
     *
     * @return void
     */
    public function testWhatNothingPointsAtGoes(): void
    {
        $customers = $this->fetchTable('Customers');
        $contracts = $this->fetchTable('Contracts');
        $services = $this->fetchTable('Services');

        $this->assertTrue($contracts->delete($contracts->get(self::FREE_CONTRACT_ID)));
        $this->assertTrue($customers->delete($customers->get(self::FREE_CUSTOMER_ID)));
        $this->assertTrue($services->delete($services->get(self::FREE_SERVICE_ID)));
    }

    /**
     * @param string $table What points at the customer.
     * @return void
     * @link \App\Model\Table\CustomersTable::buildRules()
     */
    #[TestWith(['CustomerMessages'])]
    #[TestWith(['CustomerProposals'])]
    #[TestWith(['Bookkeeping.Invoices'])]
    public function testACustomerPointedAtStays(string $table): void
    {
        $this->fetchTable($table)->updateAll(['customer_id' => self::FREE_CUSTOMER_ID], ['1 = 1']);

        $this->assertRefused('Customers', self::FREE_CUSTOMER_ID);
    }

    /**
     * Who did the work on a contract is a customer as well, marked as a dealer.
     *
     * @param string $column Which of the two technicians they are.
     * @return void
     * @link \App\Model\Table\CustomersTable::buildRules()
     */
    #[TestWith(['installation_technician_id'])]
    #[TestWith(['uninstallation_technician_id'])]
    public function testATechnicianOfAContractStays(string $column): void
    {
        $this->fetchTable('Contracts')->updateAll([$column => self::FREE_CUSTOMER_ID], ['1 = 1']);

        $this->assertRefused('Customers', self::FREE_CUSTOMER_ID);
    }

    /**
     * @return void
     * @link \App\Model\Table\CustomersTable::buildRules()
     */
    public function testACustomerWithAFolderStays(): void
    {
        $this->aFolder(['customer_id' => self::FREE_CUSTOMER_ID]);

        $this->assertRefused('Customers', self::FREE_CUSTOMER_ID);
    }

    /**
     * @return void
     * @link \App\Model\Table\CustomersTable::buildRules()
     */
    public function testACustomerWithAHistoryStays(): void
    {
        $this->aHistoricalConnection(['customer_id' => self::FREE_CUSTOMER_ID]);

        $this->assertRefused('Customers', self::FREE_CUSTOMER_ID);
    }

    /**
     * @param string $table What points at the contract.
     * @return void
     * @link \App\Model\Table\ContractsTable::buildRules()
     */
    #[TestWith(['ContractProposals'])]
    #[TestWith(['ServiceOverrides'])]
    #[TestWith(['CustomerLabels'])]
    public function testAContractPointedAtStays(string $table): void
    {
        $this->fetchTable($table)->updateAll(['contract_id' => self::FREE_CONTRACT_ID], ['1 = 1']);

        $this->assertRefused('Contracts', self::FREE_CONTRACT_ID);
    }

    /**
     * @return void
     * @link \App\Model\Table\ContractsTable::buildRules()
     */
    public function testAContractWithAFolderStays(): void
    {
        $this->aFolder(['contract_id' => self::FREE_CONTRACT_ID]);

        $this->assertRefused('Contracts', self::FREE_CONTRACT_ID);
    }

    /**
     * @return void
     * @link \App\Model\Table\ContractsTable::buildRules()
     */
    public function testAContractWithAHistoryStays(): void
    {
        $this->aHistoricalConnection(['contract_id' => self::FREE_CONTRACT_ID]);

        $this->assertRefused('Contracts', self::FREE_CONTRACT_ID);
    }

    /**
     * @return void
     * @link \App\Model\Table\ServicesTable::buildRules()
     */
    public function testAServiceWithAnOverrideStays(): void
    {
        $this->fetchTable('ServiceOverrides')->updateAll(['service_id' => self::FREE_SERVICE_ID], ['1 = 1']);

        $this->assertRefused('Services', self::FREE_SERVICE_ID);
    }

    /**
     * Papers that end a version point at it from a column of their own.
     *
     * @return void
     * @link \App\Model\Table\ContractVersionsTable::buildRules()
     */
    public function testAVersionPapersEndStays(): void
    {
        $versions = $this->fetchTable('ContractVersions');

        $free = $this->aVersion();
        $this->assertTrue($versions->delete($versions->get($free)), 'Nothing else keeps a version.');

        $ended = $this->aVersion();
        $this->fetchTable('ContractProposals')->updateAll(['terminates_contract_version_id' => $ended], ['1 = 1']);

        $this->assertRefused('ContractVersions', $ended);
    }

    /**
     * The record is still there, and whoever asked is told so rather than shown an error page.
     *
     * @param string $alias The table.
     * @param string $id The record.
     * @return void
     */
    private function assertRefused(string $alias, string $id): void
    {
        $table = $this->fetchTable($alias);
        $record = $table->get($id);

        $this->assertFalse($table->delete($record));
        $this->assertNotSame([], $record->getErrors());
        $this->assertTrue($table->exists(['id' => $id]));
    }

    /**
     * A version of the contract with nothing to do with anything.
     *
     * @return string
     */
    private function aVersion(): string
    {
        $versions = $this->fetchTable('ContractVersions');
        $version = $versions->newEntity([], ['validate' => false]);
        foreach (['contract_id' => self::FREE_CONTRACT_ID, 'valid_from' => '2026-01-01'] as $field => $value) {
            $version->set($field, $value);
        }
        $versions->saveOrFail($version, ['checkRules' => false]);

        return (string)$version->get('id');
    }

    /**
     * A folder of a kind that asks for nothing.
     *
     * @param array<string, string> $under What it is filed under.
     * @return void
     */
    private function aFolder(array $under): void
    {
        $types = $this->fetchTable('DocumentationTypes');
        $kind = $types->saveOrFail($types->newEntity([
            'name' => 'Documentation',
            'position' => 0,
            'currently_offered' => true,
            'date_required' => false,
        ]));

        $folders = $this->fetchTable('Documentations');
        $folders->saveOrFail($folders->newEntity($under + [
            'documentation_type_id' => $kind->get('id'),
            'name' => 'The wiring in the rack',
        ]));
    }

    /**
     * A line of the history of connections.
     *
     * @param array<string, string> $under Whose it is.
     * @return void
     */
    private function aHistoricalConnection(array $under): void
    {
        $connections = $this->fetchTable('HistoricalConnections');
        $connection = $connections->newEmptyEntity();
        $data = $under + [
            'source' => HistoricalConnectionSource::Radius,
            'source_reference' => 'account-1',
            'first_seen' => new DateTime('2026-01-01 10:00:00'),
            'first_seen_source' => FirstSeenSource::Session,
            'last_seen' => new DateTime('2026-01-01 12:00:00'),
        ];
        foreach ($data as $field => $value) {
            $connection->set($field, $value);
        }
        $connections->saveOrFail($connection, ['checkRules' => false]);
    }
}
