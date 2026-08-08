<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\FulltextSearchCustomersTable;
use App\Test\Traits\FulltextSearchCustomersTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Override;

/**
 * App\Model\Table\FulltextSearchCustomersTable Test Case
 *
 * The table holds what the advanced search is answered from, so what is asked of it here is not
 * what it stores but what can be found afterwards - a document nobody can search by is the same
 * as no document at all.
 */
class FulltextSearchCustomersTableTest extends TestCase
{
    use FulltextSearchCustomersTestTrait;

    /**
     * The customer the fixtures carry a phone number, an address and an IP address for.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * The other customer the fixtures carry, who has none of that.
     *
     * @var string
     */
    private const OTHER_CUSTOMER_ID = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    /**
     * Test subject
     *
     * @var \App\Model\Table\FulltextSearchCustomersTable
     */
    protected $FulltextSearchCustomers;

    /**
     * The series in force before a test named its own.
     *
     * @var mixed
     */
    private mixed $seriesBefore = null;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.Emails',
        'app.Phones',
        'app.IpAddresses',
        'app.FulltextSearchCustomers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->seriesBefore = Configure::read('Customers.series');

        $config = $this->getTableLocator()->exists('FulltextSearchCustomers')
            ? []
            : ['className' => FulltextSearchCustomersTable::class];
        $this->FulltextSearchCustomers = $this->getTableLocator()->get('FulltextSearchCustomers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Configure::write('Customers.series', $this->seriesBefore);

        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->FulltextSearchCustomers);

        parent::tearDown();
    }

    /**
     * Every customer gets a document, whether or not anything is related to them.
     *
     * @return void
     * @link \App\Model\Table\FulltextSearchCustomersTable::rebuild()
     */
    public function testRebuildWritesADocumentForEveryCustomer(): void
    {
        $customers = $this->getTableLocator()->get('Customers')->find()->count();

        $this->assertSame($customers, $this->FulltextSearchCustomers->rebuild());
        $this->assertSame($customers, $this->FulltextSearchCustomers->find()->count());
    }

    /**
     * A customer is found by what their related records say, which is the whole reason the search
     * builds a document instead of reading the customer's own columns.
     *
     * @return void
     * @link \App\Model\Table\FulltextSearchCustomersTable::rebuild()
     */
    public function testRebuildFindsACustomerByWhatTheirRelatedRecordsSay(): void
    {
        $this->FulltextSearchCustomers->rebuild();

        // the address of the fixture IP address, which is on no column of the customer
        $this->assertContains(self::CUSTOMER_ID, $this->customersFoundBy('192.168.11.11'));
    }

    /**
     * The customer number is stored as it is shown, with the series added to it - what is on the
     * invoice is the sum, not the number in the column.
     *
     * @return void
     * @link \App\Model\Table\FulltextSearchCustomersTable::rebuild()
     */
    public function testRebuildStoresTheCustomerNumberAsItIsShown(): void
    {
        Configure::write('Customers.series', 550000);

        $this->FulltextSearchCustomers->rebuild();

        // the fixture customer is number 1, so 550001 is only there if the series was added
        $this->assertContains(self::CUSTOMER_ID, $this->customersFoundBy('550001'));
    }

    /**
     * A refresh rebuilds the customers it is given and leaves the rest as they were.
     *
     * Both customers are changed behind the application's back, so the only difference between
     * them is which one the refresh was told about.
     *
     * @return void
     * @link \App\Model\Table\FulltextSearchCustomersTable::refresh()
     */
    public function testRefreshRebuildsOnlyTheCustomersItIsGiven(): void
    {
        $this->FulltextSearchCustomers->rebuild();

        $connection = $this->FulltextSearchCustomers->getConnection();
        $connection->execute(
            'UPDATE customers SET company = ? WHERE id IN (?, ?)',
            ['Ravenswood', self::CUSTOMER_ID, self::OTHER_CUSTOMER_ID],
        );

        $this->assertSame(1, $this->FulltextSearchCustomers->refresh([self::CUSTOMER_ID]));

        $this->assertSame([self::CUSTOMER_ID], $this->customersFoundBy('Ravenswood'));
    }

    /**
     * A customer who is no longer there has no document to build, which is not an error - the row
     * they had went with them.
     *
     * @return void
     * @link \App\Model\Table\FulltextSearchCustomersTable::refresh()
     */
    public function testRefreshOfACustomerWhoIsGoneWritesNothing(): void
    {
        $this->assertSame(0, $this->FulltextSearchCustomers->refresh([Text::uuid()]));
        $this->assertSame(0, $this->FulltextSearchCustomers->refresh([]));
    }
}
