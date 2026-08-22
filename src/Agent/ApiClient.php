<?php
declare(strict_types=1);

namespace App\Agent;

use App\Agent\Provider\AgentPayloadNormalizer;
use App\Http\Answer;
use App\Http\WritesDownFailuresTrait;
use Cake\Core\Configure;
use Cake\Http\Client;
use Radius\Model\Entity\Radacct;
use Throwable;

/**
 * Talking to the Watcher Agent.
 *
 * Every reading comes back as an {@see \App\Http\Answer}, so the caller says what a failure is
 * worth: a page drawing a status shows that it does not know, an operator asking for a session to
 * be dropped is told why it was not.
 */
class ApiClient
{
    use WritesDownFailuresTrait;

    /**
     * What this service is called in the log.
     */
    private const SERVICE = 'Watcher Agent';

    /**
     * Ask the agent to do one thing.
     *
     * @param string $function API function to call (e.g., 'radius/disconnect')
     * @param array<string, mixed> $data Data to send in the request body
     * @param int $timeout Timeout in seconds
     * @param string $expect The field the answer must carry to be one at all.
     * @return \App\Http\Answer<array<int|string, mixed>>
     */
    private static function ask(string $function, array $data = [], int $timeout = 30, string $expect = ''): Answer
    {
        $agentUrl = (string)Configure::read('Agent.url');
        $agentToken = (string)Configure::read('Agent.token');

        // Not being configured is a state, not a failure - an installation without an agent says
        // so by leaving the address empty, and nobody asked.
        if ($agentUrl === '' || $agentToken === '') {
            return Answer::notAsked();
        }

        $http = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $agentToken,
                'Accept' => 'application/json',
            ],
            'timeout' => $timeout,
        ]);

        $where = $agentUrl . '/api/' . $function;

        try {
            $response = $http->post($where, $data, ['type' => 'json']);
        } catch (Throwable $e) {
            return self::unreachable(self::SERVICE, $where, $e->getMessage());
        }

        $body = $response->getJson();
        $message = is_array($body) ? ($body['message'] ?? null) : null;
        $message = is_scalar($message) ? (string)$message : null;

        if (!$response->isOk()) {
            return self::refused(self::SERVICE, $where, $response->getStatusCode(), $message);
        }

        // An answer with no verdict in it is not a verdict of no; it is an answer to a different
        // question, and reading it as one would report a host as unreachable that was never asked.
        if (!is_array($body) || ($expect !== '' && !isset($body[$expect]))) {
            return self::unexpected(
                self::SERVICE,
                $where,
                $message ?? sprintf('no `%s` in it', $expect),
            );
        }

        return Answer::of($body);
    }

    /**
     * Ping a host
     *
     * @param string $ipAddress IP address to ping
     * @return \App\Http\Answer<\App\Agent\Dto\PingResult>
     */
    public static function ping(string $ipAddress): Answer
    {
        return self::ask(
            function: 'ping',
            data: [
                'host' => $ipAddress,
                'count' => 10,
                'timeout_ms' => 500,
            ],
            timeout: 10,
            expect: 'reachable',
        )->map(AgentPayloadNormalizer::ping(...));
    }

    /**
     * RADIUS disconnect
     *
     * @param \Radius\Model\Entity\Radacct $session RADIUS Accounting Record.
     * @return \App\Http\Answer<\App\Agent\Dto\DisconnectResult>
     */
    public static function radiusDisconnect(Radacct $session): Answer
    {
        return self::ask(
            function: 'radius/disconnect',
            data: [
                'nas_ip' => $session->nasipaddress,
                'port' => 1700,
                'secret' => Configure::read('Agent.radiusSecret'),
                'timeout_ms' => 3000, // 3 seconds

                'username' => $session->username,
                'acct_session_id' => $session->acctsessionid,
                'framed_ip' => $session->framedipaddress,
            ],
            timeout: 10,
            expect: 'success',
        )->map(AgentPayloadNormalizer::disconnect(...));
    }
}
