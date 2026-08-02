<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Bookkeeping\Controller\InvoicesController;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Bookkeeping\Controller\InvoicesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(InvoicesController::class)]
class InvoicesControllerTest extends TestCase
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
        'plugin.Bookkeeping.Invoices',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \Bookkeeping\Controller\InvoicesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/bookkeeping/invoices');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \Bookkeeping\Controller\InvoicesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/bookkeeping/invoices?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \Bookkeeping\Controller\InvoicesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/bookkeeping/invoices/view/' . $this->firstId('Bookkeeping.Invoices'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \Bookkeeping\Controller\InvoicesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/bookkeeping/invoices/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \Bookkeeping\Controller\InvoicesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/bookkeeping/invoices/edit/' . $this->firstId('Bookkeeping.Invoices'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \Bookkeeping\Controller\InvoicesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/bookkeeping/invoices/delete/' . $this->firstId('Bookkeeping.Invoices'));

        $this->assertRedirect();
    }
}
