<?php
declare(strict_types=1);

namespace App\Test\TestCase\Addresses\Check;

use App\Addresses\Check\DuplicateAddressCheck;
use App\Model\Entity\Customer;
use App\Model\Enum\AddressNumberType;
use App\Model\Enum\AddressType;
use App\Model\Table\AddressesTable;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Addresses\Check\DuplicateAddressCheck Test Case
 */
#[UsesClass(DuplicateAddressCheck::class)]
class DuplicateAddressCheckTest extends TestCase
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
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
    ];

    /**
     * @var \App\Model\Table\AddressesTable
     */
    private AddressesTable $Addresses;

    /**
     * @var \App\Addresses\Check\DuplicateAddressCheck
     */
    private DuplicateAddressCheck $check;

    /**
     * Customer number to hand the next customer this test makes.
     *
     * @var int
     */
    private int $nid = 2000;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        /** @var \App\Model\Table\AddressesTable $addresses */
        $addresses = $this->fetchTable(AddressesTable::class);
        $this->Addresses = $addresses;

        // What these customers have running is asked separately, below.
        $this->check = new DuplicateAddressCheck($this->Addresses, false);
    }

    /**
     * A place written down twice, and the things that make the second one another place.
     *
     * @return array<string, array{array<string, mixed>, array<string, mixed>, bool}>
     */
    public static function addressPairs(): array
    {
        $place = [
            'street' => 'Long Street',
            'number' => '42',
            'city' => 'Town',
        ];

        return [
            'the same words twice' => [$place, $place, true],
            'the same words in another case and with spaces around them' => [
                $place,
                ['street' => '  LONG street ', 'number' => ' 42', 'city' => 'town  '],
                true,
            ],
            'nothing written down either time' => [
                ['street' => null, 'number' => null, 'city' => null],
                ['street' => '', 'number' => '', 'city' => ''],
                true,
            ],
            'another street' => [$place, ['street' => 'Short Street'] + $place, false],
            'another number' => [$place, ['number' => '43'] + $place, false],
            'another town' => [$place, ['city' => 'Village'] + $place, false],
            // a registration number points at a different building than a house number
            // that reads alike
            'the same number of another kind' => [
                ['number_type' => AddressNumberType::House] + $place,
                ['number_type' => AddressNumberType::Registration] + $place,
                false,
            ],
            'another entrance of the same building' => [
                ['entrance' => 'A'] + $place,
                ['entrance' => 'B'] + $place,
                false,
            ],
            'another unit behind the same entrance' => [
                ['entrance' => 'A', 'unit' => '3'] + $place,
                ['entrance' => 'A', 'unit' => '7'] + $place,
                false,
            ],
            'no entrance either time, left out one way or the other' => [
                ['entrance' => null, 'unit' => null] + $place,
                ['entrance' => '', 'unit' => ''] + $place,
                true,
            ],
            'the same words under another type' => [
                ['type' => AddressType::Installation] + $place,
                ['type' => AddressType::Billing] + $place,
                false,
            ],
            // somebody who placed the pin themselves said where the place is
            'pinned by hand on two spots' => [
                ['manual_coordinate_setting' => true, 'gps_x' => 15.4, 'gps_y' => 50.7] + $place,
                ['manual_coordinate_setting' => true, 'gps_x' => 15.5, 'gps_y' => 50.8] + $place,
                false,
            ],
            'pinned by hand on the same spot' => [
                ['manual_coordinate_setting' => true, 'gps_x' => 15.4, 'gps_y' => 50.7] + $place,
                ['manual_coordinate_setting' => true, 'gps_x' => 15.4, 'gps_y' => 50.7] + $place,
                true,
            ],
            'pinned by hand a few metres apart' => [
                ['manual_coordinate_setting' => true, 'gps_x' => 15.400001, 'gps_y' => 50.700001] + $place,
                ['manual_coordinate_setting' => true, 'gps_x' => 15.400009, 'gps_y' => 50.700009] + $place,
                true,
            ],
            'pinned by hand only one of the two times' => [
                ['manual_coordinate_setting' => true, 'gps_x' => 15.4, 'gps_y' => 50.7] + $place,
                ['manual_coordinate_setting' => false, 'gps_x' => 15.4, 'gps_y' => 50.7] + $place,
                false,
            ],
            // coordinates nobody placed are the registry's, and it hands the same house
            // slightly different ones from one lookup to the next
            'coordinates apart that nobody placed' => [
                ['manual_coordinate_setting' => false, 'gps_x' => 15.4, 'gps_y' => 50.7] + $place,
                ['manual_coordinate_setting' => false, 'gps_x' => 15.5, 'gps_y' => 50.8] + $place,
                true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $first The address written down first.
     * @param array<string, mixed> $second The address written down after it.
     * @param bool $duplicate Whether the two are one place recorded twice.
     * @return void
     * @link \App\Addresses\Check\DuplicateAddressCheck::find()
     */
    #[DataProvider('addressPairs')]
    public function testTellsOnePlaceWrittenTwiceFromTwoPlaces(array $first, array $second, bool $duplicate): void
    {
        $customer = $this->customerWith($first, $second);

        $found = $this->groupsFor($customer);

        if (!$duplicate) {
            $this->assertSame([], $found, 'Two places are not one address recorded twice.');

            return;
        }

        $this->assertCount(1, $found, 'One place written down twice is one group.');
        $this->assertSame(2, $found[0]->get('total'));
    }

    /**
     * The listing says how many rows say the same thing, on one line rather than on a line
     * each, and says it in the words somebody typed.
     *
     * @return void
     * @link \App\Addresses\Check\DuplicateAddressCheck::find()
     */
    public function testCountsTheWholeGroupOnOneRow(): void
    {
        $place = ['street' => 'Long Street', 'number' => '42', 'city' => 'Town'];
        $customer = $this->customerWith($place, $place, $place);

        $found = $this->groupsFor($customer);

        $this->assertCount(1, $found);
        $this->assertSame(3, $found[0]->get('total'));
        $this->assertSame('Long Street 42, Town', $found[0]->get('address'));
    }

    /**
     * A customer left on record with nothing running is not somebody whose addresses are
     * work. The same customer with something running is reported.
     *
     * @return void
     * @link \App\Addresses\Check\DuplicateAddressCheck::find()
     */
    public function testPassesOverCustomersWithNothingRunning(): void
    {
        $place = ['street' => 'Long Street', 'number' => '42', 'city' => 'Town'];
        $dormant = $this->customerWith($place, $place);
        $running = $this->customerWith($place, $place);
        $this->giveActiveContract($running);

        $ignoring = (new DuplicateAddressCheck($this->Addresses))
            ->find()->all()->extract('customer_id')->toList();

        $this->assertNotContains($dormant->id, $ignoring);
        $this->assertContains($running->id, $ignoring);

        // and with the filter lifted, both of them are there
        $all = $this->check->find()->all()->extract('customer_id')->toList();

        $this->assertContains($dormant->id, $all);
        $this->assertContains($running->id, $all);
    }

    /**
     * The groups reported for one customer.
     *
     * @param \App\Model\Entity\Customer $customer Customer to look for.
     * @return list<\Cake\Datasource\EntityInterface>
     */
    private function groupsFor(Customer $customer): array
    {
        // `toList()` hands back the keys it was given, so the caller cannot reach for the
        // first group by its position until they are numbered again.
        return array_values(
            $this->check->find()->all()->filter(
                fn(EntityInterface $group): bool => $group->get('customer_id') === $customer->id,
            )->toList(),
        );
    }

    /**
     * A contract in a state that provides services.
     *
     * @param \App\Model\Entity\Customer $customer Customer to give it to.
     * @return void
     */
    private function giveActiveContract(Customer $customer): void
    {
        $contracts = $this->Addresses->Customers->Contracts;

        // The rules would ask this contract for an installation address and an access point,
        // which is exactly what it is here not to have - all this contract is for is to say
        // that the customer still has something running.
        $contracts->saveOrFail(
            $contracts->newEntity(
                [
                    'customer_id' => $customer->id,
                    // the fixture state has active_services set
                    'contract_state_id' => '3fc51c92-5dbb-4bd4-9a47-237169c2755c',
                    'service_type_id' => '907cbc5c-af88-43b6-b535-959b4fa2ce3d',
                ],
                ['validate' => false],
            ),
            ['checkRules' => false],
        );
    }

    /**
     * A customer whose only addresses are the given ones.
     *
     * @param array<string, mixed> ...$addresses Addresses to give them.
     * @return \App\Model\Entity\Customer
     */
    private function customerWith(array ...$addresses): Customer
    {
        $customers = $this->Addresses->Customers;

        // The fixtures write their own nid rather than drawing one, which leaves the
        // sequence behind them - so a customer made here has to say which number it is.
        $this->nid++;

        $customer = $customers->saveOrFail(
            $customers->newEntity(
                [
                    'nid' => $this->nid,
                    'accounting_profile_id' => 'ab05963c-1531-4677-a9ee-80cecde25124',
                ],
                ['validate' => false, 'accessibleFields' => ['nid' => true]],
            ),
        );

        foreach ($addresses as $address) {
            $this->Addresses->saveOrFail(
                $this->Addresses->newEntity(
                    $address + [
                        'customer_id' => $customer->id,
                        'type' => AddressType::Installation,
                        'number_type' => AddressNumberType::House,
                        'country_id' => 'b490f1c9-ff7e-430a-bfb0-f400878e1617',
                    ],
                    ['validate' => false],
                ),
            );
        }

        return $customer;
    }
}
