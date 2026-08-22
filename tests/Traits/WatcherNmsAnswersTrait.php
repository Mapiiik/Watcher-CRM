<?php
declare(strict_types=1);

namespace App\Test\Traits;

use Cake\Cache\Cache;
use Cake\Http\TestSuite\HttpClientTrait;

/**
 * Lets a test say what Watcher NMS keeps, and what it says when it keeps nothing.
 *
 * The rules that check a place of the network read it over the network, so a test that does not
 * speak for the other application either reaches whatever the installation is configured with or
 * asks nothing at all - and either way it is not the test that was written. The address is
 * therefore always the made-up one, and an answer is only given where the test gives it.
 */
trait WatcherNmsAnswersTrait
{
    use ConfigureTestTrait;
    use HttpClientTrait;

    /**
     * A place of the network the tests let Watcher NMS answer with.
     *
     * @var string
     */
    protected const ACCESS_POINT_ID = '9c4f6b1e-2f0a-4d3c-9a71-6b0d5f8e2a14';

    /**
     * Stand a Watcher NMS up for the length of the test. Call from `setUp()`.
     *
     * @return void
     */
    protected function withWatcherNms(): void
    {
        $this->withConfigure(['Nms.url' => 'https://nms.example.com', 'Nms.key' => 'secret']);

        // An answer outlives the test that was given it, and the next one would be answered by it.
        Cache::clear('api_client');
    }

    /**
     * Take it down again. Call from `tearDown()`.
     *
     * @return void
     */
    protected function withoutWatcherNms(): void
    {
        Cache::clear('api_client');
        $this->restoreConfigure();
    }

    /**
     * Let Watcher NMS answer with the one place the tests know about.
     *
     * @return void
     */
    protected function answerWithTheOneAccessPoint(): void
    {
        $body = (string)json_encode(['accessPoints' => [['id' => self::ACCESS_POINT_ID, 'name' => 'Hilltop']]]);

        $this->mockClientGet(
            $this->accessPointsUrl(),
            $this->newClientResponse(200, ['Content-Type: application/json'], $body),
        );
    }

    /**
     * Let Watcher NMS be asked and answer nothing worth reading.
     *
     * @return void
     */
    protected function answerWithAFailure(): void
    {
        $this->mockClientGet($this->accessPointsUrl(), $this->newClientResponse(500));
    }

    /**
     * Where the listing of places is asked for, spelled the way the client asks for it.
     *
     * @return string
     */
    private function accessPointsUrl(): string
    {
        return 'https://nms.example.com/api/access-points.json?'
            . http_build_query(['api_key' => 'secret'], '', '&', PHP_QUERY_RFC3986);
    }
}
