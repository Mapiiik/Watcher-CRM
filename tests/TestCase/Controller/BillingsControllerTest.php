<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\BillingsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\BillingsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(BillingsController::class)]
class BillingsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Customer the nested routes hang off.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract the nested routes hang off.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

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
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\BillingsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/billings');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\BillingsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/billings?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\BillingsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/billings/view/' . $this->firstId('Billings'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\BillingsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/billings/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\BillingsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/billings/edit/' . $this->firstId('Billings'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\BillingsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/delete/' . $this->firstId('Billings'));

        $this->assertRedirect();
    }

    /**
     * Added under its customer and the contract, the record is filed under them without the form saying so.
     *
     * The form under a customer and the contract leaves those fields out - the route already says which record it is,
     * and the controller fills them in. Posting them in the body instead, as a test reaching the
     * flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \App\Controller\BillingsController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('Billings');
        $this->post('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/billings/add', [
            'text' => 'Nested item',
            'price' => '100.00',
            'billing_from' => '2026-08-05',
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('Billings', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
        $this->assertSame(self::CONTRACT_ID, $added->get('contract_id'));
    }

    /**
     * Asked for under a customer the record does not belong to, it is answered under the one it
     * does.
     *
     * The nested routes match any id against any record, so such a URL used to render the record
     * under a heading naming a customer it has nothing to do with. It is not an error - the record
     * exists and the caller is welcome to it - so the caller is sent to it.
     *
     * @return void
     * @link \App\Controller\AppController::beforeFilter()
     */
    public function testViewUnderAnotherCustomerRedirectsToItsOwn(): void
    {
        $id = $this->firstId('Billings');
        $this->login();
        $this->get('/customers/ae128a49-82fd-4b80-921f-f11af75fd113/billings/view/' . $id);

        $this->assertRedirect('/customers/' . self::CUSTOMER_ID . '/billings/view/' . $id);
    }

    /**
     * Both ids the route carries are read, not only the outer one.
     *
     * @return void
     * @link \App\Controller\AppController::beforeFilter()
     */
    public function testViewUnderAnotherContractRedirectsToItsOwn(): void
    {
        $id = $this->firstId('Billings');
        $this->login();
        $this->get(
            '/customers/' . self::CUSTOMER_ID
            . '/contracts/00000000-0000-4000-8000-000000000000/billings/view/' . $id,
        );

        $this->assertRedirect(
            '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/billings/view/' . $id,
        );
    }

    /**
     * Asked for under the customer and contract it belongs to, the record is answered there.
     *
     * @return void
     * @link \App\Controller\AppController::beforeFilter()
     */
    public function testViewUnderItsOwnRouteIsAnsweredThere(): void
    {
        $this->login();
        $this->get(
            '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/billings/view/'
            . $this->firstId('Billings'),
        );

        $this->assertResponseOk();
    }
}
