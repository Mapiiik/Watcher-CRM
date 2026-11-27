<?php
declare(strict_types=1);

namespace Radius\Test\TestCase\Updater;

use App\Messages\Message;
use ArrayObject;
use Cake\Cache\Cache;
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
 * The access points are changed by the Watcher Agent and the site is read from Watcher NMS, so
 * what matters here is what the two are asked and how their answers reach the operator.
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
     * Where Watcher NMS listens.
     *
     * @var string
     */
    private const NMS = 'https://nms.example.com';

    /**
     * The access point of the session.
     *
     * @var string
     */
    private const ACCESS_POINT = '10.10.10.1';

    /**
     * The site Watcher NMS keeps the access point of the session under.
     *
     * @var string
     */
    private const SITE = '5b0c8f4e-7a1d-4c2e-9f63-1d8e2a7b4c05';

    /**
     * The station.
     *
     * @var string
     */
    private const STATION = 'AA:BB:CC:DD:EE:FF';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Radius.Accounts',
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
        Configure::write('Nms.url', '');
        Configure::write('Nms.key', '');
        Cache::clear('api_client');

        $this->fetchTable('Radius.Radacct')->updateAll(
            ['username' => 'station', 'callingstationid' => self::STATION],
            ['1 = 1'],
        );
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('api_client');

        parent::tearDown();
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
                ['menu' => '/interface/wifi/access-list', 'interface' => 'wifi1', 'mac_address' => self::STATION],
            ],
        ]);

        $cleaner = new AccessListCleaner();

        $this->assertTrue($cleaner->removeMacAddress(new Account(['username' => 'station'])));
        $this->assertSame(
            [['host' => self::ACCESS_POINT, 'mac_address' => self::STATION]],
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
     * Every board of the site is cleaned, the one of the session first. The board of the session
     * is not asked again under its own address, a board kept twice is asked once, and a board of
     * another site - which a Watcher NMS not knowing the filter would hand over - is left alone.
     *
     * @return void
     * @link \Radius\Updater\AccessListCleaner::removeMacAddress()
     */
    public function testEveryBoardOfTheSiteIsCleaned(): void
    {
        $this->siteIs([
            $this->board('session', '10.20.0.1'),
            $this->board('second', '10.20.0.2'),
            $this->board('second, replaced', '10.20.0.2'),
            $this->board('third', '10.20.0.3'),
            $this->board('without an address', null),
            $this->board('elsewhere', '10.30.0.1', '0d6f1c2a-3b4e-4f5a-8b9c-0d1e2f3a4b5c'),
        ]);
        $sent = $this->agentAnswersFor([
            self::ACCESS_POINT => [200, $this->removed(self::ACCESS_POINT, 'wlan1')],
            '10.20.0.2' => [200, $this->removed('10.20.0.2')],
            '10.20.0.3' => [200, $this->removed('10.20.0.3', 'wlan2')],
        ]);

        $cleaner = new AccessListCleaner();

        $this->assertTrue($cleaner->removeMacAddress(new Account(['username' => 'station'])));
        $this->assertSame([self::ACCESS_POINT, '10.20.0.2', '10.20.0.3'], $sent->getArrayCopy());
        $this->assertSame(['success'], $this->types($cleaner));
        $text = $cleaner->Messages->getMessages()[0]->text;
        $this->assertStringContainsString('Access points updated.', $text);
        $this->assertStringContainsString('3 access points of the site Hilltop checked.', $text);
        $this->assertStringContainsString('on interface wlan1 from router 10.10.10.1.', $text);
        $this->assertStringContainsString('on interface wlan2 from router 10.20.0.3.', $text);
    }

    /**
     * A board that cannot be reached is a warning of its own, and the rest of the site is still
     * cleaned.
     *
     * @return void
     * @link \Radius\Updater\AccessListCleaner::removeMacAddress()
     */
    public function testABoardOutOfReachDoesNotStopTheRest(): void
    {
        $this->siteIs([
            $this->board('session', '10.20.0.1'),
            $this->board('down', '10.20.0.2'),
            $this->board('up', '10.20.0.3'),
        ]);
        $sent = $this->agentAnswersFor([
            self::ACCESS_POINT => [200, $this->removed(self::ACCESS_POINT)],
            '10.20.0.2' => [502, ['error' => 'routeros_unreachable', 'message' => 'no route to host']],
            '10.20.0.3' => [200, $this->removed('10.20.0.3')],
        ]);

        $cleaner = new AccessListCleaner();

        $this->assertTrue($cleaner->removeMacAddress(new Account(['username' => 'station'])));
        $this->assertSame([self::ACCESS_POINT, '10.20.0.2', '10.20.0.3'], $sent->getArrayCopy());
        $this->assertSame(['warning', 'success'], $this->types($cleaner));
        $this->assertStringContainsString(
            'Problem connecting to the access point 10.20.0.2: ',
            $cleaner->Messages->getMessages()[0]->text,
        );
        $this->assertStringContainsString(
            '2 access points of the site Hilltop checked.',
            $cleaner->Messages->getMessages()[1]->text,
        );
    }

    /**
     * Where Watcher NMS was asked and did not answer, the access point of the session is still
     * cleaned, and the operator is told the rest of the site was not.
     *
     * @return void
     * @link \Radius\Updater\AccessListCleaner::removeMacAddress()
     */
    public function testWithoutAnAnswerFromWatcherNmsOnlyTheSessionIsCleaned(): void
    {
        $this->nmsAnswers(['some_ip_address' => self::ACCESS_POINT], 500, []);
        $sent = $this->agentAnswersFor([self::ACCESS_POINT => [200, $this->removed(self::ACCESS_POINT)]]);

        $cleaner = new AccessListCleaner();

        $this->assertTrue($cleaner->removeMacAddress(new Account(['username' => 'station'])));
        $this->assertSame([self::ACCESS_POINT], $sent->getArrayCopy());
        $this->assertSame(['warning', 'success'], $this->types($cleaner));
        $this->assertStringContainsString(
            'Watcher NMS did not answer, so only the access point 10.10.10.1 is cleaned.',
            $cleaner->Messages->getMessages()[0]->text,
        );
    }

    /**
     * An access point Watcher NMS keeps on no site is cleaned alone, and the operator is told why.
     *
     * @return void
     * @link \Radius\Updater\AccessListCleaner::removeMacAddress()
     */
    public function testAnAccessPointOnNoSiteIsCleanedAlone(): void
    {
        $this->nmsAnswers(['some_ip_address' => self::ACCESS_POINT], 200, ['routerosDevices' => []]);
        $sent = $this->agentAnswersFor([self::ACCESS_POINT => [200, $this->removed(self::ACCESS_POINT)]]);

        $cleaner = new AccessListCleaner();

        $this->assertTrue($cleaner->removeMacAddress(new Account(['username' => 'station'])));
        $this->assertSame([self::ACCESS_POINT], $sent->getArrayCopy());
        $this->assertSame(['warning', 'success'], $this->types($cleaner));
    }

    /**
     * A station another active account has connected from since is still in use - a unit handed
     * over with the contract - so it is left on every access point.
     *
     * @return void
     * @link \Radius\Updater\AccessListCleaner::removeMacAddress()
     */
    public function testAStationInUseByAnotherAccountIsLeftAlone(): void
    {
        $this->connectedSinceBy('successor', active: true);
        $sent = $this->agentAnswers(200, ['removed' => []]);

        $cleaner = new AccessListCleaner();

        $this->assertFalse($cleaner->removeMacAddress(new Account(['username' => 'station'])));
        $this->assertCount(0, $sent);
        $this->assertSame(['warning'], $this->types($cleaner));
        $this->assertStringContainsString(
            'The active account successor has connected from the MAC address AA:BB:CC:DD:EE:FF since',
            $cleaner->Messages->getMessages()[0]->text,
        );
    }

    /**
     * An account no longer active that connected from the station since does not keep it.
     *
     * @return void
     * @link \Radius\Updater\AccessListCleaner::removeMacAddress()
     */
    public function testAStationUsedByAnInactiveAccountIsStillRemoved(): void
    {
        $this->connectedSinceBy('successor', active: false);
        $sent = $this->agentAnswers(200, ['removed' => []]);

        $cleaner = new AccessListCleaner();

        $this->assertTrue($cleaner->removeMacAddress(new Account(['username' => 'station'])));
        $this->assertCount(1, $sent);
    }

    /**
     * Have Watcher NMS keep the access point of the session on a site with these boards.
     *
     * @param list<array<string, mixed>> $boards The boards Watcher NMS hands over for the site.
     * @return void
     */
    private function siteIs(array $boards): void
    {
        Configure::write('Nms.url', self::NMS);
        Configure::write('Nms.key', 'nms-key');

        $this->nmsAnswers(['some_ip_address' => self::ACCESS_POINT], 200, ['routerosDevices' => [$boards[0]]]);
        $this->nmsAnswers(
            ['access_point_id' => self::SITE, 'maximum_age' => AccessListCleaner::MAXIMUM_DEVICE_AGE],
            200,
            ['routerosDevices' => $boards],
        );
    }

    /**
     * One board as Watcher NMS hands it over.
     *
     * @param string $id What it is kept under.
     * @param string|null $ipAddress The address it is managed at.
     * @param string $site The site it is on.
     * @return array<string, mixed>
     */
    private function board(string $id, ?string $ipAddress, string $site = self::SITE): array
    {
        return [
            'id' => $id,
            'ip_address' => $ipAddress,
            'access_point_id' => $site,
            'access_point' => ['id' => $site, 'name' => $site === self::SITE ? 'Hilltop' : 'Elsewhere'],
        ];
    }

    /**
     * Have Watcher NMS answer one search.
     *
     * @param array<string, mixed> $query What the search asks for.
     * @param int $status What Watcher NMS answers with.
     * @param array<string, mixed> $body What it says.
     * @return void
     */
    private function nmsAnswers(array $query, int $status, array $body): void
    {
        Configure::write('Nms.url', self::NMS);
        Configure::write('Nms.key', 'nms-key');

        $this->mockClientGet(
            self::NMS . '/api/routeros-devices/search.json??' . http_build_query($query, '', '&', PHP_QUERY_RFC3986),
            $this->newClientResponse($status, ['Content-Type: application/json'], (string)json_encode($body)),
        );
    }

    /**
     * Have another account connect from the station after the session of the one being cleaned.
     *
     * @param string $username The other account.
     * @param bool $active Whether it is active.
     * @return void
     */
    private function connectedSinceBy(string $username, bool $active): void
    {
        $accounts = $this->fetchTable('Radius.Accounts');
        $account = $accounts->find()->firstOrFail();
        $accounts->updateAll(['username' => $username, 'active' => $active], ['id' => $account->get('id')]);

        $radacct = $this->fetchTable('Radius.Radacct');
        /** @var array<string, mixed> $session */
        $session = $radacct->find()->enableHydration(false)->firstOrFail();
        $radacct->insertQuery()
            ->insert(array_keys($session), $radacct->getSchema()->typeMap())
            ->values([
                'radacctid' => (int)$session['radacctid'] + 1,
                'acctuniqueid' => 'later',
                'username' => $username,
            ] + $session)
            ->execute();
    }

    /**
     * What the agent says it removed from a board.
     *
     * @param string $host The board.
     * @param string $interface Where the station was.
     * @return array<string, mixed>
     */
    private function removed(string $host, string $interface = 'wlan1'): array
    {
        return [
            'host' => $host,
            'transport' => 'api-ssl',
            'removed' => [
                ['menu' => '/interface/wireless/access-list', 'interface' => $interface, 'mac_address' => self::STATION],
            ],
        ];
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
     * Have the agent answer each board in its own way, writing down which boards it was asked about.
     *
     * @param array<string, array{0: int, 1: array<string, mixed>}> $answers Status and body by board.
     * @return \ArrayObject<int, string>
     */
    private function agentAnswersFor(array $answers): ArrayObject
    {
        /** @var \ArrayObject<int, string> $sent */
        $sent = new ArrayObject();

        foreach ($answers as $host => [$status, $body]) {
            $this->mockClientPost(
                self::AGENT . '/api/routeros/access-list/remove',
                $this->newClientResponse($status, ['Content-Type: application/json'], (string)json_encode($body)),
                ['match' => function (RequestInterface $request) use ($sent, $host): bool {
                    $asked = json_decode((string)$request->getBody(), true);
                    if (!is_array($asked) || $asked['host'] !== (string)$host) {
                        return false;
                    }
                    $sent[] = $asked['host'];

                    return true;
                }],
            );
        }

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
