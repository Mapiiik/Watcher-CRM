<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Eurofaktura;

use App\Http\Answer;
use App\Http\WritesDownFailuresTrait;
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
    use WritesDownFailuresTrait;

    /**
     * What this service is called in the log.
     */
    private const SERVICE = 'The Eurofaktura API';

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
            return self::unreachable(self::SERVICE, $url, $e->getMessage());
        }

        $body = $response->getJson();

        // The accounting system answers a question it did not care for with a 500 and the reason
        // inside the body - "no documents found" among them, which is an answer and not a failure.
        // So the body is the verdict here, and the status only speaks when none came with it.
        if (is_array($body)) {
            return Answer::of($body);
        }

        if (!$response->isOk()) {
            return self::refused(self::SERVICE, $url, $response->getStatusCode());
        }

        return self::unexpected(self::SERVICE, $url, 'not an object');
    }
}
