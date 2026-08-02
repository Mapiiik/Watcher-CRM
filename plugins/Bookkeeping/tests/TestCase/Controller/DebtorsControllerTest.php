<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Bookkeeping\Controller\DebtorsController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\Controller\DebtorsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(DebtorsController::class)]
class DebtorsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

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
        'app.Queues',
        'app.Services',
        'app.Billings',
        'plugin.Bookkeeping.Invoices',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \Bookkeeping\Controller\DebtorsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/bookkeeping/debtors');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the thresholds filled in, which is what decides who counts as a
     * debtor at all rather than merely how the answer is presented.
     *
     * @return void
     * @link \Bookkeeping\Controller\DebtorsController::index()
     */
    public function testIndexWithThresholds(): void
    {
        $this->login();
        $this->get('/bookkeeping/debtors?allowed_payment_delay=14&allowed_total_overdue_debt=100');

        $this->assertResponseOk();
    }
}
