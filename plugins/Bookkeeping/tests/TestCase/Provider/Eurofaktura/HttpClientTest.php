<?php
declare(strict_types=1);

namespace Bookkeeping\Test\TestCase\Provider\Eurofaktura;

use App\Http\Answer;
use Bookkeeping\Provider\Eurofaktura\EurofakturaCredentials;
use Bookkeeping\Provider\Eurofaktura\HttpClient;
use Bookkeeping\Provider\Eurofaktura\JsonParser;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Bookkeeping\Provider\Eurofaktura\HttpClient Test Case
 *
 * The accounting system answers a listing that matched nothing with a 500 and the reason in the
 * body, so a nightly sync on a quiet day looks exactly like one that went wrong. Reading the status
 * as the verdict ends the run and leaves the invoices unread, which is what these tests are here to
 * stop happening again.
 */
#[CoversClass(HttpClient::class)]
class HttpClientTest extends TestCase
{
    use HttpClientTrait;

    private const URL = 'https://e-racuni.com/H7i/API';

    /**
     * Test subject
     *
     * @var \Bookkeeping\Provider\Eurofaktura\HttpClient
     */
    private HttpClient $client;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new HttpClient();
    }

    /**
     * A listing that matched nothing is an answer, not a failure.
     *
     * @return void
     */
    public function testNothingFoundIsAnAnswer(): void
    {
        $this->mock(500, [
            'response' => [
                'status' => 'error',
                'description' => 'Error nr: 1 #noDocumentsFound - No documents were found'
                    . ' matching search criteria!;',
            ],
        ]);

        $answer = $this->send();

        $this->assertTrue($answer->ok());
        $this->assertSame([], (new JsonParser())->parseSalesInvoiceList($answer->data));
    }

    /**
     * A refusal with something else behind it still travels as an answer, for the parser to read.
     *
     * @return void
     */
    public function testARefusalIsHandedOnToBeRead(): void
    {
        $this->mock(500, ['response' => ['status' => 'error', 'description' => 'Bad credentials']]);

        $answer = $this->send();

        $this->assertTrue($answer->ok());
        $this->assertSame('Bad credentials', $answer->data['response']['description']);
    }

    /**
     * An answer that is not the accounting system talking at all is a failure.
     *
     * @return void
     */
    public function testAGatewayErrorIsAFailure(): void
    {
        $this->mockClientPost(
            self::URL,
            $this->newClientResponse(502, ['Content-Type: text/html'], '<html>Bad Gateway</html>'),
        );

        $answer = $this->send();

        $this->assertTrue($answer->unanswered());
        $this->assertStringContainsString('502', (string)$answer->failure);
    }

    /**
     * A listing that did match something comes back as it always did.
     *
     * @return void
     */
    public function testAnOrdinaryAnswerComesBackWhole(): void
    {
        $this->mock(200, ['response' => ['status' => 'ok', 'result' => []]]);

        $answer = $this->send();

        $this->assertTrue($answer->ok());
        $this->assertSame('ok', $answer->data['response']['status']);
    }

    /**
     * @param int $status What the accounting system answers with.
     * @param array<string, mixed> $body What it puts in the body.
     * @return void
     */
    private function mock(int $status, array $body): void
    {
        $this->mockClientPost(
            self::URL,
            $this->newClientResponse(
                $status,
                ['Content-Type: application/json'],
                (string)json_encode($body),
            ),
        );
    }

    /**
     * @return \App\Http\Answer<array<mixed>>
     */
    private function send(): Answer
    {
        return $this->client->send(
            new EurofakturaCredentials('user', 'secret', 'token'),
            'SalesInvoiceList',
        );
    }
}
