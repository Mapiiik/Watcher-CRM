<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Bookkeeping\Controller\DebtorsController;
use Bookkeeping\Debtors\DebtorsProcessor;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;

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
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.Emails',
        'app.Phones',
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
     * A contact is whatever somebody wrote down, so it is shown as text.
     *
     * @return void
     * @link \Bookkeeping\Controller\DebtorsController::index()
     */
    public function testTheContactsOfADebtorAreText(): void
    {
        $this->contactsWithMarkup();
        $this->login();

        // the processor keeps what it loaded in a static, which outlives the test that loaded it
        $loaded = new ReflectionProperty(DebtorsProcessor::class, 'debtors');
        $loaded->setValue(null, null);

        $this->get('/bookkeeping/debtors?allowed_payment_delay=0&allowed_total_overdue_debt=0');
        $loaded->setValue(null, null);

        $this->assertResponseOk();
        $this->assertContactsAreText();
    }

    /**
     * Contacts that would come out as markup if they were not escaped.
     *
     * @return void
     */
    private function contactsWithMarkup(): void
    {
        $this->fetchTable('Emails')->updateAll(['email' => '<i>mail</i>'], ['1 = 1']);
        $this->fetchTable('Phones')->updateAll(['phone' => '<i>phone</i>'], ['1 = 1']);
    }

    /**
     * What was written as a contact is on the page as text.
     *
     * @return void
     */
    private function assertContactsAreText(): void
    {
        $this->assertResponseContains('&lt;i&gt;mail&lt;/i&gt;');
        $this->assertResponseContains('&lt;i&gt;phone&lt;/i&gt;');
        $this->assertResponseNotContains('<i>mail</i>');
        $this->assertResponseNotContains('<i>phone</i>');
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
