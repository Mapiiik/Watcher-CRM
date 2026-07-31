<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CustomersController;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\CustomersController Test Case
 */
#[UsesClass(CustomersController::class)]
class CustomersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Customer owning the contract from the Contracts fixture.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * Contract from the Contracts fixture.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * Verification code of the contract from the Contracts fixture, rendered in the column
     * the obligation column has to precede.
     *
     * @var string
     */
    private const VERIFICATION_CODE = 'Lorem ipsum dolor sit amet';

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
    ];

    /**
     * login method
     *
     * @return void
     */
    protected function login(): void
    {
        /** @var \App\Model\Table\AppUsersTable $usersTable */
        $usersTable = $this->getTableLocator()->get(Configure::read('Users.table', 'Users'));

        $user = $usersTable->newEmptyEntity();
        $user->username = 'tester';
        $user->role = 'admin';
        $user->active = true;

        $this->session(['Auth' => $user]);
    }

    /**
     * Adds a contract version with the given obligation date to the fixture contract.
     *
     * @param string $obligationUntil Obligation date.
     * @return void
     */
    private function addContractVersion(string $obligationUntil): void
    {
        $contractVersionsTable = $this->getTableLocator()->get('ContractVersions');
        $contractVersion = $contractVersionsTable->newEntity([
            'contract_id' => self::CONTRACT_ID,
            'valid_from' => '2023-01-01',
            'obligation_until' => $obligationUntil,
            'obligations_settled' => false,
            'number_of_amendments' => 0,
        ]);
        $contractVersionsTable->saveOrFail($contractVersion);
    }

    /**
     * Asserts the obligation cell of the fixture contract, anchored to the verification code
     * cell that has to follow it.
     *
     * @param string $style Expected style attribute of the cell.
     * @param string $obligationUntil Expected content of the cell.
     * @return void
     */
    private function assertObligationCell(string $style, string $obligationUntil): void
    {
        $this->assertResponseRegExp(
            '~<td style="' . preg_quote($style, '~') . '">' . preg_quote($obligationUntil, '~') . '</td>\s*'
            . '<td>' . preg_quote(self::VERIFICATION_CODE, '~') . '</td>~',
        );
    }

    /**
     * Test index method
     *
     * @return void
     * @link \App\Controller\CustomersController::index()
     */
    public function testIndex(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertResponseContains(__('Obligation Until'));
    }

    /**
     * Test that the related contracts show the latest obligation date of their contract versions.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewShowsLatestObligationUntilOfContractVersions(): void
    {
        // later than the 2022-11-30 obligation of the fixture contract version, but still in the past
        $this->addContractVersion('2023-06-30');

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertObligationCell('', (string)new Date('2023-06-30'));
    }

    /**
     * Test that an obligation date in the future is highlighted, as it is on the contract detail.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewHighlightsFutureObligationUntil(): void
    {
        $futureObligationUntil = Date::now()->addYears(1);
        $this->addContractVersion($futureObligationUntil->toDateString());

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertObligationCell('color: red;', (string)$futureObligationUntil);
    }

    /**
     * Test that a contract without any obligation renders an empty cell.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testViewRendersEmptyObligationUntilWithoutObligation(): void
    {
        $contractVersionsTable = $this->getTableLocator()->get('ContractVersions');
        $contractVersionsTable->updateAll(['obligation_until' => null], ['contract_id' => self::CONTRACT_ID]);

        $this->login();
        $this->get('/customers/view/' . self::CUSTOMER_ID);

        $this->assertResponseOk();
        $this->assertObligationCell('', '');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\CustomersController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\CustomersController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\CustomersController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test print method
     *
     * @return void
     * @link \App\Controller\CustomersController::print()
     */
    public function testPrint(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
