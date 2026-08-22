<?php
declare(strict_types=1);

namespace App\SledovaniTV;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Log\Log;
use RuntimeException;
use Throwable;

class ApiClient
{
    /**
     * How long to wait for SledovaniTV, in seconds.
     */
    private const TIMEOUT = 30;

    /**
     * POST request to SledovaniTV
     *
     * @return \Cake\Http\Client\Response
     * @throws \RuntimeException When data cannot be retrieved from the SledovaniTV API.
     */
    private static function postRequest(string $function, array $data = []): Response
    {
        $http = new Client(['timeout' => self::TIMEOUT]);

        try {
            $response = $http->post(
                'https://sledovanitv.cz/partner/api/' . $function,
                [
                    'partner' => Configure::read('SledovaniTv.username'),
                    'password' => Configure::read('SledovaniTv.password'),
                ] + $data,
                [
                    'type' => 'application/x-www-form-urlencoded',
                ],
            );
        } catch (Throwable $e) {
            throw new RuntimeException(
                __('The SledovaniTV API is unreachable: {0}', $e->getMessage()),
                $e->getCode(),
                previous: $e,
            );
        }

        if ($response->isOk()) {
            return $response;
        }
        // The headers are left out on purpose: they carry the session cookie, and a log is read by
        // more people than a password is.
        Log::error(
            'Invalid response from SledovaniTV API: '
                . json_encode(
                    [
                        'status' => $response->getStatusCode(),
                        'reason' => $response->getReasonPhrase(),
                        'body' => $response->getStringBody(),
                    ],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
                ),
        );
        throw new RuntimeException('Error while communicating with the SledovaniTV API.');
    }

    /**
     * Load SledovaniTV users
     *
     * @return array<int, mixed> List of SledovaniTV users
     */
    public static function getUsers(): array
    {
        $response = self::postRequest('get-users');
        $data = $response->getJson();

        // An answer with a status of 200 still need not be the list - a refused login is reported
        // that way - so what is not the list is read as nobody rather than taken apart.
        if (is_array($data) && isset($data['users']) && is_array($data['users'])) {
            return $data['users'];
        }

        Log::warning('The SledovaniTV API answered `get-users` without a list of users in it.');

        return [];
    }

    /**
     * Suspend user
     *
     * @param int $id User ID
     * @return bool True if succesfully suspended
     */
    public static function suspendUser(int $id): bool
    {
        $response = self::postRequest('suspend-user', ['userId' => $id]);
        $data = $response->getJson();

        return is_array($data) && (bool)($data['suspended'] ?? false);
    }

    /**
     * Unsuspend user
     *
     * @param int $id User ID
     * @return bool True if succesfully activated
     */
    public static function unsuspendUser(int $id): bool
    {
        $response = self::postRequest('unsuspend-user', ['userId' => $id]);
        $data = $response->getJson();

        return is_array($data) && (bool)($data['activated'] ?? false);
    }
}
