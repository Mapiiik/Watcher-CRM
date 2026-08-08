<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\DealerCommissionsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\DealerCommissionsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(DealerCommissionsController::class)]
class DealerCommissionsControllerTest extends TestCase
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
        'app.Commissions',
        'app.DealerCommissions',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\DealerCommissionsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/dealer-commissions');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\DealerCommissionsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/dealer-commissions?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\DealerCommissionsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/dealer-commissions/view/' . $this->firstId('DealerCommissions'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\DealerCommissionsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/dealer-commissions/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\DealerCommissionsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/dealer-commissions/edit/' . $this->firstId('DealerCommissions'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\DealerCommissionsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/dealer-commissions/delete/' . $this->firstId('DealerCommissions'));

        $this->assertRedirect();
    }

    /**
     * A commission filled in on the form is really stored. Rendering the form proves the page is
     * there; marshalling, validation, the application rules and the save only ever run on a request
     * that carries data.
     *
     * The dealer and the commission are taken from the record the fixtures carry, because only a
     * customer the association counts as a dealer will pass the rules.
     *
     * @return void
     * @link \App\Controller\DealerCommissionsController::add()
     */
    public function testAddStoresACommission(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $dealerCommissions = $this->getTableLocator()->get('DealerCommissions');
        $existing = $dealerCommissions->find()->firstOrFail();

        $before = $this->idsIn('DealerCommissions');
        $this->post('/dealer-commissions/add', [
            'dealer_id' => $existing->get('dealer_id'),
            'commission_id' => $existing->get('commission_id'),
            'fixed' => '250',
            'percentage' => '5',
        ]);

        $this->assertRedirect();
        $this->assertSame(250.0, $this->addedRecord('DealerCommissions', $before)->get('fixed'));
    }

    /**
     * A commission for a dealer that is not there is not stored, and the operator is given the form
     * back rather than a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\DealerCommissionsController::add()
     */
    public function testAddRefusesACommissionForADealerThatIsNotThere(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $dealerCommissions = $this->getTableLocator()->get('DealerCommissions');
        $existing = $dealerCommissions->find()->firstOrFail();
        $before = $dealerCommissions->find()->count();

        $this->post('/dealer-commissions/add', [
            'dealer_id' => '3f2b1a0c-0000-4000-8000-000000000000',
            'commission_id' => $existing->get('commission_id'),
            'fixed' => '250',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $dealerCommissions->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\DealerCommissionsController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $dealerCommissionId = $this->firstId('DealerCommissions');
        $this->post('/dealer-commissions/edit/' . $dealerCommissionId, ['fixed' => '333']);

        $this->assertRedirect();
        $this->assertSame(
            333.0,
            $this->getTableLocator()->get('DealerCommissions')->get($dealerCommissionId)->fixed,
        );
    }
}
