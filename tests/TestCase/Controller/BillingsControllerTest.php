<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\BillingsController;
use App\Model\Entity\Billing;
use App\Model\Table\BillingsTable;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
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
     * Service a bulk change is asked to leave.
     *
     * @var string
     */
    private const BULK_ORIGINAL_SERVICE_ID = 'eaacfeb3-1430-43ce-842e-497c5c95d953';

    /**
     * Service a bulk change is asked to move to.
     *
     * @var string
     */
    private const BULK_NEW_SERVICE_ID = '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2';

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
        'app.CustomerMessages',
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
     * An admin or a manager removes a billing somebody has been invoiced for, and the request
     * carries the override that gets it past the table's rule.
     *
     * Whether anybody else is even offered the button is the permissions' answer - they ask
     * `mayBeDeleted()` themselves - so it is not asked again here.
     *
     * @param string $role The role asking.
     * @return void
     * @link \App\Controller\BillingsController::delete()
     */
    #[DataProvider('rolesOfferedTheBoxes')]
    public function testRemovingABillingInvoicedFor(string $role): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $invoiced = $this->closedBillingId();

        $this->login($role);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/delete/' . $invoiced);

        $this->assertRedirect();
        $this->assertFalse($billings->exists(['id' => $invoiced]));
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
            // taken from today rather than written down: a day in a month already invoiced for is
            // refused, and a day written down becomes one as soon as the month turns
            'billing_from' => $this->getTableLocator()->get('Billings')->firstOpenPeriodStart()->toDateString(),
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
     * The roles offered the boxes that override what the others are held to.
     *
     * @return array<string, array{string}>
     */
    public static function rolesOfferedTheBoxes(): array
    {
        return [
            'admin' => ['admin'],
            'manager' => ['manager'],
        ];
    }

    /**
     * And an admin or a manager may say they mean it, which is the whole of what the box on the
     * form does.
     *
     * @param string $role The role asking.
     * @return void
     * @link \App\Controller\BillingsController::edit()
     */
    #[DataProvider('rolesOfferedTheBoxes')]
    public function testTickingTheBoxMovesItAnyway(string $role): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $billingId = $billings
            ->find()
            ->where(['Billings.billing_until IS' => null])
            ->firstOrFail()
            ->get('id');
        $moved = $billings->firstOpenPeriodStart();

        $this->login($role);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/edit/' . $billingId, [
            'billing_from' => $moved->toDateString(),
            'allow_closed_periods' => '1',
        ]);

        $this->assertRedirect();
        $this->assertTrue($moved->equals($billings->get($billingId)->billing_from));
    }

    /**
     * The way out of a settled billing is to end it and start another, and the roles that write
     * billings may now walk it themselves - before, only an admin could.
     *
     * @return void
     * @link \App\Controller\BillingsController::serviceChange()
     */
    public function testWhoeverWritesBillingsMayChangeTheServiceOfARunningOne(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $billing = $billings->find()->where(['Billings.billing_until IS' => null])->firstOrFail();
        $from = $billings->firstOpenPeriodStart();
        $service = $billings->Services
            ->find()
            ->where(['Services.id IS NOT' => $billing->get('service_id')])
            ->firstOrFail();

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('Billings');
        // deliberately without the money: the replacement is the original with the form laid
        // over it, so what the form leaves out has to be carried across
        $this->post('/billings/service-change/' . $billing->get('id'), [
            'service_id' => $service->get('id'),
            'billing_from' => $from->toDateString(),
        ]);

        $this->assertRedirectContains('/billings/view/');
        $this->assertEquals(
            $from->subDays(1),
            $billings->get($billing->get('id'))->billing_until,
            'The billing being replaced was not ended the day before its replacement starts.',
        );

        $started = $this->addedRecord('Billings', $before);
        $this->assertSame($service->get('id'), $started->get('service_id'));
        $this->assertEquals($from, $started->get('billing_from'));
        $this->assertEquals($billing->get('price'), $started->get('price'), 'The price was not carried across.');
    }

    /**
     * The letter itself, which has never been written: the action it hangs off was dormant for
     * years and then refused everything, so this branch has not run once.
     *
     * @return void
     * @link \App\Controller\BillingsController::serviceChange()
     */
    public function testTheCustomerNotificationIsActuallyWritten(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $billing = $billings->find()->where(['Billings.billing_until IS' => null])->firstOrFail();
        $service = $billings->Services
            ->find()
            ->where(['Services.id IS NOT' => $billing->get('service_id')])
            ->firstOrFail();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('CustomerMessages');
        $this->post('/billings/service-change/' . $billing->get('id'), [
            'service_id' => $service->get('id'),
            'billing_from' => $billings->firstOpenPeriodStart()->toDateString(),
            'send_customer_notification' => '1',
        ]);

        $this->assertRedirectContains('/billings/view/');

        $written = $this->addedRecord('CustomerMessages', $before);
        $this->assertNotEmpty($written->get('subject'));
        $this->assertNotEmpty($written->get('body'));
        $this->assertSame($billing->get('customer_id'), $written->get('customer_id'));
        $this->assertSame(['customer@example.com'], $written->get('recipients'));
    }

    /**
     * The letter goes out over the company's name, so the option is an admin's or a manager's -
     * and unticked even there, because a service is usually changed on the customer's own word.
     *
     * @param string $role The role asking.
     * @return void
     * @link \App\Controller\BillingsController::serviceChange()
     */
    #[DataProvider('rolesOfferedTheBoxes')]
    public function testTheCustomerNotificationIsOfferedToTheFewAndUnticked(string $role): void
    {
        $billingId = $this->firstId('Billings');

        $this->login('bookkeeper');
        $this->get('/billings/service-change/' . $billingId);

        $this->assertResponseOk();
        $this->assertResponseNotContains('send-customer-notification');
        $this->assertResponseNotContains('version-without-legislative-information');

        $this->login($role);
        $this->get('/billings/service-change/' . $billingId);

        $this->assertResponseOk();
        $this->assertResponseContains('send-customer-notification');
        $this->assertResponseNotContains('checked="checked"');
    }

    /**
     * Nor is the letter written for whoever is not offered the box, though the request asks for it.
     *
     * @return void
     * @link \App\Controller\BillingsController::serviceChange()
     */
    public function testTheCustomerNotificationIsNotWrittenForTheOthers(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $billing = $billings->find()->where(['Billings.billing_until IS' => null])->firstOrFail();
        $service = $billings->Services
            ->find()
            ->where(['Services.id IS NOT' => $billing->get('service_id')])
            ->firstOrFail();

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('CustomerMessages');
        $this->post('/billings/service-change/' . $billing->get('id'), [
            'service_id' => $service->get('id'),
            'billing_from' => $billings->firstOpenPeriodStart()->toDateString(),
            'send_customer_notification' => '1',
        ]);

        // the change itself goes through, only the letter does not
        $this->assertRedirectContains('/billings/view/');
        $this->assertSame($before, $this->idsIn('CustomerMessages'));
    }

    /**
     * Changing the prices of hundreds of contracts at one submission stays the admin's.
     *
     * @return void
     * @link \App\Controller\BillingsController::bulkServiceChange()
     */
    public function testTheBulkServiceChangeIsNotOpenedAlongWithTheSingleOne(): void
    {
        $this->login('bookkeeper');
        $this->get('/billings/bulk-service-change');

        $this->assertRedirect();
        $this->assertNull($this->viewVariable('billings'), 'The bulk form was rendered for them.');
    }

    /**
     * A new tariff from a day on: what ran on the old one ends the day before and goes on under the
     * new one, with everything that was its own, and the fixed end of a billing is kept.
     *
     * @return void
     * @link \App\Controller\BillingsController::bulkServiceChange()
     */
    public function testTheBulkChangeCarriesTheBillingsOverToTheNewService(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $from = $billings->firstOpenPeriodStart();

        [$open, $fixed] = $this->billedCustomers(2);
        $fixedUntil = $from->addYears(1)->toDateString();
        $billings->updateAll(['billing_until' => $fixedUntil], ['id' => $fixed['billing_id']]);
        $billings->updateAll(['note' => 'Kept with the billing', 'quantity' => 2], ['id' => $open['billing_id']]);

        $this->bulkChange(['active_on_date' => $from->toDateString()], [
            'billing_from' => $from->toDateString(),
        ]);

        $this->assertFlashElement('flash/success');
        foreach ([$open, $fixed] as $customer) {
            $this->assertSame(
                $from->subDays(1)->toDateString(),
                $billings->get($customer['billing_id'])->get('billing_until')->toDateString(),
            );
        }

        $replacement = $this->replacementOn($open['contract_id']);
        $this->assertSame($from->toDateString(), $replacement->get('billing_from')->toDateString());
        $this->assertNull($replacement->get('billing_until'));
        $this->assertSame($open['customer_id'], $replacement->get('customer_id'));
        $this->assertSame('Kept with the billing', $replacement->get('note'));
        $this->assertSame(2, $replacement->get('quantity'));

        $this->assertSame(
            $fixedUntil,
            $this->replacementOn($fixed['contract_id'])->get('billing_until')->toDateString(),
        );
    }

    /**
     * Only what the form listed is changed: a price of its own stays out unless it is asked for, and
     * asked for by the price, the listing and the change agree.
     *
     * @return void
     * @link \App\Controller\BillingsController::bulkServiceChange()
     */
    public function testTheBulkChangeTakesBillingsOfAPriceOfTheirOwnOnlyWhenAskedTo(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $from = $billings->firstOpenPeriodStart();

        [$standard, $discounted] = $this->billedCustomers(2);
        $billings->updateAll(['price' => '12.50'], ['id' => $discounted['billing_id']]);

        $this->bulkChange(['active_on_date' => $from->toDateString()], [
            'billing_from' => $from->toDateString(),
        ]);
        $this->assertNull($billings->get($discounted['billing_id'])->get('billing_until'));
        $this->assertNotNull($billings->get($standard['billing_id'])->get('billing_until'));

        $this->bulkChange(
            [
                'active_on_date' => $from->toDateString(),
                'standard_prices_only' => '0',
                'price' => '12.50',
                'empty_is_null' => '1',
            ],
            ['billing_from' => $from->toDateString(), 'price' => '10.25'],
        );

        $this->assertNotNull($billings->get($discounted['billing_id'])->get('billing_until'));
        $this->assertSame('10.25', $this->replacementOn($discounted['contract_id'])->get('price')->toString());
    }

    /**
     * The fibre reaching one block of flats: narrowed by the access point, the change stays there.
     *
     * @return void
     * @link \App\Controller\BillingsController::bulkServiceChange()
     */
    public function testTheBulkChangeStaysOnTheAccessPointAskedFor(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $from = $billings->firstOpenPeriodStart();

        [$here, $elsewhere] = $this->billedCustomers(2);
        $accessPointId = 'a0000000-0000-4000-8000-000000000001';
        $billings->Contracts->updateAll(['access_point_id' => $accessPointId], ['id' => $here['contract_id']]);

        $this->bulkChange(
            ['active_on_date' => $from->toDateString(), 'access_point_id' => $accessPointId],
            ['billing_from' => $from->toDateString()],
        );

        $this->assertNotNull($billings->get($here['billing_id'])->get('billing_until'));
        $this->assertNull($billings->get($elsewhere['billing_id'])->get('billing_until'));
    }

    /**
     * Taken in batches, every customer changed is written to at their own address.
     *
     * The limit is what makes this worth asking: it cuts the billings, and the customers' addresses
     * have to be read for those billings and not for as many customers as the limit says.
     *
     * @return void
     * @link \App\Controller\BillingsController::bulkServiceChange()
     */
    public function testEveryCustomerOfABatchIsToldAtTheirOwnAddress(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $from = $billings->firstOpenPeriodStart();

        $customers = $this->billedCustomers(6);

        $before = $this->idsIn('CustomerMessages');
        $this->bulkChange(
            ['active_on_date' => $from->toDateString(), 'processing_limit' => '3'],
            ['billing_from' => $from->toDateString(), 'send_customer_notification' => '1'],
        );

        $this->assertFlashElement('flash/success');
        $addresses = array_column($customers, 'email', 'customer_id');
        $messages = $this->getTableLocator()->get('CustomerMessages')
            ->find()
            ->where(['id NOT IN' => $before])
            ->all();

        $this->assertCount(3, $messages);
        foreach ($messages as $message) {
            $this->assertSame([$addresses[$message->get('customer_id')]], $message->get('recipients'));
        }
    }

    /**
     * Taken again before the day of the change, what has been changed still shows as running. It
     * must not be billed twice: the batch stops there and says on which contract.
     *
     * @return void
     * @link \App\Controller\BillingsController::bulkServiceChange()
     */
    public function testABatchTakenAgainDoesNotBillTwice(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $from = $billings->firstOpenPeriodStart()->addMonths(1);

        [$customer] = $this->billedCustomers(1);
        $filter = ['active_on_date' => $from->subDays(1)->toDateString()];
        $change = ['billing_from' => $from->toDateString()];

        $this->bulkChange($filter, $change);
        $this->assertFlashElement('flash/success');

        $this->bulkChange($filter, $change);
        $this->assertFlashMessage('The billing could not be saved. Please, try again. (Contract Number: BULK-0)');

        $this->assertSame(1, $billings->find()->where([
            'contract_id' => $customer['contract_id'],
            'service_id' => self::BULK_NEW_SERVICE_ID,
        ])->count());
    }

    /**
     * The form goes through with the tokens it was rendered with. The other tests here have theirs
     * made for them, so they would not notice a field the form protection turns away.
     *
     * @return void
     * @link \App\Controller\BillingsController::bulkServiceChange()
     */
    public function testTheBulkFormGoesThroughAsItWasRendered(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $from = $billings->firstOpenPeriodStart();
        [$customer] = $this->billedCustomers(1);

        $this->login();
        $this->enableCsrfToken();

        $url = '/billings/bulk-service-change?' . http_build_query([
            'original_service_id' => self::BULK_ORIGINAL_SERVICE_ID,
            'active_on_date' => $from->toDateString(),
            'processing_limit' => '100',
        ]);
        $this->get($url);
        $this->assertResponseOk();

        // the listing below has forms of its own, each with tokens of its own
        $form = (string)strstr((string)strstr($this->_getBodyAsString(), 'name="service_id"'), '</form>', true);
        preg_match_all('~name="_Token\[(\w+)\]"[^>]*value="([^"]*)"~', $form, $tokens, PREG_SET_ORDER);

        $this->post($url, [
            'service_id' => self::BULK_NEW_SERVICE_ID,
            'billing_from' => $from->toDateString(),
            'price' => '',
            'fixed_discount' => '',
            'percentage_discount' => '',
            'send_customer_notification' => '0',
            'version_without_legislative_information' => '0',
            'allow_below_minimum' => '0',
            '_Token' => array_column(
                array_map(fn(array $token): array => [$token[1], html_entity_decode($token[2])], $tokens),
                1,
                0,
            ),
        ]);

        $this->assertRedirect();
        $this->assertSame(
            $from->subDays(1)->toDateString(),
            $billings->get($customer['billing_id'])->get('billing_until')->toDateString(),
        );
    }

    /**
     * The form lists what the request narrowed it to.
     *
     * @return void
     * @link \App\Controller\BillingsController::bulkServiceChange()
     */
    public function testTheBulkFormListsWhatIsAboutToChange(): void
    {
        $from = $this->getTableLocator()->get('Billings')->firstOpenPeriodStart();
        $this->billedCustomers(3);

        $this->login();
        $this->get('/billings/bulk-service-change?' . http_build_query([
            'original_service_id' => self::BULK_ORIGINAL_SERVICE_ID,
            'active_on_date' => $from->toDateString(),
            'processing_limit' => '2',
        ]));

        $this->assertResponseOk();
        $this->assertCount(2, $this->viewVariable('billings'));
    }

    /**
     * A box that cannot be saved is shut rather than argued with afterwards - and told why, or
     * being shut is its own puzzle.
     *
     * Every box a rule would refuse and no others, because a form that shuts more than the rules
     * do takes work away from the operator for no reason.
     *
     * @return void
     * @link \App\Controller\BillingsController::edit()
     */
    public function testTheSettledTermsAreShutOnTheFormForWhoeverCannotSaveThem(): void
    {
        $billingId = $this->closedBillingId();

        $this->login('bookkeeper');
        $this->get('/billings/edit/' . $billingId);

        $this->assertResponseOk();
        // its end ran out in an invoiced month too, so a service change would not save either
        $this->assertResponseNotContains('/billings/service-change/' . $billingId);

        foreach ([...BillingsTable::SETTLED_TERMS, 'billing_from', 'billing_until'] as $shut) {
            $this->assertFieldIsShut($shut);
        }
        foreach (['text', 'separate_invoice', 'note'] as $open) {
            $this->assertFieldIsOpen($open);
        }

        // the admin has the box that reaches into an invoiced period, so nothing is shut for
        // them - but they are told the same thing, or they would reach in without noticing
        $this->login();
        $this->get('/billings/edit/' . $billingId);

        $this->assertResponseOk();
        $this->assertResponseNotContains('disabled="disabled"');
        $this->assertResponseContains('message warning');
    }

    /**
     * A billing running on may still be brought to a close, even where everything else about it
     * is settled - which is what ending a contract does.
     *
     * @return void
     * @link \App\Controller\BillingsController::edit()
     */
    public function testTheEndStaysOpenOnABillingThatRunsOn(): void
    {
        $billingId = $this->getTableLocator()->get('Billings')
            ->find()
            ->where(['Billings.billing_until IS' => null])
            ->firstOrFail()
            ->get('id');

        $this->login('bookkeeper');
        $this->get('/billings/edit/' . $billingId);

        $this->assertResponseOk();
        $this->assertFieldIsShut('billing_from');
        $this->assertFieldIsOpen('billing_until');
        // and because it still has an end to give, the way out is worth offering
        $this->assertResponseContains('/billings/service-change/' . $billingId);
    }

    /**
     * A billing invoicing has not reached is nobody's business but the operator's, so it is
     * offered whole.
     *
     * @return void
     * @link \App\Controller\BillingsController::edit()
     */
    public function testTheSettledTermsStayOpenWhileNothingHasBeenInvoiced(): void
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
        $this->get('/billings/edit/' . $billing->id);

        $this->assertResponseOk();
        $this->assertResponseNotContains('disabled="disabled"');
        $this->assertResponseNotContains('message warning');
    }

    /**
     * A field the form offers but will not take back.
     *
     * Matched loosely, because what the helper puts between the name and the disabled attribute
     * differs from one kind of box to the next and says nothing about the question being asked.
     *
     * @param string $field The field that has to be shut.
     * @return void
     */
    private function assertFieldIsShut(string $field): void
    {
        $this->assertMatchesRegularExpression(
            sprintf('/name="%s"[^>]*disabled="disabled"/', preg_quote($field, '/')),
            (string)$this->_response?->getBody(),
            sprintf('%s was left open.', $field),
        );
    }

    /**
     * A field that is still the operator's to fill in.
     *
     * @param string $field The field that has to be open.
     * @return void
     */
    private function assertFieldIsOpen(string $field): void
    {
        $this->assertDoesNotMatchRegularExpression(
            sprintf('/name="%s"[^>]*disabled="disabled"/', preg_quote($field, '/')),
            (string)$this->_response?->getBody(),
            sprintf('%s was shut.', $field),
        );
    }

    /**
     * A billing from the fixtures whose whole period lies in years nobody can still invoice for.
     *
     * @return string
     */
    private function closedBillingId(): string
    {
        return (string)$this->getTableLocator()->get('Billings')
            ->find()
            ->where(['Billings.billing_until IS NOT' => null])
            ->firstOrFail()
            ->get('id');
    }

    /**
     * A price somebody has been invoiced is not re-typed on the form either.
     *
     * @return void
     * @link \App\Controller\BillingsController::edit()
     */
    public function testTheFormRefusesToRepriceABillingAlreadyInvoicedFor(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $billingId = $this->firstId('Billings');
        $priced = $billings->get($billingId)->price;

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/edit/' . $billingId, ['price' => '99.00']);

        $this->assertNoRedirect();
        $this->assertEquals($priced, $billings->get($billingId)->price, 'The price was rewritten.');
    }

    /**
     * A connection below the contract's minimum is not saved, and a box sent by somebody who is not
     * offered it changes nothing.
     *
     * @return void
     * @link \App\Controller\BillingsController::add()
     */
    public function testTheFormRefusesAConnectionBelowTheMinimum(): void
    {
        $this->agreeMinimum('100');
        $before = $this->idsIn('Billings');

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/add', $this->connectionPricedAt('50') + [
            BillingsTable::ALLOW_BELOW_MINIMUM => '1',
        ]);

        $this->assertNoRedirect();
        $this->assertSame($before, $this->idsIn('Billings'));
    }

    /**
     * An admin and a manager are offered the box, and without ticking it are refused the same as
     * anybody.
     *
     * @param string $role The role asking.
     * @return void
     * @link \App\Controller\BillingsController::add()
     */
    #[DataProvider('rolesOfferedTheBoxes')]
    public function testTickingTheBoxPricesTheConnectionBelowTheMinimum(string $role): void
    {
        $this->agreeMinimum('100');
        $before = $this->idsIn('Billings');

        $this->login($role);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/add', $this->connectionPricedAt('50'));

        $this->assertNoRedirect();
        $this->assertResponseContains('allow_below_minimum');
        $this->assertSame($before, $this->idsIn('Billings'));

        $this->post('/billings/add', $this->connectionPricedAt('50') + [
            BillingsTable::ALLOW_BELOW_MINIMUM => '1',
        ]);

        $this->assertRedirect();
        $this->assertNotSame($before, $this->idsIn('Billings'));
    }

    /**
     * Deleting has no box to tick, only the confirmation behind the link. Whoever may price the
     * connection below the minimum may also take it away, the others are refused.
     *
     * @param string $role The role asking.
     * @return void
     * @link \App\Controller\BillingsController::delete()
     */
    #[DataProvider('rolesOfferedTheBoxes')]
    public function testDeletingTheConnectionTheMinimumRestsOn(string $role): void
    {
        $this->agreeMinimum('100');
        $before = $this->idsIn('Billings');
        $billings = $this->getTableLocator()->get('Billings');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/billings/add', $this->connectionPricedAt('150'));
        $this->assertRedirect();
        $billingId = (string)$this->addedRecord('Billings', $before)->get('id');

        $this->login('bookkeeper');
        $this->post('/billings/delete/' . $billingId);

        // let through to the action, and refused there rather than at the door
        $this->assertRedirectContains('/billings');
        $this->assertTrue($billings->exists(['id' => $billingId]));

        $this->login($role);
        $this->post('/billings/delete/' . $billingId);

        $this->assertFalse($billings->exists(['id' => $billingId]));
    }

    /**
     * The button on a billing invoiced for is drawn only for whoever may override the invoiced
     * periods, and its question warns them what they are about to change.
     *
     * @return void
     * @link \App\View\Helper\BillingHelper::deleteConfirmation()
     */
    public function testTheDeleteConfirmationWarnsOfAnInvoicedPeriod(): void
    {
        $warning = 'deleting it changes a period whose invoices have gone out';
        $billings = $this->getTableLocator()->get('Billings');
        $invoiced = $this->closedBillingId();
        $open = $billings->saveOrFail($billings->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            'text' => 'Not invoiced for yet',
            'quantity' => 1,
            'separate_invoice' => false,
            'billing_from' => $billings->firstOpenPeriodStart()->toDateString(),
        ]));

        $this->login('manager');
        $this->get('/billings/view/' . $invoiced);

        $this->assertResponseOk();
        $this->assertResponseContains('/billings/delete/' . $invoiced);
        $this->assertResponseContains($warning);

        $this->get('/billings/view/' . $open->id);

        $this->assertResponseOk();
        $this->assertResponseContains('/billings/delete/' . $open->id);
        $this->assertResponseNotContains($warning);

        $this->login('bookkeeper');
        $this->get('/billings/view/' . $invoiced);

        $this->assertResponseOk();
        $this->assertResponseNotContains('/billings/delete/' . $invoiced);
    }

    /**
     * A cheaper tariff below the minimum leaves the running one as it was, not ended with nothing
     * after it.
     *
     * @return void
     * @link \App\Controller\BillingsController::serviceChange()
     */
    public function testAServiceChangeBelowTheMinimumIsRefused(): void
    {
        $this->agreeMinimum('100');
        $billings = $this->getTableLocator()->get('Billings');
        $billing = $billings->find()
            ->where(['Billings.billing_until IS' => null, 'Billings.contract_id' => self::CONTRACT_ID])
            ->firstOrFail();

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->enableRetainFlashMessages();
        $this->post('/billings/service-change/' . $billing->get('id'), [
            'service_id' => $billing->get('service_id'),
            'price' => '50',
            'billing_from' => $billings->firstOpenPeriodStart()->toDateString(),
        ]);

        $this->assertNull($billings->get($billing->get('id'))->billing_until, 'The running billing was ended.');
        // the refusal is said, not only the failure
        $this->assertStringContainsString(
            'minimum set on the contract',
            (string)json_encode($this->_requestSession?->read('Flash')),
        );
    }

    /**
     * Puts a minimum on the contract the nested routes hang off.
     *
     * @param string $minimum The minimum.
     * @return void
     */
    private function agreeMinimum(string $minimum): void
    {
        $this->getTableLocator()->get('Contracts')->updateAll(
            ['minimum_connection_price' => $minimum],
            ['id' => self::CONTRACT_ID],
        );
    }

    /**
     * What the form sends for a new connection on that contract.
     *
     * @param string $price The price.
     * @return array<string, string>
     */
    private function connectionPricedAt(string $price): array
    {
        return [
            'customer_id' => self::CUSTOMER_ID,
            'contract_id' => self::CONTRACT_ID,
            // the fixture's service with a connection profile
            'service_id' => '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2',
            'price' => $price,
            'quantity' => '1',
            'separate_invoice' => '0',
            'billing_from' => $this->getTableLocator()->get('Billings')->firstOpenPeriodStart()->toDateString(),
        ];
    }

    /**
     * Customers of their own, each with a contract, a billing address and a billing at the standard
     * price running on the service a bulk change leaves.
     *
     * Written past the application, which has its own opinions on what a customer needs. The ids
     * run against the order of the rows, so that what is read first is not also what sorts first.
     *
     * @param int $count How many.
     * @return array<int, array{customer_id: string, contract_id: string, billing_id: string, email: string}>
     */
    private function billedCustomers(int $count): array
    {
        $connection = $this->getTableLocator()->get('Billings')->getConnection();
        $id = fn(int $kind, int $number): string => sprintf('%08d-0000-4000-8000-%012d', $kind, $count - $number);

        $customers = [];
        for ($number = 0; $number < $count; $number++) {
            $customer = [
                'customer_id' => $id(1, $number),
                'contract_id' => $id(2, $number),
                'billing_id' => $id(3, $number),
                'email' => 'customer' . $number . '@example.com',
            ];

            $connection->insert('customers', [
                'id' => $customer['customer_id'],
                'nid' => 9000 + $number,
                'accounting_profile_id' => 'ab05963c-1531-4677-a9ee-80cecde25124',
                'last_name' => 'Customer ' . $number,
            ]);
            $connection->insert('contracts', [
                'id' => $customer['contract_id'],
                'nid' => 9000 + $number,
                'customer_id' => $customer['customer_id'],
                'number' => 'BULK-' . $number,
                'contract_state_id' => '3fc51c92-5dbb-4bd4-9a47-237169c2755c',
                'service_type_id' => '907cbc5c-af88-43b6-b535-959b4fa2ce3d',
            ]);
            $connection->insert('emails', [
                'customer_id' => $customer['customer_id'],
                'email' => $customer['email'],
                'use_for_billing' => true,
            ]);
            $connection->insert('billings', [
                'id' => $customer['billing_id'],
                'customer_id' => $customer['customer_id'],
                'contract_id' => $customer['contract_id'],
                'service_id' => self::BULK_ORIGINAL_SERVICE_ID,
                'billing_from' => '2022-01-01',
                'quantity' => 1,
            ]);

            $customers[] = $customer;
        }

        return $customers;
    }

    /**
     * Submits the bulk change the way the form does: the filter in the address, the change in the body.
     *
     * @param array<string, string> $filter What the listing is narrowed by.
     * @param array<string, string> $change What the form says.
     * @return void
     */
    private function bulkChange(array $filter, array $change): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post(
            '/billings/bulk-service-change?' . http_build_query($filter + [
                'original_service_id' => self::BULK_ORIGINAL_SERVICE_ID,
            ]),
            $change + [
                'service_id' => self::BULK_NEW_SERVICE_ID,
                'price' => '',
                'fixed_discount' => '',
                'percentage_discount' => '',
            ],
        );

        $this->assertRedirect();
    }

    /**
     * What a bulk change left running on a contract.
     *
     * @param string $contractId The contract.
     * @return \App\Model\Entity\Billing
     */
    private function replacementOn(string $contractId): Billing
    {
        /** @var \App\Model\Entity\Billing */
        return $this->getTableLocator()->get('Billings')
            ->find()
            ->where(['contract_id' => $contractId, 'service_id' => self::BULK_NEW_SERVICE_ID])
            ->firstOrFail();
    }
}
