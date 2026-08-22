<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Eurofaktura;

use App\Http\Answer;
use Cake\Http\Client;
use Settings\Utility\Settings;
use Throwable;

/**
 * Class HttpClient
 *
 * Handles HTTP communication with Eurofaktura / E-racuni API.
 *
 * The reading comes back as an {@see \App\Http\Answer}, so the caller says what a failure is
 * worth. Reading the answer itself stays with the parser: what the accounting system says is its
 * own vocabulary and is none of this class's business.
 */
class HttpClient
{
    /**
     * Send request to Eurofaktura / E-racuni API.
     *
     * @param \Bookkeeping\Provider\Eurofaktura\EurofakturaCredentials $credentials Authentication credentials.
     * @param string $method API method name (e.g. SalesInvoiceCreate)
     * @param array $parameters Method parameters
     * @return \App\Http\Answer<array<mixed>>
     */
    public function send(
        EurofakturaCredentials $credentials,
        string $method,
        array $parameters = [],
    ): Answer {
        $url = Settings::getString(
            EurofakturaProvider::SETTINGS_ROOT . '.api.url',
            'https://e-racuni.com/H7i/API',
        );

        if ($url === '') {
            return Answer::notAsked();
        }

        try {
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

            $response = $http->post($url, json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (Throwable $e) {
            return Answer::failed(__d(
                'bookkeeping',
                'Error connecting to Eurofaktura / E-racuni API: {0}',
                [$e->getMessage()],
            ));
        }

        $body = $response->getJson();
        $body = is_array($body) ? $body : [];

        // The transport-level verdict travels with the body: the accounting system explains a
        // refusal inside the answer, and the caller wants both to say what went wrong.
        if (!$response->isOk()) {
            $description = $body['response']['description'] ?? __d('bookkeeping', 'Unknown error');

            return Answer::failed(__d(
                'bookkeeping',
                'Eurofaktura API error ({0}, {1})',
                [$response->getStatusCode(), str_replace(["\r", "\n"], ' ', (string)$description)],
            ));
        }

        return Answer::of($body);
    }
}
