<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\BusinessRegister\VatNumberCheck;
use App\BusinessRegister\VatNumberStatus;
use App\Model\Entity\Customer;
use App\Test\TestCase\BusinessRegister\Source\StubSource;
use App\Test\Traits\ConfigureTestTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Entity\Customer Test Case
 */
#[CoversClass(Customer::class)]
class CustomerTest extends TestCase
{
    use ConfigureTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        StubSource::reset();
        Cache::clear('business_register');

        $this->withConfigure([
            // the customer number is built from the nid and the configured series
            'Customers.series' => 0,
            // the registers are stood in for, so nothing here reaches the network
            'BusinessRegister.sources' => ['stub' => StubSource::class],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    public function tearDown(): void
    {
        $this->restoreConfigure();
        Cache::clear('business_register');
        StubSource::reset();

        parent::tearDown();
    }

    /**
     * Test that all name parts are joined in order.
     *
     * @return void
     * @link \App\Model\Entity\Customer::_getFullName()
     */
    public function testFullNameJoinsAllParts(): void
    {
        $customer = new Customer([
            'title' => 'Ing.',
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'suffix' => 'Ph.D.',
        ]);

        $this->assertSame('Ing. Jan Novak Ph.D.', $customer->full_name);
    }

    /**
     * Test that missing name parts leave no double or leading space.
     *
     * @return void
     * @link \App\Model\Entity\Customer::_getFullName()
     */
    public function testFullNameSkipsMissingParts(): void
    {
        $customer = new Customer([
            'first_name' => 'Jan',
            'last_name' => 'Novak',
        ]);

        $this->assertSame('Jan Novak', $customer->full_name);
    }

    /**
     * Test that a company is bracketed and put before the person.
     *
     * @return void
     * @link \App\Model\Entity\Customer::_getName()
     */
    public function testNameCombinesCompanyAndPerson(): void
    {
        $customer = new Customer([
            'company' => 'NETAIR, s.r.o.',
            'first_name' => 'Jan',
            'last_name' => 'Novak',
        ]);

        $this->assertSame('[NETAIR, s.r.o.] Jan Novak', $customer->name);
    }

    /**
     * Test that a company without a person leaves no trailing space.
     *
     * @return void
     * @link \App\Model\Entity\Customer::_getName()
     */
    public function testNameWithCompanyOnly(): void
    {
        $customer = new Customer([
            'company' => 'NETAIR, s.r.o.',
        ]);

        $this->assertSame('[NETAIR, s.r.o.]', $customer->name);
    }

    /**
     * Test that lists put the surname first and the degrees behind a comma.
     *
     * @return void
     * @link \App\Model\Entity\Customer::_getNameForLists()
     */
    public function testNameForListsOrdersPartsForSorting(): void
    {
        $customer = new Customer([
            'nid' => 1234,
            'company' => 'NETAIR, s.r.o.',
            'title' => 'Ing.',
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'suffix' => 'Ph.D.',
        ]);

        $this->assertSame('[NETAIR, s.r.o.] Novak Jan, Ing., Ph.D. (1234)', $customer->name_for_lists);
    }

    /**
     * Test that degrees alone are not preceded by a comma.
     *
     * @return void
     * @link \App\Model\Entity\Customer::_getNameForLists()
     */
    public function testNameForListsWithDegreesOnly(): void
    {
        $customer = new Customer([
            'nid' => 1234,
            'title' => 'Ing.',
            'suffix' => 'Ph.D.',
        ]);

        $this->assertSame('Ing., Ph.D. (1234)', $customer->name_for_lists);
    }

    /**
     * Test that a person without degrees gets no trailing comma.
     *
     * @return void
     * @link \App\Model\Entity\Customer::_getNameForLists()
     */
    public function testNameForListsWithPersonOnly(): void
    {
        $customer = new Customer([
            'nid' => 1234,
            'first_name' => 'Jan',
            'last_name' => 'Novak',
        ]);

        $this->assertSame('Novak Jan (1234)', $customer->name_for_lists);
    }

    /**
     * Test that the customer number is offset by the configured series.
     *
     * @return void
     * @link \App\Model\Entity\Customer::_getNumber()
     */
    public function testNumberAddsTheConfiguredSeries(): void
    {
        $this->withConfigure(['Customers.series' => 100000]);

        $customer = new Customer([
            'nid' => 1234,
        ]);

        $this->assertSame('101234', $customer->number);
    }

    /**
     * The customer knows where their own number can be looked up, so a template asking for the
     * link does not have to know which register that is.
     *
     * @return void
     * @link \App\Model\Entity\Customer::identityNumberPortalUrl()
     */
    public function testIdentityNumberPortalUrlIsOfferedForANumberThatHoldsUp(): void
    {
        $customer = new Customer(['identity_number' => '27496139']);

        $this->assertSame(
            'https://ares.gov.cz/ekonomicke-subjekty?ico=27496139',
            $customer->identityNumberPortalUrl(),
        );
    }

    /**
     * A number that does not hold up, and a customer without one, get no link - following one
     * would only land on an empty result.
     *
     * @return void
     * @link \App\Model\Entity\Customer::identityNumberPortalUrl()
     */
    public function testIdentityNumberPortalUrlIsNotOfferedWithoutAValidNumber(): void
    {
        $this->assertNull((new Customer(['identity_number' => '12345678']))->identityNumberPortalUrl());
        $this->assertNull((new Customer())->identityNumberPortalUrl());
    }

    /**
     * The customer asks the registers about their own numbers, so a template, a command or a
     * report can all get the same answer without knowing which register that is.
     *
     * @return void
     * @link \App\Model\Entity\Customer::identityNumberCheck()
     * @link \App\Model\Entity\Customer::vatNumberCheck()
     */
    public function testTheCustomerAsksTheRegistersAboutTheirOwnNumbers(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'company' => 'NETAIR, s.r.o.'],
        ];
        StubSource::$vatNumberCheck = new VatNumberCheck(VatNumberStatus::Registered, 'NETAIR, s.r.o.');

        $customer = new Customer([
            'identity_number' => '27496139',
            'vat_number' => 'CZ27496139',
        ]);

        $this->assertSame('NETAIR, s.r.o.', $customer->identityNumberCheck()?->company);
        $this->assertSame(VatNumberStatus::Registered, $customer->vatNumberCheck()?->status);
    }

    /**
     * A number that fails its own check digit is not asked about at all - no register holds it,
     * and the check digit already said so without anyone being asked.
     *
     * @return void
     * @link \App\Model\Entity\Customer::identityNumberCheck()
     */
    public function testANumberThatFailsItsCheckDigitIsNotAskedAbout(): void
    {
        StubSource::$unreachableOnReference = true;

        $customer = new Customer(['identity_number' => '12345678']);

        $this->assertNull($customer->identityNumberCheck());
    }
}
