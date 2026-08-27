<?php
declare(strict_types=1);

namespace App\Test\TestCase\Customers\Check;

use App\Customers\Check\MissingEmailCheck;
use App\Model\Table\CustomersTable;
use Cake\Cache\Cache;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Settings\Utility\Settings;

/**
 * App\Customers\Check\MissingEmailCheck Test Case
 *
 * Everything the application sends on its own goes by e-mail, so a customer without one only
 * hears from us when somebody picks up the telephone - unless they have been asked already and
 * said no, which is what the label saying so is for.
 */
#[UsesClass(MissingEmailCheck::class)]
class MissingEmailCheckTest extends TestCase
{
    use LocatorAwareTrait;

    /**
     * The customer the fixtures give an e-mail address and a label.
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * The label they carry, whatever it is called.
     */
    private const LABEL_ID = 'e9cbb697-be8b-4e05-8226-1b0aeb53130d';

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
     * @return void
     * @link \App\Customers\Check\MissingEmailCheck::find()
     */
    public function testACustomerWithAnAddressIsNotReported(): void
    {
        $this->assertSame([], $this->found());
    }

    /**
     * @return void
     * @link \App\Customers\Check\MissingEmailCheck::find()
     */
    public function testACustomerWithNoAddressAtAllIsReported(): void
    {
        $this->getTableLocator()->get('Emails')->deleteAll(['customer_id' => self::CUSTOMER_ID]);

        $this->assertCount(1, $this->found());
    }

    /**
     * A row with nothing written in it is the same as no row at all, and would otherwise say
     * the customer can be reached when they cannot.
     *
     * @return void
     * @link \App\Customers\Check\MissingEmailCheck::customersWhoHaveOne()
     */
    public function testARowWithNothingInItDoesNotCountAsAnAddress(): void
    {
        $this->getTableLocator()->get('Emails')->updateAll(['email' => ''], ['customer_id' => self::CUSTOMER_ID]);

        $this->assertCount(1, $this->found());
    }

    /**
     * Somebody has already asked and been told no. Reporting it again every week is asking the
     * same question of the same person over and over.
     *
     * @return void
     * @link \App\Customers\Check\AbstractCustomerCheck::excusedBy()
     */
    public function testACustomerWhoHasSaidNoIsLetOff(): void
    {
        $this->getTableLocator()->get('Emails')->deleteAll(['customer_id' => self::CUSTOMER_ID]);
        $this->labelledAs('No e-mail');

        Settings::set('core.customers.checks.missing_email_excused_by', ['No e-mail']);

        $this->assertSame([], $this->found());
    }

    /**
     * Left empty, the setting excuses nobody. As an `IN` over no values it would quietly say
     * the opposite and empty the check.
     *
     * @return void
     * @link \App\Customers\Check\AbstractCustomerCheck::excusedBy()
     */
    public function testNamingNoLabelsLetsNobodyOff(): void
    {
        $this->getTableLocator()->get('Emails')->deleteAll(['customer_id' => self::CUSTOMER_ID]);
        $this->labelledAs('No e-mail');

        Settings::set('core.customers.checks.missing_email_excused_by', []);

        $this->assertCount(1, $this->found());
    }

    /**
     * Run the check and return what it found.
     *
     * @return list<\App\Model\Entity\Customer>
     */
    private function found(): array
    {
        /** @var list<\App\Model\Entity\Customer> $records */
        $records = (new MissingEmailCheck($this->Customers, true, self::CUSTOMER_ID))->find()->all()->toList();

        return $records;
    }

    /**
     * Rename the label the customer already carries, so the case can name it in the settings.
     *
     * @param string $name What the label is called.
     * @return void
     */
    private function labelledAs(string $name): void
    {
        $this->getTableLocator()->get('Labels')->updateAll(['name' => $name], ['id' => self::LABEL_ID]);
    }
}
