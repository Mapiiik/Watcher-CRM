<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Behavior;

use App\Model\Table\CustomersTable;
use App\Model\Table\PhonesTable;
use App\Test\Traits\FulltextSearchCustomersTestTrait;
use Cake\Datasource\EntityInterface;
use Cake\TestSuite\TestCase;
use Override;

/**
 * App\Model\Behavior\FulltextSearchCustomersBehavior Test Case
 *
 * What the behavior is for is that nobody has to remember it: whatever is saved, the customer can
 * be found by it from that moment on. The tests therefore never touch the document - they save a
 * record and then ask the search.
 */
class FulltextSearchCustomersBehaviorTest extends TestCase
{
    use FulltextSearchCustomersTestTrait;

    /**
     * The customer the fixtures carry the related records for.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * The other customer the fixtures carry.
     *
     * @var string
     */
    private const OTHER_CUSTOMER_ID = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    /**
     * A number worth searching for, written the way somebody would write it down.
     *
     * @var string
     */
    private const PHONE = '+420 604 694 702';

    /**
     * Test subject
     *
     * @var \App\Model\Table\PhonesTable
     */
    protected $Phones;

    /**
     * The customers, saved to directly where the test is about the customer's own fields.
     *
     * @var \App\Model\Table\CustomersTable
     */
    protected $Customers;

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

        $phonesConfig = $this->getTableLocator()->exists('Phones') ? [] : ['className' => PhonesTable::class];
        $this->Phones = $this->getTableLocator()->get('Phones', $phonesConfig);

        $customersConfig = $this->getTableLocator()->exists('Customers') ? [] : ['className' => CustomersTable::class];
        $this->Customers = $this->getTableLocator()->get('Customers', $customersConfig);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Phones);
        /** @phpstan-ignore unset.possiblyHookedProperty */
        unset($this->Customers);

        parent::tearDown();
    }

    /**
     * A saved record can be searched for straight away, with nothing else run in between.
     *
     * The number is searched for without its spaces, which is a form that appears nowhere in the
     * database - the document carries it because that is how people type a number they are looking
     * for.
     *
     * @return void
     * @link \App\Model\Behavior\FulltextSearchCustomersBehavior::afterSave()
     */
    public function testASavedRecordCanBeSearchedForAtOnce(): void
    {
        $this->savePhone(self::CUSTOMER_ID);

        $this->assertSame([self::CUSTOMER_ID], $this->customersFoundBy('604694702'));
    }

    /**
     * A record moved to another customer leaves the document of the one it came from.
     *
     * @return void
     * @link \App\Model\Behavior\FulltextSearchCustomersBehavior::afterSave()
     */
    public function testAMovedRecordIsTakenOutOfTheDocumentItWasIn(): void
    {
        $phone = $this->savePhone(self::CUSTOMER_ID);

        $phone->set('customer_id', self::OTHER_CUSTOMER_ID);
        $this->Phones->saveOrFail($phone);

        $this->assertSame([self::OTHER_CUSTOMER_ID], $this->customersFoundBy('604694702'));
    }

    /**
     * A deleted record is no longer something its customer can be found by.
     *
     * @return void
     * @link \App\Model\Behavior\FulltextSearchCustomersBehavior::afterDelete()
     */
    public function testADeletedRecordIsTakenOutOfTheDocument(): void
    {
        $phone = $this->savePhone(self::CUSTOMER_ID);

        $this->Phones->deleteOrFail($phone);

        $this->assertSame([], $this->customersFoundBy('604694702'));
    }

    /**
     * The customer's own fields are part of the document as well, so saving the customer is enough
     * for the change to be searched for.
     *
     * @return void
     * @link \App\Model\Behavior\FulltextSearchCustomersBehavior::afterSave()
     */
    public function testTheCustomersOwnFieldsAreKeptUpToDate(): void
    {
        $customer = $this->Customers->get(self::CUSTOMER_ID);
        $customer->set('company', 'Ravenswood');
        $this->Customers->saveOrFail($customer);

        $this->assertSame([self::CUSTOMER_ID], $this->customersFoundBy('Ravenswood'));
    }

    /**
     * A name is found whether or not the accents are typed - in the search or in the record.
     *
     * Both halves matter. Somebody looking for `Patočka` on a keyboard that is not theirs has to
     * find them, and so does somebody looking for `Patočka` properly when the name was entered
     * without the accents in the first place. What must not follow from that is a search that
     * finds words merely resembling the term, which is why the second assertion is here.
     *
     * @return void
     * @link \App\Database\FulltextSearchCustomersDocument::CONFIGURATION
     */
    public function testACustomerIsFoundWhicheverWayTheAccentsAreTyped(): void
    {
        $customer = $this->Customers->get(self::CUSTOMER_ID);
        $customer->set('company', 'Patočka');
        $this->Customers->saveOrFail($customer);

        $this->assertSame([self::CUSTOMER_ID], $this->customersFoundBy('patocka'));
        $this->assertSame([self::CUSTOMER_ID], $this->customersFoundBy('Patočka'));
        $this->assertSame([], $this->customersFoundBy('patocce'));
    }

    /**
     * The other way round: what is stored without the accents is found with them.
     *
     * @return void
     * @link \App\Database\FulltextSearchCustomersDocument::CONFIGURATION
     */
    public function testACustomerEnteredWithoutAccentsIsFoundWithThem(): void
    {
        $customer = $this->Customers->get(self::CUSTOMER_ID);
        $customer->set('company', 'Nemec');
        $this->Customers->saveOrFail($customer);

        $this->assertSame([self::CUSTOMER_ID], $this->customersFoundBy('Němec'));
    }

    /**
     * Record a phone number for a customer.
     *
     * @param string $customerId Customer the number belongs to.
     * @return \Cake\Datasource\EntityInterface
     */
    private function savePhone(string $customerId): EntityInterface
    {
        return $this->Phones->saveOrFail($this->Phones->newEntity([
            'customer_id' => $customerId,
            'phone' => self::PHONE,
        ]));
    }
}
