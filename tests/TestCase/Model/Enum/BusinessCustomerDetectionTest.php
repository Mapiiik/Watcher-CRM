<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Enum;

use App\Model\Entity\Customer;
use App\Model\Enum\BusinessCustomerDetection;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Enum\BusinessCustomerDetection Test Case
 *
 * The same customer is a household in Croatia and a business in Czechia, depending on what tells.
 */
#[CoversClass(BusinessCustomerDetection::class)]
class BusinessCustomerDetectionTest extends TestCase
{
    /**
     * A person with an identity number and no company.
     *
     * @return void
     * @link \App\Model\Entity\Customer::isBusiness()
     */
    public function testEachCountryTellsItsOwnWay(): void
    {
        $person = new Customer(['identity_number' => '12345678903', 'company' => null]);
        $company = new Customer(['identity_number' => '12345678903', 'company' => 'Multi d.o.o.']);

        $this->assertTrue($person->isBusiness(BusinessCustomerDetection::IdentityNumber));
        $this->assertFalse($person->isBusiness(BusinessCustomerDetection::Company));
        $this->assertTrue($company->isBusiness(BusinessCustomerDetection::Company));
        $this->assertFalse((new Customer(['company' => '  ']))->isBusiness(BusinessCustomerDetection::Company));
    }

    /**
     * A setting that says nothing known keeps the Czech way, as it was before there was a choice.
     *
     * @return void
     * @link \App\Model\Enum\BusinessCustomerDetection::fromSetting()
     */
    public function testAnUnknownSettingKeepsTheIdentityNumber(): void
    {
        $this->assertSame(BusinessCustomerDetection::IdentityNumber, BusinessCustomerDetection::fromSetting(null));
        $this->assertSame(BusinessCustomerDetection::IdentityNumber, BusinessCustomerDetection::fromSetting('x'));
        $this->assertSame(BusinessCustomerDetection::Company, BusinessCustomerDetection::fromSetting('company'));
    }
}
