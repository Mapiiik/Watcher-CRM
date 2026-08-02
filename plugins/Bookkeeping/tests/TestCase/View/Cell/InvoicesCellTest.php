<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\View\Cell;

use App\Test\Traits\ControllerTestTrait;
use Bookkeeping\View\Cell\InvoicesCell;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\View\Cell\InvoicesCell Test Case
 *
 * The cell lists the invoices of whatever it is given conditions for, and is rendered on the
 * customer and the contract detail. It is exercised through one of those pages: its links go
 * through `AuthLink`, which asks the authorization service in the request whether to render each
 * one, and outside a request there is nothing to ask.
 */
#[UsesClass(InvoicesCell::class)]
class InvoicesCellTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Customer the fixture invoice belongs to.
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
        'plugin.Bookkeeping.Invoices',
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
     * The invoices of the customer are listed, by the number they are referred to by.
     *
     * On their own page the customer column is left out - it would repeat whose page it is on
     * every row.
     *
     * @return void
     * @link \Bookkeeping\View\Cell\InvoicesCell::display()
     */
    public function testDisplay(): void
    {
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains(__d('bookkeeping', 'Variable Symbol'));
        $this->assertResponseNotContains(__d('bookkeeping', 'Customer Number'));

        $number = $this->getTableLocator()->get('Bookkeeping.Invoices')->find()->firstOrFail()->get('number');
        $this->assertResponseContains(h($number));
    }

    /**
     * A customer with no invoice gets no table rather than an empty one.
     *
     * @return void
     * @link \Bookkeeping\View\Cell\InvoicesCell::display()
     */
    public function testDisplayWithoutAnyInvoices(): void
    {
        $this->getTableLocator()->get('Bookkeeping.Invoices')->deleteAll([]);

        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseNotContains(__d('bookkeeping', 'Variable Symbol'));
    }
}
