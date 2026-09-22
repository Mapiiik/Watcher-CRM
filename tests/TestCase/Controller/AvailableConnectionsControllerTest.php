<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\AvailableConnectionsController;
use App\Model\Enum\AvailableConnectionOrigin;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\AvailableConnectionsController Test Case
 */
#[UsesClass(AvailableConnectionsController::class)]
class AvailableConnectionsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use HttpClientTrait;
    use IntegrationTestTrait;

    private const RECORD_ID = 'ac000000-0000-4000-8000-000000000001';

    private const REGISTRY = 'https://addresses.example.com';

    /**
     * @var list<string>
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
        'app.AvailableConnections',
    ];

    /**
     * The registry answers what the tests ask of it and nothing is kept between them.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('Addresses.url', self::REGISTRY);
        Cache::clear('addresses_api');

        $this->mockClientGet(self::REGISTRY . '/v1/meta', $this->newClientResponse(
            200,
            ['Content-Type: application/json'],
            (string)json_encode(['supported_countries' => ['cz', 'hr']]),
        ));
        $this->mockClientGet(self::REGISTRY . '/v1/addresses/cz/16936213', $this->newClientResponse(
            200,
            ['Content-Type: application/json'],
            (string)json_encode([
                'source' => 'cz',
                'registry_ref' => '16936213',
                'formatted_address' => 'Pod Černým mostem 472, Podmoklice, 51301 Semily',
                'geometry' => ['type' => 'Point', 'coordinates' => [15.32, 50.59]],
            ]),
        ));
    }

    /**
     * @return void
     * @link \App\Controller\AvailableConnectionsController::index()
     */
    public function testIndex(): void
    {
        $this->login();

        $this->get('/available-connections?access_technology=ftth_p2mp_pon&origin=manual');

        $this->assertResponseOk();
        $this->assertResponseContains('Luční 464');
    }

    /**
     * @return void
     * @link \App\Controller\AvailableConnectionsController::view()
     */
    public function testView(): void
    {
        $this->login();

        $this->get('/available-connections/view/' . self::RECORD_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('Wired, nobody in yet');
    }

    /**
     * Picking an address fills the form in from the registry and saves nothing yet.
     *
     * @return void
     * @link \App\Controller\AvailableConnectionsController::add()
     */
    public function testPickingAnAddressFillsTheFormIn(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $table = $this->getTableLocator()->get('AvailableConnections');
        $before = $table->find()->count();

        $this->post('/available-connections/add', [
            'address_registry_source' => 'cz',
            'address_registry_search' => 'cz|16936213',
            'refresh' => 'refresh',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('Pod Černým mostem 472');
        $this->assertSame($before, $table->find()->count());
    }

    /**
     * With the address picked and the rest filled in, the record is kept as entered by hand.
     *
     * @return void
     * @link \App\Controller\AvailableConnectionsController::add()
     */
    public function testAddStoresAConnectionEnteredByHand(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/available-connections/add', [
            'address_registry_source' => 'cz',
            'address_registry_search' => 'cz|16936213',
            'access_technology' => 'fttb_ethernet',
            'speed_down_max' => '1024000',
            'speed_up_max' => '1024000',
        ]);

        $this->assertRedirect();
        /** @var \App\Model\Entity\AvailableConnection $stored */
        $stored = $this->getTableLocator()->get('AvailableConnections')->find()
            ->where(['address_registry_reference' => '16936213'])
            ->firstOrFail();
        $this->assertSame(AvailableConnectionOrigin::Manual, $stored->origin);
        $this->assertSame('Pod Černým mostem 472, Podmoklice, 51301 Semily', $stored->address_label);
        $this->assertSame(50.59, $stored->gps_y);
    }

    /**
     * Without an address from the registry there is nothing to report, so nothing is stored.
     *
     * @return void
     * @link \App\Controller\AvailableConnectionsController::add()
     */
    public function testAddRefusesAConnectionWithoutAnAddress(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $table = $this->getTableLocator()->get('AvailableConnections');
        $before = $table->find()->count();

        $this->post('/available-connections/add', [
            'address_registry_source' => 'cz',
            'access_technology' => 'fttb_ethernet',
            'speed_down_max' => '1024000',
            'speed_up_max' => '1024000',
        ]);

        $this->assertResponseOk();
        $this->assertSame($before, $table->find()->count());
    }

    /**
     * Changing what a synchronised record is about takes it over from the synchronisation, and
     * a note alone does not.
     *
     * @return void
     * @link \App\Controller\AvailableConnectionsController::edit()
     */
    public function testChangingTheSpeedsTakesTheRecordOver(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $table = $this->getTableLocator()->get('AvailableConnections');
        $table->updateAll(['origin' => AvailableConnectionOrigin::Contract->value], ['id' => self::RECORD_ID]);

        $this->post('/available-connections/edit/' . self::RECORD_ID, ['note' => 'Still wired']);
        $this->assertRedirect();
        $this->assertSame(AvailableConnectionOrigin::Contract, $table->get(self::RECORD_ID)->origin);

        $this->post('/available-connections/edit/' . self::RECORD_ID, ['speed_up_max' => '512000']);
        $this->assertRedirect();
        $this->assertSame(AvailableConnectionOrigin::Manual, $table->get(self::RECORD_ID)->origin);
    }

    /**
     * Retiring keeps the record and stops it being reported.
     *
     * @return void
     * @link \App\Controller\AvailableConnectionsController::retire()
     */
    public function testRetireKeepsTheRecord(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/available-connections/retire/' . self::RECORD_ID);

        $this->assertRedirect();
        $this->assertNotNull($this->getTableLocator()->get('AvailableConnections')->get(self::RECORD_ID)->retired);
    }
}
