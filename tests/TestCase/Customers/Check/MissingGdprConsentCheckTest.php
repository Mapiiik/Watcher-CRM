<?php
declare(strict_types=1);

namespace App\Test\TestCase\Customers\Check;

use App\Check\CheckScope;
use App\Customers\Check\MissingGdprConsentCheck;
use App\Model\Table\CustomersTable;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Customers\Check\MissingGdprConsentCheck Test Case
 */
#[UsesClass(MissingGdprConsentCheck::class)]
class MissingGdprConsentCheckTest extends TestCase
{
    /**
     * The customer of the fixtures.
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

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
        'app.Emails',
        'app.Labels',
        'app.CustomerLabels',
        'plugin.Settings.Settings',
    ];

    private CustomersTable $Customers;

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->Customers = $this->getTableLocator()->get('Customers', ['className' => CustomersTable::class]);
    }

    /**
     * Nothing said about the consent is no consent: the comparison used to leave such a customer out.
     *
     * @return void
     * @link \App\Customers\Check\MissingGdprConsentCheck::find()
     */
    public function testACustomerWhoNeverAnsweredIsReported(): void
    {
        $this->Customers->updateAll(['agree_gdpr' => null], ['id' => self::CUSTOMER_ID]);

        $this->assertContains(self::CUSTOMER_ID, $this->found());
    }

    /**
     * A consent given is the end of it.
     *
     * @return void
     * @link \App\Customers\Check\MissingGdprConsentCheck::find()
     */
    public function testACustomerWhoAgreedIsNotReported(): void
    {
        $this->Customers->updateAll(['agree_gdpr' => true], ['id' => self::CUSTOMER_ID]);

        $this->assertNotContains(self::CUSTOMER_ID, $this->found());
    }

    /**
     * The ids the check finds.
     *
     * @return list<string>
     */
    private function found(): array
    {
        /** @var list<string> */
        return (new MissingGdprConsentCheck($this->Customers, new CheckScope()))
            ->find()
            ->all()
            ->extract('id')
            ->toList();
    }
}
