<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\QueuesController;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\QueuesController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(QueuesController::class)]
class QueuesControllerTest extends TestCase
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
        'app.Queues',
        'app.ServiceTypes',
        'app.Services',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\QueuesController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/queues');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search filled in, which builds a different query than the plain
     * listing does and is therefore worth requesting on its own.
     *
     * @return void
     * @link \App\Controller\QueuesController::index()
     */
    public function testIndexWithSearch(): void
    {
        $this->login();
        $this->get('/queues?search=Lorem');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \App\Controller\QueuesController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/queues/view/' . $this->firstId('Queues'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \App\Controller\QueuesController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/queues/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \App\Controller\QueuesController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/queues/edit/' . $this->firstId('Queues'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \App\Controller\QueuesController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/queues/delete/' . $this->firstId('Queues'));

        $this->assertRedirect();
    }

    /**
     * A queue filled in on the form is really stored. Rendering the form proves the page is there;
     * marshalling, validation, the application rules and the save only ever run on a request that
     * carries data.
     *
     * @return void
     * @link \App\Controller\QueuesController::add()
     */
    public function testAddStoresAQueue(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/queues/add', [
            'name' => 'Basic 50',
            'caption' => '50/50 Mbit',
            'fup_limit' => '100000',
            'service_type_id' => $this->firstId('ServiceTypes'),
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\Queue $stored */
        $stored = $this->getTableLocator()->get('Queues')
            ->find()
            ->where(['name' => 'Basic 50'])
            ->firstOrFail();
        $this->assertSame(100000, $stored->fup_limit);
    }

    /**
     * A queue without a name is not stored, and the operator is given the form back rather than a
     * redirect that would suggest it went through.
     *
     * @return void
     * @link \App\Controller\QueuesController::add()
     */
    public function testAddRefusesAQueueWithoutAName(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $queues = $this->getTableLocator()->get('Queues');
        $before = $queues->find()->count();

        $this->post('/queues/add', [
            'name' => '',
            'caption' => '50/50 Mbit',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $queues->find()->count());
    }

    /**
     * A change made on the form reaches the record.
     *
     * @return void
     * @link \App\Controller\QueuesController::edit()
     */
    public function testEditStoresTheChange(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $queueId = $this->firstId('Queues');
        $this->post('/queues/edit/' . $queueId, ['name' => 'Renamed queue']);

        $this->assertRedirect();
        $this->assertSame(
            'Renamed queue',
            $this->getTableLocator()->get('Queues')->get($queueId)->name,
        );
    }
}
