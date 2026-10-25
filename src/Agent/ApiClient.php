<?php
declare(strict_types=1);

namespace App\Agent;

use App\Agent\Provider\AgentPayloadNormalizer;
use App\Http\Answer;
use App\Http\WritesDownFailuresTrait;
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
    use WritesDownFailuresTrait;

    /**
     * What this service is called in the log.
     */
    private const SERVICE = 'Watcher Agent';

    /**
     * Ask the agent to do one thing.
     *
     * @param string $function API function to call (e.g., 'radius/disconnect')
     * @param array<string, mixed> $data Data to send in the request body
     * @param int $timeout Timeout in seconds
     * @param string $expect The field the answer must carry to be one at all.
     * @return \App\Http\Answer<array<int|string, mixed>>
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

        $verify = Configure::read('Agent.verifyTls') !== false;

        $http = new Client([
            'headers' => [
                'Authorization' => 'Bearer ' . $agentToken,
                'Accept' => 'application/json',
            ],
            'timeout' => $timeout,
            // the name is checked by `ssl_verify_peer_name` in the stream adapter, by
            // `ssl_verify_host` in curl
            'ssl_verify_peer' => $verify,
            'ssl_verify_peer_name' => $verify,
            'ssl_verify_host' => $verify,
        ]);

        $where = $agentUrl . '/api/' . $function;

        try {
            $response = $http->post($where, $data, ['type' => 'json']);
        } catch (Throwable $e) {
            return self::unreachable(self::SERVICE, $where, $e->getMessage());
        }

        $body = $response->getJson();
        $message = is_array($body) ? ($body['message'] ?? null) : null;
        $message = is_scalar($message) ? (string)$message : null;

        if (!$response->isOk()) {
            return self::refused(self::SERVICE, $where, $response->getStatusCode(), $message);
        }

        // An answer with no verdict in it is not a verdict of no; it is an answer to a different
        // question, and reading it as one would report a host as unreachable that was never asked.
        if (!is_array($body) || ($expect !== '' && !isset($body[$expect]))) {
            return self::unexpected(
                self::SERVICE,
                $where,
                $message ?? sprintf('no `%s` in it', $expect),
            );
        }

        return Answer::of($body);
    }

    /**
     * Ping a host
     *
     * @param string $ipAddress IP address to ping
     * @return \App\Http\Answer<\App\Agent\Dto\PingResult>
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
     * Make a firewall address list on a router hold exactly the given addresses.
     *
     * @param string $host The router.
     * @param string $list The address list.
     * @param array<string, string> $entries Comment by address or network, IPv4 and IPv6 alike.
     * @return \App\Http\Answer<\App\Agent\Dto\AddressListChanges>
     */
    public static function syncAddressList(string $host, string $list, array $entries): Answer
    {
        return self::changeAddressList('sync', [
            'host' => $host,
            'list' => $list,
            'entries' => self::addressListEntries($entries),
        ]);
    }

    /**
     * Put addresses on a firewall address list of a router, leaving the rest of it alone.
     *
     * @param string $host The router.
     * @param string $list The address list.
     * @param array<string, string> $entries Comment by address or network, IPv4 and IPv6 alike.
     * @return \App\Http\Answer<\App\Agent\Dto\AddressListChanges>
     */
    public static function addToAddressList(string $host, string $list, array $entries): Answer
    {
        return self::changeAddressList('add', [
            'host' => $host,
            'list' => $list,
            'entries' => self::addressListEntries($entries),
        ]);
    }

    /**
     * Take addresses off a firewall address list of a router.
     *
     * @param string $host The router.
     * @param string $list The address list.
     * @param array<string> $addresses Addresses or networks, IPv4 and IPv6 alike.
     * @return \App\Http\Answer<\App\Agent\Dto\AddressListChanges>
     */
    public static function removeFromAddressList(string $host, string $list, array $addresses): Answer
    {
        return self::changeAddressList('remove', [
            'host' => $host,
            'list' => $list,
            'addresses' => array_values($addresses),
        ]);
    }

    /**
     * Take a station off the access lists of an access point, so that it has to be accepted again.
     *
     * @param string $host The access point.
     * @param string $macAddress The station.
     * @return \App\Http\Answer<\App\Agent\Dto\AccessListChanges>
     */
    public static function removeFromAccessList(string $host, string $macAddress): Answer
    {
        return self::ask(
            function: 'routeros/access-list/remove',
            data: [
                'host' => $host,
                'mac_address' => $macAddress,
            ],
            timeout: 15,
            expect: 'removed',
        )->map(AgentPayloadNormalizer::accessList(...));
    }

    /**
     * One of the address list operations. A whole list may take a while on a router that has much to
     * correct, hence the longer wait.
     *
     * @param string $operation `sync`, `add` or `remove`.
     * @param array<string, mixed> $data What to send.
     * @return \App\Http\Answer<\App\Agent\Dto\AddressListChanges>
     */
    private static function changeAddressList(string $operation, array $data): Answer
    {
        return self::ask(
            function: 'routeros/address-list/' . $operation,
            data: $data,
            timeout: 60,
            expect: 'removed',
        )->map(AgentPayloadNormalizer::addressList(...));
    }

    /**
     * @param array<string, string> $entries Comment by address.
     * @return list<array<string, string>>
     * @phpstan-return list<array{address: string, comment: string}>
     */
    private static function addressListEntries(array $entries): array
    {
        $list = [];
        foreach ($entries as $address => $comment) {
            $list[] = ['address' => (string)$address, 'comment' => $comment];
        }

        return $list;
    }

    /**
     * RADIUS disconnect
     *
     * @param \Radius\Model\Entity\Radacct $session RADIUS Accounting Record.
     * @return \App\Http\Answer<\App\Agent\Dto\DisconnectResult>
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
