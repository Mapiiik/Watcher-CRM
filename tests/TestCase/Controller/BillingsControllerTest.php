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
     * A wrong outer id is corrected while a right inner one is left standing.
     *
     * @return void
     * @link \App\Controller\AppController::beforeFilter()
     */
    public function testViewUnderAnotherCustomerKeepsTheContractItDoesBelongTo(): void
    {
        // a billing of that contract rather than whichever comes first: the contract in the URL is
        // the one the redirect is expected to keep, so it has to be the record's own
        $id = $this->getTableLocator()->get('Billings')
            ->find()
            ->where(['Billings.contract_id' => self::CONTRACT_ID])
            ->firstOrFail()
            ->get('id');

        $this->login();
        $this->get(
            '/customers/ae128a49-82fd-4b80-921f-f11af75fd113/contracts/' . self::CONTRACT_ID
            . '/billings/view/' . $id,
        );

        $this->assertRedirect(
            '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/billings/view/' . $id,
        );
    }

    /**
     * The form for a new record under a contract that is not there drops the nesting.
     *
     * This is what a bookmark turns into once the contract behind it is deleted. Left alone, the
     * form would fill the dead id in and the save fail on `existsIn`, complaining about a field the
     * form does not render - which reads as no reason at all.
     *
     * @return void
     * @link \App\Controller\AppController::beforeFilter()
     */
    public function testAddUnderAContractThatIsGoneDropsTheNesting(): void
    {
        $this->login();
        $this->get(
            '/customers/' . self::CUSTOMER_ID
            . '/contracts/00000000-0000-4000-8000-000000000000/billings/add',
        );

        $this->assertRedirect('/customers/' . self::CUSTOMER_ID . '/billings/add');
    }

    /**
     * A customer that is not there takes the contract nested under it along.
     *
     * There is no route reaching a contract without its customer, so keeping the inner id would
     * name an address that does not exist.
     *
     * @return void
     * @link \App\Controller\AppController::beforeFilter()
     */
    public function testAddUnderACustomerThatIsGoneDropsTheNestingWhole(): void
    {
        $this->login();
        $this->get(
            '/customers/00000000-0000-4000-8000-000000000000/contracts/' . self::CONTRACT_ID
            . '/billings/add',
        );

        $this->assertRedirect('/billings/add');
    }

    /**
     * The listing under a customer that is not there drops the nesting as well - it is the address
     * that is wrong, not the action asked for.
     *
     * @return void
     * @link \App\Controller\AppController::beforeFilter()
     */
    public function testIndexUnderACustomerThatIsGoneDropsTheNesting(): void
    {
        $this->login();
        $this->get('/customers/00000000-0000-4000-8000-000000000000/billings');

        $this->assertRedirect('/billings');
    }

    /**
     * Asked for under the customer and contract it belongs to, the record is answered there.
     *
     * @return void
     * @link \App\Controller\AppController::beforeFilter()
     */
    public function testViewUnderItsOwnRouteIsAnsweredThere(): void
    {
        // a billing of that contract rather than whichever comes first: the fixtures carry
        // billings on more than one contract, and one filed elsewhere is redirected away from
        // this address rather than answered at it
        $billingId = $this->getTableLocator()->get('Billings')
            ->find()
            ->where(['Billings.contract_id' => self::CONTRACT_ID])
            ->firstOrFail()
            ->get('id');

        $this->login();
        $this->get(
            '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/billings/view/'
            . $billingId,
        );

        $this->assertResponseOk();
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\BillingsController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $billingId = $this->firstId('Billings');
        $this->post('/billings/edit/' . $billingId, ['text' => 'Renamed item']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed item',
            $this->getTableLocator()->get('Billings')->get($billingId)->text,
        );
    }

    /**
     * The roles that write billings may now take one back, which they could not before - but only
     * while nobody has been invoiced for it.
     *
     * @return void
     * @link \App\Controller\BillingsController::delete()
     */
    public function testABillingNobodyHasBeenInvoicedForMayBeDeletedByWhoeverWritesThem(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $billing = $billings->saveOrFail($billings->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'text' => 'Not invoiced for yet',
            'quantity' => 1,
            'separate_invoice' => false,
            'billing_from' => $billings->firstOpenPeriodStart()->toDateString(),
        ]));

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/delete/' . $billing->id);

        $this->assertRedirect();
        $this->assertFalse($billings->exists(['id' => $billing->id]));
    }

    /**
     * What has been invoiced for stays, whoever asks - short of an admin, who is trusted with the
     * whole application anyway.
     *
     * @return void
     * @link \App\Controller\BillingsController::delete()
     */
    public function testABillingAlreadyInvoicedForIsNotTheirsToDelete(): void
    {
        $billingId = $this->firstId('Billings');

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/delete/' . $billingId);

        $this->assertTrue($this->getTableLocator()->get('Billings')->exists(['id' => $billingId]));
    }

    /**
     * The admin keeps the reach they have always had.
     *
     * @return void
     * @link \App\Controller\BillingsController::delete()
     */
    public function testAnAdminDeletesABillingWhateverItsDates(): void
    {
        $billingId = $this->firstId('Billings');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/delete/' . $billingId);

        $this->assertRedirect();
        $this->assertFalse($this->getTableLocator()->get('Billings')->exists(['id' => $billingId]));
    }

    /**
     * A start lying in a period that has been invoiced for is not to be moved, and the form says
     * so rather than saving something the invoices do not agree with.
     *
     * @return void
     * @link \App\Controller\BillingsController::edit()
     */
    public function testTheFormRefusesToMoveAStartThatHasBeenInvoicedFor(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $billingId = $this->firstId('Billings');
        $was = $billings->get($billingId)->billing_from;

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/edit/' . $billingId, [
            'billing_from' => $billings->firstOpenPeriodStart()->toDateString(),
        ]);

        $this->assertResponseOk();
        $this->assertTrue($was->equals($billings->get($billingId)->billing_from));
    }

    /**
     * And the admin may say they mean it, which is the whole of what the box on the form does.
     *
     * @return void
     * @link \App\Controller\BillingsController::edit()
     */
    public function testAnAdminTickingTheBoxMovesItAnyway(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $billingId = $billings
            ->find()
            ->where(['Billings.billing_until IS' => null])
            ->firstOrFail()
            ->get('id');
        $moved = $billings->firstOpenPeriodStart();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/edit/' . $billingId, [
            'billing_from' => $moved->toDateString(),
            'allow_closed_periods' => '1',
        ]);

        $this->assertRedirect();
        $this->assertTrue($moved->equals($billings->get($billingId)->billing_from));
    }
}
