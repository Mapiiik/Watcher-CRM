<?php
declare(strict_types=1);

namespace App\SledovaniTV;

use App\Http\Answer;
use App\Http\WritesDownFailuresTrait;
use App\SledovaniTV\Provider\TvUserPayloadNormalizer;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use Throwable;

/**
 * Talking to the television service.
 *
 * Every reading comes back as an {@see \App\Http\Answer}. The one caller runs over every viewer
 * the service holds and turns some of them off, so it needs to tell a service that would not
 * answer from one that answered nothing: the first must leave the viewers alone, the second means
 * there are none.
 */
class ApiClient
{
    use WritesDownFailuresTrait;

    /**
     * How long to wait for SledovaniTV, in seconds.
     */
    private const TIMEOUT = 30;

    /**
     * What this service is called in the log.
     */
    private const SERVICE = 'The SledovaniTV API';

    /**
     * Ask the television service to do one thing.
     *
     * @param string $function What to ask for.
     * @param array<string, mixed> $data What to ask it with.
     * @return \App\Http\Answer<array<int|string, mixed>>
     */
    private static function ask(string $function, array $data = []): Answer
    {
        $partner = (string)Configure::read('SledovaniTv.username');

        // Not being configured is a state, not a failure - an installation that sells no
        // television says so by leaving the account empty, and nobody asked.
        if ($partner === '') {
            return Answer::notAsked();
        }

        $http = new Client(['timeout' => self::TIMEOUT]);
        $where = 'https://sledovanitv.cz/partner/api/' . $function;

        try {
            $response = $http->post(
                $where,
                [
                    'partner' => $partner,
                    'password' => Configure::read('SledovaniTv.password'),
                ] + $data,
                [
                    'type' => 'application/x-www-form-urlencoded',
                ],
            );
        } catch (Throwable $e) {
            return self::unreachable(self::SERVICE, $where, $e->getMessage());
        }

        if (!$response->isOk()) {
            // The body says more than the status does and the headers say nothing worth having:
            // they carry the session cookie, and a log is read by more people than a password is.
            return self::refused(
                self::SERVICE,
                $where,
                $response->getStatusCode(),
                trim($response->getReasonPhrase() . ' ' . $response->getStringBody()),
            );
        }

        $body = $response->getJson();

        return is_array($body)
            ? Answer::of($body)
            : self::unexpected(self::SERVICE, $where, 'not an object');
    }

    /**
     * Load SledovaniTV users
     *
     * @return \App\Http\Answer<\Cake\Collection\CollectionInterface<int, \App\SledovaniTV\Dto\TvUser>>
     */
    public static function getUsers(): Answer
    {
        return self::ask('get-users')->map(function (array $body): CollectionInterface {
            // An answer with a status of 200 still need not be the list - a refused login is
            // reported that way - so what is not the list is read as nobody.
            $users = $body['users'] ?? null;

            if (!is_array($users)) {
                Log::warning('The SledovaniTV API answered `get-users` without a list of users in it.');

                return new Collection([]);
            }

            return TvUserPayloadNormalizer::users($users);
        });
    }

    /**
     * Suspend user
     *
     * @param string $id User ID
     * @return \App\Http\Answer<bool>
     */
    public static function suspendUser(string $id): Answer
    {
        return self::ask('suspend-user', ['userId' => $id])
            ->map(fn(array $body): bool => (bool)($body['suspended'] ?? false));
    }

    /**
     * Unsuspend user
     *
     * @param string $id User ID
     * @return \App\Http\Answer<bool>
     */
    public static function unsuspendUser(string $id): Answer
    {
        return self::ask('unsuspend-user', ['userId' => $id])
            ->map(fn(array $body): bool => (bool)($body['activated'] ?? false));
    }
}
