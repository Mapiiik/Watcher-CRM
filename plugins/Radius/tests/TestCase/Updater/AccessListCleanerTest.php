<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Updater;

use App\Messages\Message;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\RequestInterface;
use Radius\Model\Entity\Account;
use Radius\Updater\AccessListCleaner;

/**
 * Radius\Updater\AccessListCleaner Test Case
 *
 * The access point is changed by the Watcher Agent, so what matters here is what it is asked
 * and how its answer reaches the operator.
 */
#[CoversClass(AccessListCleaner::class)]
class AccessListCleanerTest extends TestCase
{
    use HttpClientTrait;
    use LocatorAwareTrait;

    /**
     * Where the agent listens.
     *
     * @var string
     */
    private const AGENT = 'https://agent.example.com';

    /**
     * The access point of the session.
     *
     * @var string
     */
    private const ACCESS_POINT = '10.10.10.1';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Radacct',
    ];

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Agent.url', self::AGENT);
        Configure::write('Agent.token', 'agent-token');

        $this->fetchTable('Radius.Radacct')->updateAll(
            ['username' => 'station', 'callingstationid' => 'AA:BB:CC:DD:EE:FF'],
            ['1 = 1'],
        );
    }

    /**
     * The agent is asked about the access point of the last session and the station in it, and
     * what it removed is told to the operator.
     *
     * @return void
     * @link \Radius\Updater\AccessListCleaner::removeMacAddress()
     */
    public function testTheStationIsRemovedThroughTheAgent(): void
    {
        $sent = $this->agentAnswers(200, [
            'host' => self::ACCESS_POINT,
            'transport' => 'api',
            'removed' => [
                ['menu' => '/interface/wifi/access-list', 'interface' => 'wifi1', 'mac_address' => 'AA:BB:CC:DD:EE:FF'],
            ],
        ]);

        $cleaner = new AccessListCleaner();

        $this->assertTrue($cleaner->removeMacAddress(new Account(['username' => 'station'])));
        $this->assertSame(
            [['host' => self::ACCESS_POINT, 'mac_address' => 'AA:BB:CC:DD:EE:FF']],
            $sent->getArrayCopy(),
        );
        $this->assertSame(['success'], $this->types($cleaner));
        $this->assertStringContainsString(
            'Removed MAC address entry AA:BB:CC:DD:EE:FF on interface wifi1 from router 10.10.10.1.',
            $cleaner->Messages->getMessages()[0]->text,
        );
    }

    /**
     * An access point the agent could not change is an error, with the agent's reason in it.
     *
     * @return void
     * @link \Radius\Updater\AccessListCleaner::removeMacAddress()
     */
    public function testWhatTheAgentCouldNotDoIsAnError(): void
    {
        $this->agentAnswers(502, ['error' => 'routeros_unreachable', 'message' => 'no connection to the router']);

        $cleaner = new AccessListCleaner();

        $this->assertFalse($cleaner->removeMacAddress(new Account(['username' => 'station'])));
        $this->assertSame(['error'], $this->types($cleaner));
        $this->assertStringContainsString(
            'no connection to the router',
            $cleaner->Messages->getMessages()[0]->text,
        );
    }

    /**
     * Without a session, or a session without a station, there is nothing to ask the agent about.
     *
     * @return void
     * @link \Radius\Updater\AccessListCleaner::removeMacAddress()
     */
    public function testNothingIsAskedWithoutAStation(): void
    {
        $sent = $this->agentAnswers(200, ['removed' => []]);

        $cleaner = new AccessListCleaner();
        $this->assertFalse($cleaner->removeMacAddress(new Account(['username' => 'somebody else'])));

        $this->fetchTable('Radius.Radacct')->updateAll(['callingstationid' => null], ['1 = 1']);
        $this->assertFalse($cleaner->removeMacAddress(new Account(['username' => 'station'])));

        $this->assertSame(['warning', 'warning'], $this->types($cleaner));
        $this->assertCount(0, $sent);
    }

    /**
     * Have the agent answer, writing down what it was asked.
     *
     * @param int $status What the agent answers with.
     * @param array<string, mixed> $body What it says.
     * @return \ArrayObject<int, mixed>
     */
    private function agentAnswers(int $status, array $body): ArrayObject
    {
        /** @var \ArrayObject<int, mixed> $sent */
        $sent = new ArrayObject();

        $this->mockClientPost(
            self::AGENT . '/api/routeros/access-list/remove',
            $this->newClientResponse($status, ['Content-Type: application/json'], (string)json_encode($body)),
            ['match' => function (RequestInterface $request) use ($sent): bool {
                $sent[] = json_decode((string)$request->getBody(), true);

                return true;
            }],
        );

        return $sent;
    }

    /**
     * @param \Radius\Updater\AccessListCleaner $cleaner What was cleaning.
     * @return list<string>
     */
    private function types(AccessListCleaner $cleaner): array
    {
        return array_values(array_map(
            fn(Message $message): string => $message->type,
            $cleaner->Messages->getMessages(),
        ));
    }
}
