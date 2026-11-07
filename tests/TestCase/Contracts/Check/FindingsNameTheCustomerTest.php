<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Check\CheckInterface;
use App\Check\CheckScope;
use App\Contracts\Check\BillingGapCheck;
use App\Contracts\Check\ContractVersionGapCheck;
use App\Contracts\Check\OverlappingBillingsCheck;
use App\Contracts\Check\OverlappingContractVersionsCheck;
use App\Model\Table\BillingsTable;
use App\Model\Table\ContractVersionsTable;
use Cake\Cache\Cache;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The listing of these checks has a column for the customer, with the running contracts only and
 * with all of them. The two scopes build the query differently, so each check is asked under both.
 */
#[CoversClass(BillingGapCheck::class)]
#[CoversClass(ContractVersionGapCheck::class)]
#[CoversClass(OverlappingBillingsCheck::class)]
#[CoversClass(OverlappingContractVersionsCheck::class)]
class FindingsNameTheCustomerTest extends TestCase
{
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const SERVICE_ID = 'eaacfeb3-1430-43ce-842e-497c5c95d953';

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
        'app.Billings',
        'plugin.Settings.Settings',
    ];

    private BillingsTable $Billings;

    private ContractVersionsTable $ContractVersions;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->Billings = $this->getTableLocator()->get('Billings', ['className' => BillingsTable::class]);
        $this->Billings->deleteAll(['1 = 1']);
        $this->ContractVersions = $this->getTableLocator()
            ->get('ContractVersions', ['className' => ContractVersionsTable::class]);
        $this->ContractVersions->deleteAll(['1 = 1']);
        $this->getTableLocator()->get('ContractStates')->updateAll(['active_services' => true], ['1 = 1']);

        Cache::clear('default');
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('default');

        parent::tearDown();
    }

    /**
     * @return array<string, array{bool}>
     */
    public static function scopes(): array
    {
        return [
            'the running contracts only' => [true],
            'all contracts' => [false],
        ];
    }

    /**
     * @param bool $ignore_inactive Whether to keep to the running contracts.
     * @return void
     * @link \App\Contracts\Check\OverlappingBillingsCheck::find()
     */
    #[DataProvider('scopes')]
    public function testOverlappingBillingsNameTheCustomer(bool $ignore_inactive): void
    {
        $this->billed('-2 years', null);
        $this->billed('-1 year', null);

        $this->assertTheFindingNamesTheCustomer(
            new OverlappingBillingsCheck($this->Billings, new CheckScope($ignore_inactive)),
        );
    }

    /**
     * @param bool $ignore_inactive Whether to keep to the running contracts.
     * @return void
     * @link \App\Contracts\Check\BillingGapCheck::find()
     */
    #[DataProvider('scopes')]
    public function testABreakInBillingNamesTheCustomer(bool $ignore_inactive): void
    {
        $this->billed('+1 month', '+13 months');
        $this->billed('+25 months', null);

        $this->assertTheFindingNamesTheCustomer(
            new BillingGapCheck($this->Billings, new CheckScope($ignore_inactive)),
        );
    }

    /**
     * @param bool $ignore_inactive Whether to keep to the running contracts.
     * @return void
     * @link \App\Contracts\Check\OverlappingContractVersionsCheck::find()
     */
    #[DataProvider('scopes')]
    public function testOverlappingVersionsNameTheCustomer(bool $ignore_inactive): void
    {
        $this->inForce('2022-01-01', null);
        $this->inForce('2023-01-01', null);

        $this->assertTheFindingNamesTheCustomer(
            new OverlappingContractVersionsCheck($this->ContractVersions, new CheckScope($ignore_inactive)),
        );
    }

    /**
     * @param bool $ignore_inactive Whether to keep to the running contracts.
     * @return void
     * @link \App\Contracts\Check\ContractVersionGapCheck::find()
     */
    #[DataProvider('scopes')]
    public function testABreakBetweenVersionsNamesTheCustomer(bool $ignore_inactive): void
    {
        $this->inForce('2022-01-01', '2022-12-31');
        $this->inForce(Date::now()->modify('+3 months')->format('Y-m-d'), null);

        $this->assertTheFindingNamesTheCustomer(
            new ContractVersionGapCheck($this->ContractVersions, new CheckScope($ignore_inactive)),
        );
    }

    /**
     * The finding comes with its customer, and the count the cards show still works.
     *
     * @param \App\Check\CheckInterface $check The check.
     * @return void
     */
    private function assertTheFindingNamesTheCustomer(CheckInterface $check): void
    {
        /** @var list<\App\Model\Entity\Billing|\App\Model\Entity\ContractVersion> $records */
        $records = $check->find()->all()->toList();

        $this->assertCount(1, $records);
        $this->assertSame(self::CUSTOMER_ID, $records[0]->contract?->customer?->id);
        $this->assertSame(1, $check->count());
    }

    /**
     * Bill the service over a stretch of time, given relative to today.
     *
     * @param string $from When billing starts, as a modifier of today.
     * @param string|null $until When it ends, or null for an open end.
     * @return void
     */
    private function billed(string $from, ?string $until): void
    {
        $this->Billings->saveOrFail($this->Billings->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'service_id' => self::SERVICE_ID,
            'billing_from' => Date::now()->modify($from)->format('Y-m-d'),
            'billing_until' => $until === null ? null : Date::now()->modify($until)->format('Y-m-d'),
            'quantity' => 1,
            'separate_invoice' => false,
        ]), [BillingsTable::ALLOW_CLOSED_PERIODS => true]);
    }

    /**
     * Put a version of the contract in force over a stretch of time.
     *
     * @param string $from The day it begins.
     * @param string|null $until The day it ends, or null for an open end.
     * @return void
     */
    private function inForce(string $from, ?string $until): void
    {
        $this->ContractVersions->saveOrFail($this->ContractVersions->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => $from,
            'valid_until' => $until,
            'number_of_amendments' => 0,
            'obligations_settled' => false,
        ]));
    }
}
