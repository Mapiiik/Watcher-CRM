<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Address;
use App\Model\Enum\AddressNumberType;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Entity\Address Test Case
 */
#[CoversClass(Address::class)]
class AddressTest extends TestCase
{
    /**
     * Test that all name parts are joined in order.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getFullName()
     */
    public function testFullNameJoinsAllParts(): void
    {
        $address = new Address([
            'title' => 'Ing.',
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'suffix' => 'Ph.D.',
        ]);

        $this->assertSame('Ing. Jan Novak Ph.D.', $address->full_name);
    }

    /**
     * Test that missing name parts leave no double or leading space.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getFullName()
     */
    public function testFullNameSkipsMissingParts(): void
    {
        $address = new Address([
            'last_name' => 'Novak',
        ]);

        $this->assertSame('Novak', $address->full_name);
    }

    /**
     * Test that a company is bracketed and put before the person.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getName()
     */
    public function testNameCombinesCompanyAndPerson(): void
    {
        $address = new Address([
            'company' => 'NETAIR, s.r.o.',
            'first_name' => 'Jan',
            'last_name' => 'Novak',
        ]);

        $this->assertSame('[NETAIR, s.r.o.] Jan Novak', $address->name);
    }

    /**
     * Test that a company without a person leaves no trailing space.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getName()
     */
    public function testNameWithCompanyOnly(): void
    {
        $address = new Address([
            'company' => 'NETAIR, s.r.o.',
        ]);

        $this->assertSame('[NETAIR, s.r.o.]', $address->name);
    }

    /**
     * Test that a city without a zip leaves no leading space.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getZipAndCity()
     */
    public function testZipAndCityWithoutZipHasNoLeadingSpace(): void
    {
        $address = new Address([
            'city' => 'Jablonec nad Jizerou',
        ]);

        $this->assertSame('Jablonec nad Jizerou', $address->zip_and_city);
    }

    /**
     * Test that a house number next to a street is written without a marker.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getStreetAndNumber()
     */
    public function testStreetAndNumberWritesHouseNumberWithStreetPlain(): void
    {
        $address = new Address([
            'street' => 'Studentska',
            'number' => '1903/14a',
            'number_type' => AddressNumberType::House,
        ]);

        $this->assertSame('Studentska 1903/14a', $address->street_and_number);
    }

    /**
     * Test that a registration number keeps its marker even when a street is present,
     * otherwise it reads as a house number and points at a different building.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getStreetAndNumber()
     */
    public function testStreetAndNumberMarksRegistrationNumberWithStreet(): void
    {
        $address = new Address([
            'street' => 'Koliste',
            'number' => '1',
            'number_type' => AddressNumberType::Registration,
        ]);

        $this->assertSame('Koliste Reg. No. 1', $address->street_and_number);
    }

    /**
     * Test that a house number without a street carries a marker.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getStreetAndNumber()
     */
    public function testStreetAndNumberMarksHouseNumberWithoutStreet(): void
    {
        $address = new Address([
            'number' => '111',
            'number_type' => AddressNumberType::House,
        ]);

        $this->assertSame('No. 111', $address->street_and_number);
    }

    /**
     * Test that a registration number without a street carries a marker.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getStreetAndNumber()
     */
    public function testStreetAndNumberMarksRegistrationNumberWithoutStreet(): void
    {
        $address = new Address([
            'number' => '15',
            'number_type' => AddressNumberType::Registration,
        ]);

        $this->assertSame('Reg. No. 15', $address->street_and_number);
    }

    /**
     * Test that a street without a number leaves no trailing space.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getStreetAndNumber()
     */
    public function testStreetAndNumberWithoutNumberHasNoTrailingSpace(): void
    {
        $address = new Address([
            'street' => 'Na Lukach',
            'number_type' => AddressNumberType::House,
        ]);

        $this->assertSame('Na Lukach', $address->street_and_number);
    }

    /**
     * Test that an object located by coordinates alone has an empty street line.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getStreetAndNumber()
     */
    public function testStreetAndNumberIsEmptyWithoutStreetAndNumber(): void
    {
        $address = new Address([
            'number_type' => AddressNumberType::House,
        ]);

        $this->assertSame('', $address->street_and_number);
    }

    /**
     * Test that entrance and unit are appended to the same street line.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getStreetAndNumberExtra()
     */
    public function testStreetAndNumberExtraAppendsEntranceAndUnit(): void
    {
        $address = new Address([
            'street' => 'Koliste',
            'number' => '1',
            'number_type' => AddressNumberType::Registration,
            'entrance' => 'B',
            'unit' => '4',
        ]);

        $this->assertSame('Koliste Reg. No. 1, entrance B, unit 4', $address->street_and_number_extra);
    }

    /**
     * Test that a missing street line does not leave a leading comma.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getAddress()
     */
    public function testAddressWithoutStreetLineHasNoLeadingComma(): void
    {
        $address = new Address([
            'number_type' => AddressNumberType::House,
            'zip' => '51243',
            'city' => 'Jablonec nad Jizerou',
        ]);

        $this->assertSame('512 43 Jablonec nad Jizerou', $address->address);
    }

    /**
     * Test that the name and the address are joined into a single line.
     *
     * @return void
     * @link \App\Model\Entity\Address::_getFullAddress()
     */
    public function testFullAddressJoinsNameAndAddress(): void
    {
        $address = new Address([
            'company' => 'NETAIR, s.r.o.',
            'street' => 'Jablonec nad Jizerou',
            'number' => '299',
            'number_type' => AddressNumberType::House,
            'zip' => '51243',
            'city' => 'Jablonec nad Jizerou',
        ]);

        $this->assertSame(
            '[NETAIR, s.r.o.], Jablonec nad Jizerou 299, 512 43 Jablonec nad Jizerou',
            $address->full_address,
        );
    }
}
