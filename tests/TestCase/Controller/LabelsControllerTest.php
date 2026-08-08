<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\LabelsController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\LabelsController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(LabelsController::class)]
class LabelsControllerTest extends TestCase
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
        'app.Labels',
        'app.CustomerLabels',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\LabelsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/labels');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\LabelsController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/labels?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\LabelsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/labels/view/' . $this->firstId('Labels'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\LabelsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/labels/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\LabelsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/labels/edit/' . $this->firstId('Labels'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\LabelsController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/labels/delete/' . $this->firstId('Labels'));

        $this->assertRedirect();
    }

    /**
     * A label filled in on the form is really stored. Rendering the form proves the page is there;
     * marshalling, validation, the application rules and the save only ever run on a request that
     * carries data.
     *
     * @return void
     * @link \App\Controller\LabelsController::add()
     */
    public function testAddStoresALabel(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/labels/add', [
            'name' => 'Debtor',
            'caption' => 'Owes for more than a month',
            'color' => '#336699',
            'validity' => '30',
            'dynamic' => '0',
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\Label $stored */
        $stored = $this->getTableLocator()->get('Labels')
            ->find()
            ->where(['name' => 'Debtor'])
            ->firstOrFail();
        $this->assertSame('#336699', $stored->color);
    }

    /**
     * A label that leaves the dynamic switch empty is not stored, and the operator is given the
     * form back rather than a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\LabelsController::add()
     */
    public function testAddRefusesALabelWithAnEmptyDynamicSwitch(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $labels = $this->getTableLocator()->get('Labels');
        $before = $labels->find()->count();

        $this->post('/labels/add', [
            'name' => 'Debtor',
            'dynamic' => '',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $labels->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\LabelsController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $labelId = $this->firstId('Labels');
        $this->post('/labels/edit/' . $labelId, ['name' => 'Renamed label']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed label',
            $this->getTableLocator()->get('Labels')->get($labelId)->name,
        );
    }
}
