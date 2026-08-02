<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Controller\RadacctController;

/**
 * Radius\Controller\RadacctController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(RadacctController::class)]
class RadacctControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Radacct',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \Radius\Controller\RadacctController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/radius/radacct');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \Radius\Controller\RadacctController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/radius/radacct/view/' . $this->firstId('Radius.Radacct'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \Radius\Controller\RadacctController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/radius/radacct/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \Radius\Controller\RadacctController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/radius/radacct/edit/' . $this->firstId('Radius.Radacct'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \Radius\Controller\RadacctController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/radacct/delete/' . $this->firstId('Radius.Radacct'));

        $this->assertRedirect();
    }
}
