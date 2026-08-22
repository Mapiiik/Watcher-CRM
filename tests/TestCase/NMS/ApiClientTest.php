<?php
declare(strict_types=1);

namespace App\Test\TestCase\NMS;

use App\NMS\ApiClient;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Client\Response;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\LogTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\RequestInterface;

/**
 * App\NMS\ApiClient Test Case
 *
 * What is asked about here is mostly what happens when Watcher NMS does not answer. Reading a good
 * answer is one test; the rest are the ways it can go wrong, because those are the ones that used
 * to pass for an empty list - and an operator shown an empty list has no way of telling that
 * anything went wrong at all.
 */
#[CoversClass(ApiClient::class)]
class ApiClientTest extends TestCase
{
    use HttpClientTrait;
    use LogTestTrait;

    /**
     * How many questions have actually gone out.
     */
    private int $asked = 0;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Nms.url', 'https://nms.example.com');
        Configure::write('Nms.key', 'secret');

        // Answers outlive a test otherwise, and the next one would be answered by the one before.
        Cache::clear('api_client');

        $this->setupLog(['error', 'warning']);

        $this->asked = 0;
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear('api_client');

        parent::tearDown();
    }

    /**
     * The ordinary answer comes back as a collection of what was asked for.
     *
     * @return void
     * @link \App\NMS\ApiClient::fetchAccessPoints()
     */
    public function testTheAnswerComesBackAsACollection(): void
    {
        $this->mock('/api/access-points.json', $this->jsonResponse([
            'accessPoints' => [['id' => '1', 'name' => 'Hilltop', 'archived' => null]],
        ]));

        $accessPoints = ApiClient::fetchAccessPoints();

        $this->assertNotNull($accessPoints);
        $this->assertSame([['id' => '1', 'name' => 'Hilltop', 'archived' => null]], $accessPoints->toArray());
    }

    /**
     * An answer is kept, so a page showing many rows asks once.
     *
     * @return void
     * @link \App\NMS\ApiClient::getAccessPointsList()
     */
    public function testAnAnswerIsKept(): void
    {
        $this->mock('/api/access-points.json', $this->jsonResponse([
            'accessPoints' => [['id' => '1', 'name' => 'Hilltop', 'archived' => null]],
        ]));

        $this->assertSame(['1' => 'Hilltop'], ApiClient::getAccessPointsList());
        $this->assertSame(['1' => 'Hilltop'], ApiClient::getAccessPointsList());
        $this->assertSame(1, $this->asked);
    }

    /**
     * A failure is not kept, so Watcher NMS coming back up is noticed at the next question rather
     * than whenever the cache happens to run out.
     *
     * This is the whole point of the exercise: what is written down is what Watcher NMS answered,
     * never what was left over after it did not.
     *
     * @return void
     * @link \App\NMS\ApiClient::getAccessPointsList()
     */
    public function testAFailureIsNotKept(): void
    {
        $this->mock('/api/access-points.json', $this->newClientResponse(500));

        $this->assertNull(ApiClient::getAccessPointsList());
        $this->assertNull(ApiClient::getAccessPointsList());
        $this->assertSame(2, $this->asked);
    }

    /**
     * Something going wrong at the other end is unanswered rather than empty.
     *
     * @return void
     * @link \App\NMS\ApiClient::fetchAccessPoints()
     */
    public function testSomethingGoingWrongAtTheOtherEndIsUnanswered(): void
    {
        $this->mock('/api/access-points.json', $this->newClientResponse(500));

        $this->assertNull(ApiClient::fetchAccessPoints());
        $this->assertLogMessageContains('error', 'Watcher NMS answered 500 asking about /api/access-points.json');
    }

    /**
     * An answer without the key it was asked for is an answer to a different question - a login
     * form, an error page, a changed API - and is not read as data.
     *
     * @return void
     * @link \App\NMS\ApiClient::fetchAccessPoints()
     */
    public function testAnAnswerWithoutWhatWasAskedForIsUnanswered(): void
    {
        $this->mock('/api/access-points.json', $this->jsonResponse(['error' => 'Invalid API key']));

        $this->assertNull(ApiClient::fetchAccessPoints());
        $this->assertLogMessageContains('warning', 'without `accessPoints` in it');
    }

    /**
     * An answer that is not JSON at all is unanswered.
     *
     * @return void
     * @link \App\NMS\ApiClient::fetchAccessPoints()
     */
    public function testAnAnswerThatIsNotJsonIsUnanswered(): void
    {
        $this->mock(
            '/api/access-points.json',
            $this->newClientResponse(200, ['Content-Type: text/html'], '<html>Gateway timeout</html>'),
        );

        $this->assertNull(ApiClient::fetchAccessPoints());
    }

    /**
     * Watcher NMS not answering at all is unanswered rather than a broken page.
     *
     * The mock stands in for the transport: a question nothing answers throws, which is what a
     * refused connection or a name that does not resolve does.
     *
     * @return void
     * @link \App\NMS\ApiClient::fetchAccessPoints()
     */
    public function testAnUnreachableNmsIsUnanswered(): void
    {
        $this->mock('/api/somewhere-else.json', $this->jsonResponse([]));

        $this->assertNull(ApiClient::fetchAccessPoints());
        $this->assertSame(0, $this->asked);
        $this->assertLogMessageContains('error', 'Watcher NMS could not be asked about /api/access-points.json');
    }

    /**
     * An installation with no Watcher NMS asks nothing and says nothing.
     *
     * @return void
     * @link \App\NMS\ApiClient::fetchAccessPoints()
     */
    public function testNothingIsAskedWithoutAnAddress(): void
    {
        Configure::write('Nms.url', '');

        $this->mock('/api/access-points.json', $this->jsonResponse(['accessPoints' => []]));

        $this->assertNull(ApiClient::fetchAccessPoints());
        $this->assertSame(0, $this->asked);
        // Not being configured is a state, not something to keep saying in the log.
        $this->assertLogAbsent('error');
        $this->assertLogAbsent('warning');
    }

    /**
     * An address without a key is no more configured than no address at all.
     *
     * @return void
     * @link \App\NMS\ApiClient::fetchAccessPoints()
     */
    public function testNothingIsAskedWithoutAKey(): void
    {
        Configure::write('Nms.key', '');

        $this->mock('/api/access-points.json', $this->jsonResponse(['accessPoints' => []]));

        $this->assertNull(ApiClient::fetchAccessPoints());
        $this->assertSame(0, $this->asked);
    }

    /**
     * A search carries its conditions alongside the key.
     *
     * @return void
     * @link \App\NMS\ApiClient::searchRouterosDevices()
     */
    public function testASearchAsksWithItsConditions(): void
    {
        $this->mock(
            '/api/routeros-devices/search.json',
            $this->jsonResponse(['routerosDevices' => [['id' => '7']]]),
            ['some_ip_address' => '10.0.0.1'],
        );

        $devices = ApiClient::searchRouterosDevices(['some_ip_address' => '10.0.0.1']);

        $this->assertNotNull($devices);
        $this->assertSame([['id' => '7']], $devices->toArray());
    }

    /**
     * Answers one question, counting the ones that actually go out.
     *
     * @param string $path What is being asked about.
     * @param \Cake\Http\Client\Response $response What Watcher NMS answers.
     * @param array<string, mixed> $query Anything asked for beyond the key.
     * @return void
     */
    private function mock(string $path, Response $response, array $query = []): void
    {
        $url = 'https://nms.example.com' . $path . '?'
            . http_build_query(['api_key' => 'secret'] + $query, '', '&', PHP_QUERY_RFC3986);

        $this->mockClientGet($url, $response, [
            'match' => function (RequestInterface $request): bool {
                $this->asked++;

                return true;
            },
        ]);
    }

    /**
     * @param array<string, mixed> $body What Watcher NMS answers with.
     * @return \Cake\Http\Client\Response
     */
    private function jsonResponse(array $body): Response
    {
        return $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode($body));
    }
}
