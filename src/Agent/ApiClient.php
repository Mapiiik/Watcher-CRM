<?php
declare(strict_types=1);

namespace App\Agent;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use Radius\Model\Entity\Radacct;
use RuntimeException;
use Throwable;

class ApiClient
{
    /**
     * POST request to Watcher Agent
     *
     * @param string $function
     * @return \Cake\Http\Client\Response
     */
    private static function postRequest(string $function, array $data = [], int $timeout = 30): Response
    {
        $agentUrl = rtrim((string)env('WATCHER_AGENT_URL'), '/');
        $agentToken = (string)env('WATCHER_AGENT_TOKEN');

        // Create HTTP client
        $http = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $agentToken,
                'Accept' => 'application/json',
            ],
            'timeout' => $timeout,
        ]);

        $response = $http->post(
            $agentUrl . '/api/' . $function,
            $data,
            [
                'type' => 'json',
            ],
        );

        return $response;
    }

    /**
     * Ping a host
     *
     * @param string $ipAddress IP address to ping
     * @return array<string, mixed> Ping results
     * @throws \RuntimeException if the request fails or returns an error response
     */
    public static function ping(string $ipAddress): array
    {
        try {
            $response = self::postRequest(
                function: 'ping',
                data: [
                    'host' => $ipAddress,
                    'count' => 10,
                    'timeout_ms' => 1000,
                ],
                timeout: 11,
            );
        } catch (Throwable $e) {
            throw new RuntimeException(__(
                'Watcher Agent is unreachable: {0}',
                $e->getMessage(),
            ));
        }

        if (!$response->isOk()) {
            throw new RuntimeException(__(
                'Watcher Agent returned HTTP {0}',
                $response->getStatusCode(),
            ));
        }

        $data = $response->getJson();

        if (is_array($data) && array_key_exists('reachable', $data)) {
            return $data;
        } else {
            throw new RuntimeException(__(
                'Invalid response from Watcher Agent',
            ));
        }
    }

    /**
     * RADIUS disconnect
     *
     * @param \Radius\Model\Entity\Radacct $session RADIUS Accounting Record.
     * @return array<string, mixed> Response data from Watcher Agent
     * @throws \RuntimeException if the request fails or returns an error response
     */
    public static function radiusDisconnect(Radacct $session): array
    {
        try {
            $response = self::postRequest(
                function: 'radius/disconnect',
                data: [
                    'nas_ip' => $session->nasipaddress,
                    'port' => 1700,
                    'secret' => env('RADIUS_SECRET'),
                    'timeout_ms' => 3000, // 3 seconds

                    'username' => $session->username,
                    'acct_session_id' => $session->acctsessionid,
                    'framed_ip' => $session->framedipaddress,
                ],
                timeout: 10,
            );
        } catch (Throwable $e) {
            throw new RuntimeException(__(
                'Watcher Agent is unreachable: {0}',
                $e->getMessage(),
            ));
        }

        if (!$response->isOk()) {
            throw new RuntimeException(__(
                'Watcher Agent returned HTTP {0}',
                $response->getStatusCode(),
            ));
        }

        $data = $response->getJson();

        if (!is_array($data) || !isset($data['success'])) {
            throw new RuntimeException(__(
                'Invalid response from Watcher Agent',
            ));
        }

        return $data;
    }
}
