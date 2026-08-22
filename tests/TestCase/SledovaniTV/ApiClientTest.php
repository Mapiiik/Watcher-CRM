<?php
declare(strict_types=1);

namespace App\Test\TestCase\SledovaniTV;

use App\SledovaniTV\ApiClient;
use Cake\Core\Configure;
use Cake\Http\Client\Response;
use Cake\Http\TestSuite\HttpClientTrait;
use Cake\TestSuite\LogTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * App\SledovaniTV\ApiClient Test Case
 *
 * The blocking of debtors runs off these answers, so what matters is that an answer which is not
 * the one expected is read as nothing rather than taken apart. A refused login comes back with a
 * status of 200 and no list in it, and reading that as a list used to end the run.
 */
#[CoversClass(ApiClient::class)]
class ApiClientTest extends TestCase
{
    use HttpClientTrait;
    use LogTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Configure::write('SledovaniTv.username', 'partner');
        Configure::write('SledovaniTv.password', 'secret');

        $this->setupLog(['error', 'warning']);
    }

    /**
     * The list comes back as it arrived.
     *
     * @return void
     * @link \App\SledovaniTV\ApiClient::getUsers()
     */
    public function testTheListComesBackAsItArrived(): void
    {
        $this->mock('get-users', $this->jsonResponse([
            'users' => [['id' => '1', 'partnerid' => '2024001', 'active' => 1, 'suspended' => 0]],
        ]));

        $this->assertSame(
            [['id' => '1', 'partnerid' => '2024001', 'active' => 1, 'suspended' => 0]],
            ApiClient::getUsers(),
        );
    }

    /**
     * An answer with no list in it is nobody, not a broken run.
     *
     * @return void
     * @link \App\SledovaniTV\ApiClient::getUsers()
     */
    public function testAnAnswerWithoutAListIsNobody(): void
    {
        $this->mock('get-users', $this->jsonResponse(['error' => 'Invalid partner']));

        $this->assertSame([], ApiClient::getUsers());
        $this->assertLogMessageContains('warning', 'without a list of users in it');
    }

    /**
     * A user was suspended only where the answer says so.
     *
     * @return void
     * @link \App\SledovaniTV\ApiClient::suspendUser()
     */
    public function testSuspendingIsOnlyReportedWhereItIsAnswered(): void
    {
        // The mocks are answered in the order they were added.
        $this->mock('suspend-user', $this->jsonResponse(['suspended' => true]));
        $this->mock('suspend-user', $this->jsonResponse(['error' => 'No such user']));

        $this->assertTrue(ApiClient::suspendUser(1));
        $this->assertFalse(ApiClient::suspendUser(1));
    }

    /**
     * The same for putting a user back.
     *
     * @return void
     * @link \App\SledovaniTV\ApiClient::unsuspendUser()
     */
    public function testUnsuspendingIsOnlyReportedWhereItIsAnswered(): void
    {
        $this->mock('unsuspend-user', $this->jsonResponse(['activated' => true]));
        $this->mock('unsuspend-user', $this->jsonResponse(['error' => 'No such user']));

        $this->assertTrue(ApiClient::unsuspendUser(1));
        $this->assertFalse(ApiClient::unsuspendUser(1));
    }

    /**
     * Something going wrong at the other end is written down and thrown, which is what the caller
     * catches to tell the operator which part of the blocking did not happen.
     *
     * @return void
     * @link \App\SledovaniTV\ApiClient::getUsers()
     */
    public function testSomethingGoingWrongAtTheOtherEndIsThrown(): void
    {
        $this->mock('get-users', $this->newClientResponse(500, [], 'Service unavailable'));

        $this->expectException(RuntimeException::class);

        try {
            ApiClient::getUsers();
        } finally {
            $this->assertLogMessageContains('error', 'Invalid response from SledovaniTV API');
        }
    }

    /**
     * SledovaniTV not answering at all is thrown as itself rather than as whatever the transport
     * happened to call it.
     *
     * @return void
     * @link \App\SledovaniTV\ApiClient::getUsers()
     */
    public function testAnUnreachableApiIsThrown(): void
    {
        $this->mock('somewhere-else', $this->jsonResponse([]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/unreachable/');

        ApiClient::getUsers();
    }

    /**
     * @param string $function What is being asked for.
     * @param \Cake\Http\Client\Response $response What SledovaniTV answers.
     * @return void
     */
    private function mock(string $function, Response $response): void
    {
        $this->mockClientPost('https://sledovanitv.cz/partner/api/' . $function, $response);
    }

    /**
     * @param array<string, mixed> $body What SledovaniTV answers with.
     * @return \Cake\Http\Client\Response
     */
    private function jsonResponse(array $body): Response
    {
        return $this->newClientResponse(200, ['Content-Type: application/json'], (string)json_encode($body));
    }
}
