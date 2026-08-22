<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Test\Traits\ConfigureTestTrait;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;

/**
 * The pages that show what Watcher NMS says, with Watcher NMS saying nothing.
 *
 * Every one of them draws itself from a reading that came to nothing, and the point of the test is
 * that they draw themselves at all. That is not obvious: the access point of a contract reaches the
 * map, the printed contract and half a dozen listings, and each of those reads a different part of
 * it - a name, a pair of coordinates, a range of addresses. A reading that answers nothing has to
 * be safe in all of them, and the one place it was not was found this way.
 *
 * A page that says nothing is a different failure from a page that will not load, so where the
 * remark belongs it is asked for by name.
 */
class PagesSurviveADeadNmsTest extends TestCase
{
    use ConfigureTestTrait;
    use ControllerTestTrait;
    use HttpClientTrait;
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
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.ContractVersions',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.SoldEquipments',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
        'app.Emails',
        'app.Phones',
        'app.Labels',
        'app.CustomerLabels',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->withConfigure(['Nms.url' => 'https://nms.example.com', 'Nms.key' => 'secret']);

        Cache::clear('api_client');

        // Whatever is asked of Watcher NMS, it answers with a server that has fallen over.
        $this->mockClientGet('https://nms.example.com/*', $this->newClientResponse(500));

        $this->login();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    public function tearDown(): void
    {
        Cache::clear('api_client');
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * The listings that show which access point serves a contract or a task.
     *
     * @return void
     * @link \App\Controller\ContractsController::index()
     */
    public function testTheListingsDrawWithoutTheAccessPointNames(): void
    {
        foreach (['/contracts', '/tasks', '/overviews/overview-of-contracts'] as $page) {
            $this->get($page);

            $this->assertResponseOk('The page ' . $page . ' did not draw.');
        }
    }

    /**
     * The detail of a contract, which shows the access point and every address hanging off it.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testTheContractDetailDrawsAndSaysWhatItCouldNotLoad(): void
    {
        $this->get('/contracts/view/' . $this->firstId('Contracts'));

        $this->assertResponseOk();
        $this->assertResponseContains('warning-text');
    }

    /**
     * The button that opens the range picker stays: it hangs off the recorded access point rather
     * than off one looked up, so an outage does not make it look like a decision.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testTheRangeButtonStaysWhileTheNmsIsDown(): void
    {
        $this->get('/contracts/view/' . $this->firstId('Contracts'));

        $this->assertResponseOk();
        $this->assertResponseContains('New IP Address From Range');
    }

    /**
     * The map, which wants the coordinates of the access point rather than its name.
     *
     * @return void
     * @link \App\Controller\ContractsController::map()
     */
    public function testTheMapDrawsTheCustomerAlone(): void
    {
        $this->get('/contracts/map/' . $this->firstId('Contracts'));

        $this->assertResponseOk();
        $this->assertSame(['customer'], array_keys((array)$this->viewVariable('mapMarkers')));
        $this->assertNull($this->viewVariable('mapDistance'));
    }

    /**
     * The printed contract, which reads the access point's name and the range of every address on
     * it - and prints a dash where the range never came.
     *
     * @return void
     * @link \App\Controller\ContractsController::print()
     */
    public function testThePrintedContractIsStillProduced(): void
    {
        $this->get('/contracts/print/' . $this->firstId('Contracts'));

        $this->assertResponseOk();
    }

    /**
     * The customer, whose detail lists the contracts with their access points.
     *
     * @return void
     * @link \App\Controller\CustomersController::view()
     */
    public function testTheCustomerDetailDraws(): void
    {
        $this->get('/customers/view/' . $this->firstId('Customers'));

        $this->assertResponseOk();
    }

    /**
     * The forms that pick from a list Watcher NMS keeps say so outright, because an empty select
     * box is the one thing an operator cannot act on.
     *
     * @return void
     * @link \App\Controller\Traits\CommonViewVarListsTrait::setAccessPointsViewVarList()
     */
    public function testAFormSaysTheListCouldNotBeLoaded(): void
    {
        $this->get('/tasks');

        $this->assertResponseOk();
        // read off the page rather than out of the session: by now it has been drawn, which is
        // the whole of what the operator gets
        $this->assertResponseContains('The access points list could not be loaded.');
    }

    /**
     * An installation with no Watcher NMS draws the same pages without a word about it.
     *
     * @return void
     * @link \App\Controller\ContractsController::view()
     */
    public function testAnInstallationWithoutAnNmsSaysNothing(): void
    {
        $this->withConfigure(['Nms.url' => '']);

        $this->get('/contracts/view/' . $this->firstId('Contracts'));

        $this->assertResponseOk();
        $this->assertResponseNotContains('warning-text');
    }
}
