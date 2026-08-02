<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Controller;

use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Radius\Controller\RadgroupcheckController;

/**
 * Radius\Controller\RadgroupcheckController Test Case
 *
 * Smoke tests: every action is requested once and has to answer. They are deliberately shallow -
 * their job is to notice an action that stopped answering at all, which is how the query building
 * bugs in this application have shown up.
 */
#[UsesClass(RadgroupcheckController::class)]
class RadgroupcheckControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Radgroupcheck',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \Radius\Controller\RadgroupcheckController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/radius/radgroupcheck');

        $this->assertResponseOk();
    }

    /**
     * The detail of a record renders.
     *
     * @return void
     * @link \Radius\Controller\RadgroupcheckController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/radius/radgroupcheck/view/' . $this->firstId('Radius.Radgroupcheck'));

        $this->assertResponseOk();
    }

    /**
     * The form for a new record renders.
     *
     * @return void
     * @link \Radius\Controller\RadgroupcheckController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/radius/radgroupcheck/add');

        $this->assertResponseOk();
    }

    /**
     * The form of an existing record renders.
     *
     * @return void
     * @link \Radius\Controller\RadgroupcheckController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/radius/radgroupcheck/edit/' . $this->firstId('Radius.Radgroupcheck'));

        $this->assertResponseOk();
    }

    /**
     * The delete action runs and redirects. Whether the record really goes depends on what else
     * still references it, which is the application rules' business rather than this test's.
     *
     * @return void
     * @link \Radius\Controller\RadgroupcheckController::delete()
     */
    public function testDelete(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/radius/radgroupcheck/delete/' . $this->firstId('Radius.Radgroupcheck'));

        $this->assertRedirect();
    }
}
