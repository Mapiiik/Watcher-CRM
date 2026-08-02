<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\View\Cell;

use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\View\Cell\AccountsCell;

/**
 * Radius\View\Cell\AccountsCell Test Case
 *
 * The cell lists the RADIUS accounts of whatever it is given conditions for, and is rendered on the
 * customer and the contract detail. It is exercised through one of those pages: its links go
 * through `AuthLink`, which asks the authorization service in the request whether to render each
 * one, and outside a request there is nothing to ask.
 */
#[UsesClass(AccountsCell::class)]
class AccountsCellTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Customer the fixture RADIUS account belongs to.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

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
        'app.ContractVersions',
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.Emails',
        'app.Labels',
        'app.CustomerLabels',
        'app.Logins',
        'app.Phones',
        'app.SoldEquipments',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
        'app.DealerCommissions',
        'plugin.Radius.Accounts',
        'plugin.Radius.Radcheck',
        'plugin.Radius.Radreply',
        'plugin.Radius.Radusergroup',
        'plugin.Radius.Radacct',
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

        $this->login();
    }

    /**
     * The accounts of the customer are listed, with the columns the operator works from.
     *
     * @return void
     * @link \Radius\View\Cell\AccountsCell::display()
     */
    public function testDisplay(): void
    {
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains(__d('radius', 'Username'));
        $this->assertResponseContains(__d('radius', 'Network Access Server'));

        $username = $this->getTableLocator()->get('Radius.Accounts')->find()->firstOrFail()->get('username');
        $this->assertResponseContains(h($username));
    }

    /**
     * A customer with no RADIUS account gets no table rather than an empty one.
     *
     * @return void
     * @link \Radius\View\Cell\AccountsCell::display()
     */
    public function testDisplayWithoutAnyAccounts(): void
    {
        $this->getTableLocator()->get('Radius.Accounts')->deleteAll([]);

        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseNotContains(__d('radius', 'Network Access Server'));
    }
}
