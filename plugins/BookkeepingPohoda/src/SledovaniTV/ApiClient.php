<?php
declare(strict_types=1);

namespace BookkeepingPohoda\SledovaniTV;

use Cake\Http\Client;
use Cake\Http\Client\Response;
use Exception;

class ApiClient
{
    /**
     * POST request to SledovaniTV
     *
     * @param string $function
     * @return \Cake\Http\Client\Response
     * @throws \Exception When data cannot be retrieved from the SledovaniTV API.
     */
    private static function postRequest(string $function, array $data = []): Response {
        $http = new Client();

        $response = $http->post(
            'https://sledovanitv.cz/partner/api/' . $function,
            [
                'partner' => env('DEBTORS_SLEDOVANITV_USERNAME', ''),
                'password' => env('DEBTORS_SLEDOVANITV_PASSWORD', ''),
            ] + $data,
            [
                'type' => 'application/x-www-form-urlencoded',
            ],
        );

        if ($response->isOk()) {
            return $response;
        } else {
            throw new Exception('Error while communicating with the SledovaniTV API.');
        }
    }

    /**
     * Load SledovaniTV users
     *
     * @return array<int, mixed> List of SledovaniTV users
     */
    public static function getUsers(): array {
        $response = self::postRequest('get-users');
        $data = $response->getJson();

        if (is_array($data['users'])) {
            return $data['users'];
        } else {
            return [];
        }
    }

    /**
     * Suspend user
     *
     * @param int $id User ID
     * @return bool True if succesfully suspended
     */
    public static function suspendUser(int $id): bool {
        $response = self::postRequest('suspend-user', ['userId' => $id]);
        $data = $response->getJson();

        return (bool)$data['suspended'];
    }

    /**
     * Unsuspend user
     *
     * @param int $id User ID
     * @return bool True if succesfully activated
     */
    public static function unsuspendUser(int $id): bool {
        $response = self::postRequest('unsuspend-user', ['userId' => $id]);
        $data = $response->getJson();

        return (bool)$data['activated'];
    }
}