<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CustomerLabelsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\CustomerLabelsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(CustomerLabelsController::class)]
class CustomerLabelsControllerTest extends TestCase
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
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Labels',
        'app.CustomerLabels',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\CustomerLabelsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/customer-labels');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\CustomerLabelsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/customer-labels?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\CustomerLabelsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/customer-labels/view/' . $this->firstId('CustomerLabels'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\CustomerLabelsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/customer-labels/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\CustomerLabelsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/customer-labels/edit/' . $this->firstId('CustomerLabels'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\CustomerLabelsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customer-labels/delete/' . $this->firstId('CustomerLabels'));

        $this->assertRedirect();
    }

    /**
     * Added under its customer, the record is filed under them without the form saying so.
     *
     * The form under a customer leaves those fields out - the route already says which record it is,
     * and the controller fills them in. Posting them in the body instead, as a test reaching the
     * flat route does, asks a different question and leaves this one unasked.
     *
     * @return void
     * @link \App\Controller\CustomerLabelsController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('CustomerLabels');
        $this->post('/customers/' . self::CUSTOMER_ID . '/customer-labels/add', [
            'label_id' => $this->firstId('Labels'),
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('CustomerLabels', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\CustomerLabelsController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $customerLabelId = $this->firstId('CustomerLabels');
        $this->post('/customer-labels/edit/' . $customerLabelId, ['note' => 'Set by hand.']);

        $this->assertRedirect();
        $this->assertSame(
            'Set by hand.',
            $this->getTableLocator()->get('CustomerLabels')->get($customerLabelId)->note,
        );
    }
}
