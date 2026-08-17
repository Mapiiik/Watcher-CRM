<?php
declare(strict_types=1);

namespace App\Test\TestCase\Addresses\Check;

use App\Addresses\Check\UnclearBillingAddressCheck;
use App\Model\Entity\Customer;
use App\Model\Enum\AddressType;
use App\Model\Enum\BillingAddressProblem;
use App\Model\Table\CustomersTable;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Addresses\Check\UnclearBillingAddressCheck Test Case
 */
#[UsesClass(UnclearBillingAddressCheck::class)]
class UnclearBillingAddressCheckTest extends TestCase
{
    use LocatorAwareTrait;

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
    ];

    /**
     * @var \App\Model\Table\CustomersTable
     */
    private CustomersTable $Customers;

    /**
     * @var \App\Addresses\Check\UnclearBillingAddressCheck
     */
    private UnclearBillingAddressCheck $check;

    /**
     * Customer number to hand the next customer this test makes.
     *
     * @var int
     */
    private int $nid = 1000;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var \App\Model\Table\CustomersTable $customers */
        $customers = $this->fetchTable(CustomersTable::class);
        $this->Customers = $customers;
        $this->check = new UnclearBillingAddressCheck($this->Customers);
    }

    /**
     * Every combination the fallback can land on, and what it should be called.
     *
     * @return array<string, array{list<\App\Model\Enum\AddressType>, \App\Model\Enum\BillingAddressProblem|null}>
     */
    public static function addressSets(): array
    {
        return [
            'no address at all' => [[], BillingAddressProblem::Missing],
            // the fallback does not accept a delivery address, so this is as good as none
            'only a delivery address' => [[AddressType::Delivery], BillingAddressProblem::Missing],
            'one billing address' => [[AddressType::Billing], null],
            'one permanent address' => [[AddressType::Permanent], null],
            // the ordinary customer: no billing address, and the fallback settles it
            'one installation address' => [[AddressType::Installation], null],
            'two billing addresses' => [
                [AddressType::Billing, AddressType::Billing],
                BillingAddressProblem::AmbiguousBilling,
            ],
            'two permanent addresses' => [
                [AddressType::Permanent, AddressType::Permanent],
                BillingAddressProblem::AmbiguousPermanent,
            ],
            'two installation addresses' => [
                [AddressType::Installation, AddressType::Installation],
                BillingAddressProblem::AmbiguousInstallation,
            ],
            // the winning type is the only one whose count can still make it doubtful
            'one billing address beside two installation ones' => [
                [AddressType::Billing, AddressType::Installation, AddressType::Installation],
                null,
            ],
            'one permanent address beside two installation ones' => [
                [AddressType::Permanent, AddressType::Installation, AddressType::Installation],
                null,
            ],
            'two billing addresses beside one permanent one' => [
                [AddressType::Billing, AddressType::Billing, AddressType::Permanent],
                BillingAddressProblem::AmbiguousBilling,
            ],
        ];
    }

    /**
     * @param list<\App\Model\Enum\AddressType> $types Addresses the customer is given.
     * @param \App\Model\Enum\BillingAddressProblem|null $expected What it should be reported as.
     * @return void
     * @link \App\Addresses\Check\UnclearBillingAddressCheck::find()
     */
    #[DataProvider('addressSets')]
    public function testReportsWhatTheFallbackCannotSettle(array $types, ?BillingAddressProblem $expected): void
    {
        $customer = $this->customerWith($types);

        /** @var \App\Model\Entity\Customer|null $found */
        $found = $this->check->find()->all()->filter(
            fn(Customer $reported): bool => $reported->id === $customer->id,
        )->first();

        if ($expected === null) {
            $this->assertNull($found, 'The invoice address follows from the addresses, so nothing is wrong.');

            return;
        }

        $this->assertNotNull($found, 'The invoice address cannot be told from the addresses.');
        $this->assertSame($expected, $found->billing_address_problem);
    }

    /**
     * The report has to agree with the getter that does the picking, or it is reporting
     * something nobody experiences.
     *
     * @return void
     * @link \App\Model\Entity\Customer::_getBillingAddress()
     */
    public function testAgreesWithTheGetterOnWhetherThereIsAnAddress(): void
    {
        foreach (self::addressSets() as $name => [$types, $expected]) {
            $customer = $this->customerWith($types);

            $loaded = $this->Customers->get($customer->id, contain: ['Addresses']);

            $this->assertSame(
                $expected === BillingAddressProblem::Missing,
                $loaded->billing_address === null,
                sprintf('The getter and the check disagree about "%s".', $name),
            );
        }
    }

    /**
     * A customer whose only addresses are the given ones.
     *
     * @param list<\App\Model\Enum\AddressType> $types Types to give them, one address each.
     * @return \App\Model\Entity\Customer
     */
    private function customerWith(array $types): Customer
    {
        // The fixtures write their own nid rather than drawing one, which leaves the
        // sequence behind them - so a customer made here has to say which number it is.
        $this->nid++;

        $customer = $this->Customers->saveOrFail(
            $this->Customers->newEntity(
                [
                    'nid' => $this->nid,
                    'accounting_profile_id' => 'ab05963c-1531-4677-a9ee-80cecde25124',
                ],
                ['validate' => false, 'accessibleFields' => ['nid' => true]],
            ),
        );

        foreach ($types as $index => $type) {
            $this->Customers->Addresses->saveOrFail(
                $this->Customers->Addresses->newEntity(
                    [
                        'customer_id' => $customer->id,
                        'type' => $type,
                        'country_id' => 'b490f1c9-ff7e-430a-bfb0-f400878e1617',
                        'street' => 'Street ' . $index,
                        'number' => (string)$index,
                        'city' => 'Town',
                    ],
                    ['validate' => false],
                ),
            );
        }

        return $customer;
    }
}
