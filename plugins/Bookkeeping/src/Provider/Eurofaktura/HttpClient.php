<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Eurofaktura;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use RuntimeException;
use Settings\Utility\Settings;
use Throwable;

/**
 * Class HttpClient
 *
 * Handles HTTP communication with Eurofaktura / E-racuni API.
 */
class HttpClient
{
    /**
     * Send request to Eurofaktura / E-racuni API.
     *
     * @param \Bookkeeping\Provider\Eurofaktura\EurofakturaCredentials $credentials Authentication credentials.
     * @param string $method API method name (e.g. SalesInvoiceCreate)
     * @param array $parameters Method parameters
     * @return \Cake\Http\Client\Response
     */
    public function send(
        EurofakturaCredentials $credentials,
        string $method,
        array $parameters = [],
    ): Response {
        try {
            $url = Settings::getString(
                EurofakturaProvider::SETTINGS_ROOT . '.api.url',
                'https://e-racuni.com/H7i/API',
            );
            $timeout = (int)Settings::get(
                EurofakturaProvider::SETTINGS_ROOT . '.api.timeout',
                3600,
            );

            $payload = [
                'username' => $credentials->username,
                'secretKey' => $credentials->secretKey,
                'token' => $credentials->token,
                'method' => $method,
                'parameters' => $parameters,
            ];

            $http = new Client([
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => $timeout,
            ]);

            return $http->post($url, json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (Throwable $e) {
            throw new RuntimeException(__d(
                'bookkeeping',
                'Error connecting to Eurofaktura / E-racuni API: {0}',
                [$e->getMessage()],
            ), $e->getCode(), previous: $e);
        }
    }
}
