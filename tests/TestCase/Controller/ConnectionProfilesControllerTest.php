<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ConnectionProfilesController;
use App\Model\Enum\AccessTechnology;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\ConnectionProfilesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(ConnectionProfilesController::class)]
class ConnectionProfilesControllerTest extends TestCase
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
        'app.ConnectionProfiles',
        'app.ServiceTypes',
        'app.Services',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/connection-profiles');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/connection-profiles?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/connection-profiles/view/' . $this->firstId('ConnectionProfiles'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/connection-profiles/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/connection-profiles/edit/' . $this->firstId('ConnectionProfiles'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/connection-profiles/delete/' . $this->firstId('ConnectionProfiles'));

        $this->assertRedirect();
    }

    /**
     * A connection profile filled in on the form is really stored. Rendering the form proves the
     * page is there; marshalling, validation, the application rules and the save only ever run on a
     * request that carries data.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::add()
     */
    public function testAddStoresAConnectionProfile(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/connection-profiles/add', [
            'name' => '50/50 Mbit',
            'radius_group' => 'Basic 50',
            'fup_limit' => '100000',
            'service_type_id' => $this->firstId('ServiceTypes'),
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\ConnectionProfile $stored */
        $stored = $this->getTableLocator()->get('ConnectionProfiles')
            ->find()
            ->where(['radius_group' => 'Basic 50'])
            ->firstOrFail();
        $this->assertSame(100000, $stored->fup_limit);
    }

    /**
     * A connection profile without a name is not stored, and the operator is given the form back
     * rather than a redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::add()
     */
    public function testAddRefusesAConnectionProfileWithoutAName(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $connectionProfiles = $this->getTableLocator()->get('ConnectionProfiles');
        $before = $connectionProfiles->find()->count();

        $this->post('/connection-profiles/add', [
            'name' => '',
            'radius_group' => 'Basic 50',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $connectionProfiles->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $connectionProfileId = $this->firstId('ConnectionProfiles');
        $this->post('/connection-profiles/edit/' . $connectionProfileId, ['name' => 'Renamed profile']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed profile',
            $this->getTableLocator()->get('ConnectionProfiles')->get($connectionProfileId)->name,
        );
    }

    /**
     * The technology is one of the known ones, and comes back as what it is rather than as text.
     *
     * @return void
     * @link \App\Controller\ConnectionProfilesController::edit()
     */
    public function testTheTechnologyIsOneOfTheKnownOnes(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $connectionProfileId = $this->firstId('ConnectionProfiles');
        $profiles = $this->getTableLocator()->get('ConnectionProfiles');

        $this->post('/connection-profiles/edit/' . $connectionProfileId, ['access_technology' => 'ftth_p2mp_pon']);
        $this->assertRedirect();
        $this->assertSame(AccessTechnology::FtthP2mpPon, $profiles->get($connectionProfileId)->access_technology);

        $this->post('/connection-profiles/edit/' . $connectionProfileId, ['access_technology' => 's2_wifi']);
        $this->assertResponseOk();
        $this->assertSame(AccessTechnology::FtthP2mpPon, $profiles->get($connectionProfileId)->access_technology);
    }
}
