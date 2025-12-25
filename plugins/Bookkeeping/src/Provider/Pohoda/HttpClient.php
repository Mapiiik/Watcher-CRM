<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use RuntimeException;
use Throwable;

/**
 * Class HttpClient
 *
 * Handles HTTP communication with Pohoda mServer.
 * Encapsulates authentication, headers and request sending.
 */
class HttpClient
{
    /**
     * Send XML request to Pohoda mServer.
     *
     * @param string $xml XML request body.
     * @return \Cake\Http\Client\Response
     */
    public function send(string $xml): Response
    {
        try {
            $username = (string)env('POHODA_USERNAME', '');
            $password = (string)env('POHODA_PASSWORD', '');
            $url = (string)env('POHODA_MSERVER_URL', 'http://localhost:44444');
            $timeout = 3600;

            $http = new Client([
                'headers' => [
                    'STW-Application' => 'Watcher CRM',
                    'STW-Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
                    'Content-Type' => 'application/xml',
                    'Accept' => 'application/xml',
                ],
                'timeout' => $timeout,
            ]);

            return $http->post($url . '/xml', $xml);
        } catch (Throwable $e) {
            throw new RuntimeException(
                __d(
                    'bookkeeping',
                    'Error connecting to Pohoda mServer: {0}',
                    [$e->getMessage()],
                ),
                previous: $e,
            );
        }
    }
}
