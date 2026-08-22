<?php
declare(strict_types=1);

namespace App\Agent;

use App\Agent\Provider\AgentPayloadNormalizer;
use App\Http\Answer;
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
    /**
     * Ask the agent to do one thing.
     *
     * @param string $function API function to call (e.g., 'radius/disconnect')
     * @param array<string, mixed> $data Data to send in the request body
     * @param int $timeout Timeout in seconds
     * @param string $expect The field the answer must carry to be one at all.
     * @return \App\Http\Answer Answering with the body as it arrived.
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

        try {
            $response = $http->post($agentUrl . '/api/' . $function, $data, ['type' => 'json']);
        } catch (Throwable $e) {
            return Answer::failed(__('Watcher Agent is unreachable: {0}', $e->getMessage()));
        }

        $body = $response->getJson();
        $message = is_array($body) ? ($body['message'] ?? null) : null;
        $message = is_scalar($message) ? (string)$message : null;

        if (!$response->isOk()) {
            return Answer::failed(__(
                'Watcher Agent returned HTTP {0} ({1})',
                $response->getStatusCode(),
                $message ?? __('Unknown error'),
            ));
        }

        // An answer with no verdict in it is not a verdict of no; it is an answer to a different
        // question, and reading it as one would report a host as unreachable that was never asked.
        if (!is_array($body) || ($expect !== '' && !isset($body[$expect]))) {
            return Answer::failed(__(
                'Watcher Agent returned an unexpected response: {0}',
                $message ?? __('Unknown error'),
            ));
        }

        return Answer::of($body);
    }

    /**
     * Ping a host
     *
     * @param string $ipAddress IP address to ping
     * @return \App\Http\Answer Answering with a {@see \App\Agent\Dto\PingResult}.
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
     * @return \App\Http\Answer Answering with a {@see \App\Agent\Dto\DisconnectResult}.
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
