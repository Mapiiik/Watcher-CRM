<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\OverviewsController;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\OverviewsController Test Case
 */
#[UsesClass(OverviewsController::class)]
class OverviewsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
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
     * Test index method
     *
     * @return void
     * @link \App\Controller\OverviewsController::index()
     */
    public function testIndex(): void
    {
        $this->login();

        $this->get('/overviews');

        $this->assertResponseOk();
        // the whole table of connection history is reachable from here, there is
        // nowhere else to get at it outside a single customer or contract
        $this->assertResponseContains('/connection-history');
    }

    /**
     * Test view method
     *
     * @return void
     * @link \App\Controller\OverviewsController::view()
     */
    public function testView(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test add method
     *
     * @return void
     * @link \App\Controller\OverviewsController::add()
     */
    public function testAdd(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test edit method
     *
     * @return void
     * @link \App\Controller\OverviewsController::edit()
     */
    public function testEdit(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test delete method
     *
     * @return void
     * @link \App\Controller\OverviewsController::delete()
     */
    public function testDelete(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
