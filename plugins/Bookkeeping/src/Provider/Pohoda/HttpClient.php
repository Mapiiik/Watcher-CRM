<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Pohoda;

use App\Http\Answer;
use Cake\Core\Configure;
use Cake\Http\Client;
use Settings\Utility\Settings;
use SimpleXMLElement;
use Throwable;

/**
 * Class HttpClient
 *
 * Handles HTTP communication with Pohoda mServer.
 * Encapsulates authentication, headers and request sending.
 *
 * The reading comes back as an {@see \App\Http\Answer}, so the caller says what a failure is
 * worth. Reading the XML itself stays with the parsers: what mServer answers is the accounting
 * system's own vocabulary and is none of this class's business.
 */
class HttpClient
{
    /**
     * Send XML request to Pohoda mServer.
     *
     * @param string $xml XML request body.
     * @return \App\Http\Answer<\SimpleXMLElement>
     */
    public function send(string $xml): Answer
    {
        $url = Settings::getString(
            PohodaProvider::SETTINGS_ROOT . '.api.url',
            'http://localhost:44444',
        );

        if ($url === '') {
            return Answer::notAsked();
        }

        try {
            $username = (string)Configure::read('Bookkeeping.pohoda.username');
            $password = (string)Configure::read('Bookkeeping.pohoda.password');
            $timeout = (int)Settings::get(
                PohodaProvider::SETTINGS_ROOT . '.api.timeout',
                3600,
            );

            $http = new Client([
                'headers' => [
                    'STW-Application' => 'Watcher CRM',
                    'STW-Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
                    'Content-Type' => 'application/xml',
                    'Accept' => 'application/xml',
                ],
                'timeout' => $timeout,
            ]);

            $response = $http->post($url . '/xml', $xml);
        } catch (Throwable $e) {
            return Answer::failed(__d(
                'bookkeeping',
                'Error connecting to Pohoda mServer: {0}',
                [$e->getMessage()],
            ));
        }

        if (!$response->isOk()) {
            return Answer::failed(__d(
                'bookkeeping',
                'Invalid response from Pohoda mServer ({0})',
                [$response->getReasonPhrase()],
            ));
        }

        $body = $response->getXml();

        if (!$body instanceof SimpleXMLElement) {
            return Answer::failed(__d('bookkeeping', 'Invalid XML response from Pohoda mServer.'));
        }

        return Answer::of($body);
    }
}
