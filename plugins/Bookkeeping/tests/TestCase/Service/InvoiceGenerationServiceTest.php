<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Service;

use App\Model\Table\AccountingProfilesTable;
use App\Model\Table\BillingsTable;
use App\Model\Table\ContractsTable;
use App\Model\Table\ContractStatesTable;
use App\Model\Table\CustomersTable;
use App\Model\Table\ServiceTypesTable;
use App\Test\Traits\ConfigureTestTrait;
use Bookkeeping\Model\ValueObject\InvoiceDraft;
use Bookkeeping\Service\InvoiceGenerationService;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PhpCollective\DecimalObject\Decimal;
use PHPUnit\Framework\Attributes\CoversClass;
use Settings\Utility\Settings;

/**
 * Bookkeeping\Service\InvoiceGenerationService Test Case
 *
 * What a month of billings turns into before it goes to the accounting system. The amounts of a
 * single line are in BillingTest, here it is who gets how many invoices, for how much, under which
 * number and until when.
 */
#[CoversClass(InvoiceGenerationService::class)]
class InvoiceGenerationServiceTest extends TestCase
{
    use ConfigureTestTrait;
    use LocatorAwareTrait;

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const OTHER_CUSTOMER_ID = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const OTHER_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

    private const PROFILE_ID = 'ab05963c-1531-4677-a9ee-80cecde25124';

    /**
     * What the numbers of a month start from: the year, the kind of invoice and the month.
     */
    private const FIRST_NUMBER_OF_JUNE = '469060001';

    private const FIRST_REVERSE_CHARGE_NUMBER_OF_JUNE = '468060001';

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
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.Emails',
        'app.Phones',
        'plugin.Settings.Settings',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->withConfigure([
            'Customers.series' => 100000,
            'Billings.roundingPlaces' => 2,
            'Billings.roundingType' => 'HALF_UP',
        ]);
        Settings::set('bookkeeping.invoices.texts.default', 'Services {invoiced_month}, {contract_number}');
        Settings::set('bookkeeping.invoices.texts.separate', '{service_name} {invoiced_month}');

        $this->fetchTable(BillingsTable::class)->deleteAll(['1 = 1']);
        $this->fetchTable(ServiceTypesTable::class)->updateAll(
            ['separate_invoice' => false, 'invoice_with_items' => false],
            ['1 = 1'],
        );
        $this->fetchTable(AccountingProfilesTable::class)->updateAll(
            ['reverse_charge' => false, 'invoice_with_items' => false],
            ['1 = 1'],
        );
        $this->fetchTable(CustomersTable::class)->updateAll(['individual_maturity_period' => null], ['1 = 1']);
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * The lines of a customer go on one invoice, part of a month for its days. It is dated by the
     * last day of the month and due ten days later, under the customer's number as the symbol.
     *
     * @return void
     * @link \Bookkeeping\Service\InvoiceGenerationService::generate()
     */
    public function testTheLinesOfACustomerGoOnOneInvoice(): void
    {
        $this->billing('300', '2020-01-01');
        $this->billing('100', '2026-06-16');

        $drafts = $this->june();

        $this->assertCount(1, $drafts);
        $this->assertAmount('350', $drafts[0]->total);
        $this->assertAmount('350', $drafts[0]->debt);
        $this->assertSame(self::FIRST_NUMBER_OF_JUNE, $drafts[0]->number);
        $this->assertSame('100001', $drafts[0]->variableSymbol);
        $this->assertSame('100001', $drafts[0]->customerNumber);
        $this->assertSame('2026-06-30', $drafts[0]->creationDate?->format('Y-m-d'));
        $this->assertSame('2026-07-10', $drafts[0]->dueDate?->format('Y-m-d'));
        $this->assertSame('Services 06/2026, 100001', $drafts[0]->text);
        $this->assertNull($drafts[0]->internalNote);
    }

    /**
     * Invoices with reverse charge are numbered in a series of their own.
     *
     * @return void
     * @link \Bookkeeping\Service\InvoiceGenerationService::generate()
     */
    public function testReverseChargeHasASeriesOfItsOwn(): void
    {
        $this->fetchTable(AccountingProfilesTable::class)->updateAll(['reverse_charge' => true], ['1 = 1']);
        $this->billing('300', '2020-01-01');

        $this->assertSame(self::FIRST_REVERSE_CHARGE_NUMBER_OF_JUNE, $this->june()[0]->number);
    }

    /**
     * A customer with a maturity of their own is given that long.
     *
     * @return void
     * @link \Bookkeeping\Service\InvoiceGenerationService::generate()
     */
    public function testAMaturityOfTheirOwnGoesBeforeTheUsualOne(): void
    {
        $this->fetchTable(CustomersTable::class)->updateAll(['individual_maturity_period' => 14], ['id' => self::CUSTOMER_ID]);
        $this->billing('300', '2020-01-01');

        $this->assertSame('2026-07-14', $this->june()[0]->dueDate?->format('Y-m-d'));
    }

    /**
     * A line billed on its own gets an invoice of its own, with the rest on the common one, and the
     * numbers run on.
     *
     * @return void
     * @link \Bookkeeping\Service\InvoiceGenerationService::generate()
     */
    public function testALineBilledOnItsOwnGetsAnInvoiceOfItsOwn(): void
    {
        $this->billing('300', '2020-01-01');
        $this->billing('100', '2020-01-01', separate: true, text: 'Backup line');

        $drafts = $this->june();

        $this->assertCount(2, $drafts);
        $this->assertAmount('100', $drafts[0]->total);
        $this->assertSame('Backup line 06/2026', $drafts[0]->text);
        $this->assertSame('separate', $drafts[0]->internalNote);
        $this->assertAmount('300', $drafts[1]->total);
        $this->assertNull($drafts[1]->internalNote);
        $this->assertSame([self::FIRST_NUMBER_OF_JUNE, '469060002'], [$drafts[0]->number, $drafts[1]->number]);
    }

    /**
     * A contract whose service type is invoiced apart gets an invoice of its own.
     *
     * @return void
     * @link \Bookkeeping\Service\InvoiceGenerationService::generate()
     */
    public function testAContractInvoicedApartGetsAnInvoiceOfItsOwn(): void
    {
        $this->fetchTable(ServiceTypesTable::class)->updateAll(['separate_invoice' => true], ['1 = 1']);
        $this->billing('300', '2020-01-01');

        $drafts = $this->june();

        $this->assertCount(1, $drafts);
        $this->assertSame('separate', $drafts[0]->internalNote);
    }

    /**
     * Customers go one after another in the numbers of the month.
     *
     * @return void
     * @link \Bookkeeping\Service\InvoiceGenerationService::generate()
     */
    public function testEachCustomerGetsTheNextNumber(): void
    {
        $this->theOtherContractBelongsToTheOtherCustomer();
        $this->billing('300', '2020-01-01');
        $this->billing('100', '2020-01-01', contractId: self::OTHER_CONTRACT_ID, customerId: self::OTHER_CUSTOMER_ID);

        $drafts = $this->june();

        $this->assertCount(2, $drafts);
        $numbers = [$drafts[0]->number, $drafts[1]->number];
        sort($numbers);
        $this->assertSame([self::FIRST_NUMBER_OF_JUNE, '469060002'], $numbers);
    }

    /**
     * A run can be narrowed to a band of customer numbers.
     *
     * @return void
     * @link \Bookkeeping\Service\InvoiceGenerationService::generate()
     */
    public function testARunCanBeNarrowedToCustomerNumbers(): void
    {
        $this->theOtherContractBelongsToTheOtherCustomer();
        $this->billing('300', '2020-01-01');
        $this->billing('100', '2020-01-01', contractId: self::OTHER_CONTRACT_ID, customerId: self::OTHER_CUSTOMER_ID);

        $fromTheSecond = $this->june(customerMinNumber: 100002);
        $upToTheFirst = $this->june(customerMaxNumber: 100001);

        $this->assertSame(['100002'], array_map(fn(InvoiceDraft $draft): ?string => $draft->customerNumber, $fromTheSecond));
        $this->assertSame(['100001'], array_map(fn(InvoiceDraft $draft): ?string => $draft->customerNumber, $upToTheFirst));
    }

    /**
     * The contracts of a customer go by their number, either way round, and not by the order the
     * association keeps, which their random ids would decide.
     *
     * @return void
     * @link \App\Model\Table\CustomersTable::findBillingDataForMonth()
     */
    public function testTheContractsOfACustomerGoByTheirNumber(): void
    {
        $contracts = $this->fetchTable(ContractsTable::class);
        $contracts->updateAll(['service_type_id' => $contracts->get(self::CONTRACT_ID)->service_type_id], ['1 = 1']);
        $contracts->updateAll(['contract_state_id' => $contracts->get(self::CONTRACT_ID)->contract_state_id], ['1 = 1']);

        $numbers = [900001, 900002, 900003, 900004];
        foreach ([[self::CONTRACT_ID, self::OTHER_CONTRACT_ID], [self::OTHER_CONTRACT_ID, self::CONTRACT_ID]] as $order) {
            // the numbers are unique, so each round takes numbers of its own
            $contracts->updateAll(['nid' => array_shift($numbers)], ['id' => $order[0]]);
            $contracts->updateAll(['nid' => array_shift($numbers)], ['id' => $order[1]]);

            $customer = $this->fetchTable(CustomersTable::class)
                ->find('billingDataForMonth', invoicedMonth: new Date('2026-06-01'), accountingProfileId: self::PROFILE_ID)
                ->where(['Customers.id' => self::CUSTOMER_ID])
                ->firstOrFail();

            $this->assertSame($order, array_column((array)$customer->get('contracts'), 'id'));
        }
    }

    /**
     * A contract in a state that is not billed is not invoiced, whatever its lines say.
     *
     * @return void
     * @link \App\Model\Table\CustomersTable::findBillingDataForMonth()
     */
    public function testAContractThatIsNotBilledIsNotInvoiced(): void
    {
        $this->fetchTable(ContractStatesTable::class)->updateAll(['billed' => false], ['1 = 1']);
        $this->billing('300', '2020-01-01');

        $this->assertSame([], $this->june());
    }

    /**
     * What does not run in the month is not invoiced, and a line that comes to nothing makes no invoice.
     *
     * @return void
     * @link \App\Model\Table\CustomersTable::findBillingDataForMonth()
     */
    public function testWhatDoesNotRunInTheMonthIsNotInvoiced(): void
    {
        $this->billing('300', '2020-01-01', '2026-05-31');
        $this->billing('300', '2026-07-01');
        $this->billing('0', '2020-01-01');

        $this->assertSame([], $this->june());
    }

    /**
     * Only a profile that asks for it lists the lines on the invoice, and only those of the month:
     * one that is yet to start would come to nothing and still be printed.
     *
     * @return void
     * @link \Bookkeeping\Service\InvoiceGenerationService::generate()
     */
    public function testTheLinesAreListedWhereTheProfileAsksForIt(): void
    {
        $this->billing('300', '2020-01-01');
        $this->billing('100', '2020-01-01');
        $this->billing('100', '2026-07-01');

        $this->assertSame([], $this->june()[0]->items);

        $this->fetchTable(AccountingProfilesTable::class)->updateAll(['invoice_with_items' => true], ['1 = 1']);

        $this->assertCount(2, $this->june()[0]->items);
    }

    /**
     * The invoices of June.
     *
     * @param int|null $customerMinNumber The lowest customer number.
     * @param int|null $customerMaxNumber The highest customer number.
     * @return list<\Bookkeeping\Model\ValueObject\InvoiceDraft>
     */
    private function june(?int $customerMinNumber = null, ?int $customerMaxNumber = null): array
    {
        return (new InvoiceGenerationService($this->fetchTable(CustomersTable::class)))->generate(
            new Date('2026-06-01'),
            $this->fetchTable(AccountingProfilesTable::class)->get(self::PROFILE_ID),
            $customerMinNumber,
            $customerMaxNumber,
        );
    }

    /**
     * A line of a contract.
     *
     * @param string $price The price.
     * @param string $from First day billed.
     * @param string|null $until Last day billed.
     * @param bool $separate Whether it is billed on its own.
     * @param string $text What it is called.
     * @param string $contractId The contract.
     * @param string $customerId The customer.
     * @return void
     */
    private function billing(
        string $price,
        string $from,
        ?string $until = null,
        bool $separate = false,
        string $text = 'A line',
        string $contractId = self::CONTRACT_ID,
        string $customerId = self::CUSTOMER_ID,
    ): void {
        $billings = $this->fetchTable(BillingsTable::class);
        $billings->saveOrFail($billings->newEntity([
            'customer_id' => $customerId,
            'contract_id' => $contractId,
            'text' => $text,
            'price' => $price,
            'quantity' => 1,
            'billing_from' => $from,
            'billing_until' => $until,
            'separate_invoice' => $separate,
        ], ['validate' => false, 'accessibleFields' => ['*' => true]]), ['checkRules' => false]);
    }

    /**
     * Give the second customer a contract.
     *
     * @return void
     */
    private function theOtherContractBelongsToTheOtherCustomer(): void
    {
        $this->fetchTable(ContractsTable::class)->updateAll(
            ['customer_id' => self::OTHER_CUSTOMER_ID],
            ['id' => self::OTHER_CONTRACT_ID],
        );
    }

    /**
     * @param string $expected The amount.
     * @param \PhpCollective\DecimalObject\Decimal|null $actual What came out.
     * @return void
     */
    private function assertAmount(string $expected, ?Decimal $actual): void
    {
        $this->assertNotNull($actual);
        $this->assertTrue($actual->equals(Decimal::create($expected)), $expected . ' is not ' . $actual);
    }
}
