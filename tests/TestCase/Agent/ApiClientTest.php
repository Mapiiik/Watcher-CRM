<?php
declare(strict_types=1);

namespace App\Test\TestCase\Agent;

use App\Agent\ApiClient;
use Cake\Core\Configure;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\RequestInterface;

/**
 * App\Agent\ApiClient Test Case
 */
#[CoversClass(ApiClient::class)]
class ApiClientTest extends TestCase
{
    use HttpClientTrait;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('Agent.url', 'https://agent.example.com');
        Configure::write('Agent.token', 'secret');
    }

    /**
     * The certificate is checked unless the installation says otherwise, which only a development
     * agent with a certificate of its own has any business doing.
     *
     * @return void
     * @link \App\Agent\ApiClient::ping()
     */
    public function testTheCertificateIsCheckedUnlessTurnedOff(): void
    {
        $checked = [];
        $this->mockClientPost(
            'https://agent.example.com/api/ping',
            $this->newClientResponse(
                200,
                ['Content-Type: application/json'],
                (string)json_encode(['reachable' => true, 'loss_pct' => 0]),
            ),
            ['match' => function (RequestInterface $request, array $options) use (&$checked): bool {
                $this->assertSame('Bearer secret', $request->getHeaderLine('Authorization'));
                $checked[] = [
                    $options['ssl_verify_peer'],
                    $options['ssl_verify_peer_name'],
                    $options['ssl_verify_host'],
                ];

                return true;
            }],
        );

        Configure::write('Agent.verifyTls', true);
        $this->assertTrue(ApiClient::ping('192.0.2.1')->orFail()->reachable);

        Configure::write('Agent.verifyTls', false);
        $this->assertTrue(ApiClient::ping('192.0.2.1')->orFail()->reachable);

        $this->assertSame([[true, true, true], [false, false, false]], $checked);
    }
}
