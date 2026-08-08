<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AccountingProfilesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\AccountingProfilesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(AccountingProfilesController::class)]
class AccountingProfilesControllerTest extends TestCase
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
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\AccountingProfilesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/accounting-profiles');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\AccountingProfilesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/accounting-profiles?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\AccountingProfilesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/accounting-profiles/view/' . $this->firstId('AccountingProfiles'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\AccountingProfilesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/accounting-profiles/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\AccountingProfilesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/accounting-profiles/edit/' . $this->firstId('AccountingProfiles'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\AccountingProfilesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/accounting-profiles/delete/' . $this->firstId('AccountingProfiles'));

        $this->assertRedirect();
    }

    /**
     * A profile filled in on the form is really stored. Rendering the form proves the page is
     * there; marshalling, validation, the application rules and the save only ever run on a request
     * that carries data.
     *
     * @return void
     * @link \App\Controller\AccountingProfilesController::add()
     */
    public function testAddStoresAProfile(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/accounting-profiles/add', [
            'name' => 'Reduced rate',
            'vat_rate' => '12',
            'reverse_charge' => '0',
            'invoice_with_items' => '1',
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\AccountingProfile $stored */
        $stored = $this->getTableLocator()->get('AccountingProfiles')
            ->find()
            ->where(['name' => 'Reduced rate'])
            ->firstOrFail();
        $this->assertSame(12.0, $stored->vat_rate);
    }

    /**
     * A profile without a VAT rate is not stored, and the operator is given the form back rather
     * than a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\AccountingProfilesController::add()
     */
    public function testAddRefusesAProfileWithoutAVatRate(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $accountingProfiles = $this->getTableLocator()->get('AccountingProfiles');
        $before = $accountingProfiles->find()->count();

        $this->post('/accounting-profiles/add', [
            'name' => 'Reduced rate',
            'vat_rate' => '',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $accountingProfiles->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\AccountingProfilesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $accountingProfileId = $this->firstId('AccountingProfiles');
        $this->post('/accounting-profiles/edit/' . $accountingProfileId, ['name' => 'Renamed profile']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed profile',
            $this->getTableLocator()->get('AccountingProfiles')->get($accountingProfileId)->name,
        );
    }
}
