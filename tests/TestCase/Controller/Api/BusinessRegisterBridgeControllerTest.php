<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use App\Controller\Api\BusinessRegisterBridgeController;
use App\Test\TestCase\BusinessRegister\Source\StubSource;
use App\Test\Traits\ConfigureTestTrait;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\Api\BusinessRegisterBridgeController Test Case
 *
 * The register is stood in for, so what is checked is the endpoint: which requests it refuses and
 * what shape it answers the widget in.
 */
#[UsesClass(BusinessRegisterBridgeController::class)]
class BusinessRegisterBridgeControllerTest extends TestCase
{
    use ConfigureTestTrait;
    use ControllerTestTrait;
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

        StubSource::reset();
        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    public function tearDown(): void
    {
        $this->restoreConfigure();
        StubSource::reset();

        parent::tearDown();
    }

    /**
     * The hits come back as the widget wants them: an id naming both the register and the entry,
     * and a line telling two companies of the same name apart.
     *
     * @return void
     * @link \App\Controller\Api\BusinessRegisterBridgeController::search()
     */
    public function testSearchAnswersInTheShapeTheWidgetWants(): void
    {
        StubSource::$entries = [
            [
                'reference' => '27496139',
                'company' => 'NETAIR, s.r.o.',
                'identity_number' => '27496139',
                'address' => 'Jablonec nad Jizerou 299',
            ],
        ];

        $this->login();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/business-register-bridge/search.json?source=stub&query=netair');

        $this->assertResponseOk();
        $this->assertResponseContains('"stub|27496139"');
        $this->assertResponseContains('NETAIR, s.r.o., 27496139, Jablonec nad Jizerou 299');
        $this->assertResponseContains('"pagination"');
    }

    /**
     * A register with nothing to say answers with nothing rather than with an error.
     *
     * @return void
     * @link \App\Controller\Api\BusinessRegisterBridgeController::search()
     */
    public function testSearchWithoutHitsAnswersWithAnEmptyList(): void
    {
        $this->login();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/business-register-bridge/search.json?source=stub&query=nobody');

        $this->assertResponseOk();
        $this->assertResponseContains('"results"');
        // no hit means no entry, and an entry is what carries an id
        $this->assertResponseNotContains('"id"');
    }

    /**
     * Without both a register and something to look for there is nothing to ask.
     *
     * @return void
     * @link \App\Controller\Api\BusinessRegisterBridgeController::search()
     */
    public function testSearchWithoutBothParametersIsRefused(): void
    {
        $this->login();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);

        $this->get('/api/business-register-bridge/search.json?source=stub');
        $this->assertResponseCode(400);

        $this->get('/api/business-register-bridge/search.json?query=netair');
        $this->assertResponseCode(400);
    }

    /**
     * A register that is turned off is refused here rather than silently answering nothing, so a
     * widget offering one that has since gone says so.
     *
     * @return void
     * @link \App\Controller\Api\BusinessRegisterBridgeController::search()
     */
    public function testSearchInARegisterThatCannotAnswerIsRefused(): void
    {
        StubSource::$configured = false;

        $this->login();
        $this->configRequest(['headers' => ['Accept' => 'application/json']]);
        $this->get('/api/business-register-bridge/search.json?source=stub&query=netair');

        $this->assertResponseCode(400);
    }
}
