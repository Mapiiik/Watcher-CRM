<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\LoginsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\LoginsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(LoginsController::class)]
class LoginsControllerTest extends TestCase
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
        'app.Logins',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\LoginsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/logins');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\LoginsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/logins?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\LoginsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/logins/view/' . $this->firstId('Logins'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\LoginsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/logins/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\LoginsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/logins/edit/' . $this->firstId('Logins'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\LoginsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/logins/delete/' . $this->firstId('Logins'));

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
     * @link \App\Controller\LoginsController::add()
     */
    public function testAddUnderTheRouteFilesItUnderTheRoute(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('Logins');
        $this->post('/customers/' . self::CUSTOMER_ID . '/logins/add', [
            'login' => 'nested-login',
            'password' => 'secret-password',
        ]);

        $this->assertRedirect();
        $added = $this->addedRecord('Logins', $before);
        $this->assertSame(self::CUSTOMER_ID, $added->get('customer_id'));
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\LoginsController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $loginId = $this->firstId('Logins');
        $this->post('/logins/edit/' . $loginId, ['login' => 'renamed-login']);

        $this->assertRedirect();
        $this->assertSame(
            'renamed-login',
            $this->getTableLocator()->get('Logins')->get($loginId)->login,
        );
    }
}
