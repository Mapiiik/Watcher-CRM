<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Controller\Api\NetworkManagementSystemBridgeController;
use App\NMS\ApiClient;
use App\Test\Traits\ConfigureTestTrait;
use App\Test\Traits\ControllerTestTrait;
use Cake\Cache\Cache;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;
use ReflectionProperty;

/**
 * App\Controller\Api\NetworkManagementSystemBridgeController Test Case
 *
 * These are the cells a page loads afterwards, one request each, and what is checked is what the
 * operator ends up looking at: a cell that says the network management system did not answer, and
 * one that stays blank because it truly had nothing to show.
 */
#[UsesClass(NetworkManagementSystemBridgeController::class)]
class NetworkManagementSystemBridgeControllerTest extends TestCase
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
        (new ReflectionProperty(ApiClient::class, 'answered'))->setValue(null, null);
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
     * A cell whose answer never came says so, instead of quietly replacing "Loading..." with
     * nothing and reading as an address no device answers for.
     *
     * @return void
     * @link \App\Controller\Api\NetworkManagementSystemBridgeController::routerosDevices()
     */
    public function testACellWhoseAnswerNeverCameSaysSo(): void
    {
        $this->mock('/api/routeros-devices/search.json', 500, ['some_ip_address' => '10.0.0.1']);

        $this->login();
        $this->get('/api/network-management-system-bridge/routeros-devices/10.0.0.1.ajax');

        $this->assertResponseOk();
        $this->assertResponseContains('warning-text');
    }

    /**
     * A cell the network management system answered nothing for stays blank, because that is what
     * it means: no device at that address.
     *
     * @return void
     * @link \App\Controller\Api\NetworkManagementSystemBridgeController::routerosDevices()
     */
    public function testACellWithNothingToShowStaysBlank(): void
    {
        $this->mock(
            '/api/routeros-devices/search.json',
            200,
            ['some_ip_address' => '10.0.0.1'],
            ['routerosDevices' => []],
        );

        $this->login();
        $this->get('/api/network-management-system-bridge/routeros-devices/10.0.0.1.ajax');

        $this->assertResponseOk();
        $this->assertResponseNotContains('warning-text');
    }

    /**
     * The range cell is read the same way.
     *
     * @return void
     * @link \App\Controller\Api\NetworkManagementSystemBridgeController::ipAddressRanges()
     */
    public function testTheRangeCellSaysSoToo(): void
    {
        $this->mock('/api/ip-address-ranges/search.json', 500, ['ip_address' => '10.0.0.1']);

        $this->login();
        $this->get('/api/network-management-system-bridge/ip-address-ranges/10.0.0.1.ajax');

        $this->assertResponseOk();
        $this->assertResponseContains('warning-text');
    }

    /**
     * An installation with no network management system gets a blank cell rather than a remark
     * about a system it was never given.
     *
     * @return void
     * @link \App\Controller\Api\NetworkManagementSystemBridgeController::routerosDevices()
     */
    public function testAnUnconfiguredInstallationGetsNoRemark(): void
    {
        $this->withConfigure(['Nms.url' => '']);

        $this->login();
        $this->get('/api/network-management-system-bridge/routeros-devices/10.0.0.1.ajax');

        $this->assertResponseOk();
        $this->assertResponseNotContains('warning-text');
    }

    /**
     * Answers one question of the network management system's.
     *
     * @param string $path What is being asked about.
     * @param int $status What it answers with.
     * @param array<string, mixed> $query Anything asked for beyond the key.
     * @param array<string, mixed> $body What it answers.
     * @return void
     */
    private function mock(string $path, int $status, array $query = [], array $body = []): void
    {
        $url = 'https://nms.example.com' . $path . '?'
            . http_build_query(['api_key' => 'secret'] + $query, '', '&', PHP_QUERY_RFC3986);

        $this->mockClientGet($url, $this->newClientResponse(
            $status,
            ['Content-Type: application/json'],
            (string)json_encode($body),
        ));
    }
}
