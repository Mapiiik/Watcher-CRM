<?php
declare(strict_types=1);

namespace Radius\Updater;

use App\Messages\Messages;
use Cake\Http\Client as HttpClient;
use Mapik\RadiusClient\Client as RadiusClient;
use Mapik\RadiusClient\Packet;
use Mapik\RadiusClient\PacketType;
use Radius\Model\Entity\Radacct;
use Throwable;
use UnexpectedValueException;

/**
 * Message
 */
class RadiusRequestSender
{
    /**
     * Messages
     */
    public Messages $Messages;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Messages = new Messages();
    }

    /**
     * Send disconnect request using the configured backend.
     *
     * If Watcher Agent support is enabled the request is sent via the agent.
     * Otherwise, the local RADIUS disconnect mechanism is used.
     *
     * @param \Radius\Model\Entity\Radacct $session RADIUS Accounting Record.
     * @return bool Returns true if the disconnection was successful.
     */
    public function sendDisconnectRequest(Radacct $session): bool
    {
        $agentEnabled = filter_var(
            env('WATCHER_AGENT_ENABLED', false),
            FILTER_VALIDATE_BOOLEAN,
        );

        if ($agentEnabled) {
            return $this->sendDisconnectRequestViaAgent($session);
        } else {
            return $this->sendDisconnectRequestLocal($session);
        }
    }

    /**
     * Send disconnect request method (Local)
     *
     * @param \Radius\Model\Entity\Radacct $session RADIUS Accounting Record.
     * @return bool Returns true if the disconnection was successful.
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function sendDisconnectRequestLocal(Radacct $session): bool
    {
        $disconnected = false;

        $client = new RadiusClient('udp://' . $session->nasipaddress . ':1700', /* timeout */ 3);
        try {
            $response = $client->send(
                new Packet(PacketType::DISCONNECT_REQUEST(), /* secret */ env('RADIUS_SECRET'), [
                    'User-Name' => $session->username,
                    'Acct-Session-Id' => $session->acctsessionid,
                    'Framed-IP-Address' => $session->framedipaddress,
                    'NAS-IP-Address' => $session->nasipaddress,
                ]),
            );
        } catch (Throwable $e) {
            $this->Messages->error(__d(
                'radius',
                'The RADIUS session for {0} started on {1} could not be disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $e->getMessage(),
            ));

            // skip further processing and return false
            return false;
        }

        // detect response type
        switch ($response->getType()) {
            case PacketType::COA_ACK():
                $result = 'CoA-ACK';
                $disconnected = true;
                break;
            case PacketType::DISCONNECT_ACK():
                $result = 'Disconnect-ACK';
                $disconnected = true;
                break;
            case PacketType::COA_NAK():
                $result = 'CoA-NAK';
                break;
            case PacketType::DISCONNECT_NAK():
                $result = 'Disconnect-NAK';
                break;
            default:
                $result = 'Unsupported reply';
        }

        // detect error causes
        $attributes = $response->getAttributes();
        $error = $this->formatDisconnectErrors(
            $attributes['Error-Cause'] ?? [],
        );

        if ($disconnected) {
            $this->Messages->success(__d(
                'radius',
                'The RADIUS session for {0} started on {1} has been disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $error ? $result . ' - ' . $error : $result,
            ));

            return true;
        } else {
            $this->Messages->error(__d(
                'radius',
                'The RADIUS session for {0} started on {1} could not be disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $error ? $result . ' - ' . $error : $result,
            ));

            return false;
        }
    }

    /**
     * Send disconnect request method (via Watcher Agent)
     *
     * @param \Radius\Model\Entity\Radacct $session RADIUS Accounting Record.
     * @return bool Returns true if the disconnection was successful.
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    private function sendDisconnectRequestViaAgent(Radacct $session): bool
    {
        $agentUrl = rtrim((string)env('WATCHER_AGENT_URL'), '/');
        $agentToken = (string)env('WATCHER_AGENT_TOKEN');

        // Create HTTP client
        $http = new HttpClient([
            'headers' => [
                'Authorization' => 'Bearer ' . $agentToken,
                'Accept' => 'application/json',
            ],
            'timeout' => 10,
        ]);

        try {
            $response = $http->post(
                $agentUrl . '/api/radius/disconnect',
                [
                    'nas_ip' => $session->nasipaddress,
                    'port' => 1700,
                    'secret' => env('RADIUS_SECRET'),
                    'timeout_ms' => 3000, // 3 seconds

                    'username' => $session->username,
                    'acct_session_id' => $session->acctsessionid,
                    'framed_ip' => $session->framedipaddress,
                ],
                [
                    'type' => 'json',
                ],
            );
        } catch (Throwable $e) {
            $this->Messages->error(__d(
                'radius',
                'Watcher Agent is unreachable: {0}',
                $e->getMessage(),
            ));

            return false;
        }

        if (!$response->isOk()) {
            $this->Messages->error(__d(
                'radius',
                'Watcher Agent returned HTTP {0}',
                $response->getStatusCode(),
            ));

            return false;
        }

        $data = $response->getJson();

        if (!is_array($data) || !isset($data['success'])) {
            $this->Messages->error(__d(
                'radius',
                'Invalid response from Watcher Agent',
            ));

            return false;
        }

        $error = $this->formatDisconnectErrors(
            $data['error_causes'] ?? [],
        );

        if ($data['success']) {
            $this->Messages->success(__d(
                'radius',
                'The RADIUS session for {0} started on {1} has been disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                trim(($data['result'] ?? 'OK') . ($error ? ' - ' . $error : '')),
            ));

            return true;
        }

        $this->Messages->error(__d(
            'radius',
            'The RADIUS session for {0} started on {1} could not be disconnected ({2}).',
            $session->username,
            $session->acctstarttime,
            trim(($data['result'] ?? 'Error') . ($error ? ' - ' . $error : '')),
        ));

        return false;
    }

    /**
     * This private function formats disconnect errors and returns a string representation of the errors.
     *
     * @param list<mixed> $error_codes The list containing the disconnect errors.
     * @return string The string representation of the disconnect errors.
     */
    private function formatDisconnectErrors(array $error_codes): string
    {
        $errors = [];

        // Loop through the error codes and get the corresponding error message for each code
        foreach ($error_codes as $error_code) {
            if (!is_int($error_code)) {
                throw new UnexpectedValueException(
                    'Disconnect error code must be an integer',
                );
            }
            $errors[] = $this->getDisconnectErrorMessage($error_code);
        }

        // Return a string representation of the errors array, with each error separated by a comma and space
        return implode(', ', $errors);
    }

    /**
     * This function returns the error message based on the error code.
     *
     * @param int $error_code The error code to retrieve the error message for.
     * @return string The error message corresponding to the error code.
     */
    private function getDisconnectErrorMessage(int $error_code): string
    {
        // Define an array of error messages with their corresponding error codes.
        $errorMessages = [
            401 => 'Unsupported Attribute',
            402 => 'Missing Attribute',
            403 => 'NAS Identification Mismatch',
            404 => 'Invalid Request',
            405 => 'Unsupported Service',
            406 => 'Unsupported Extension',
            407 => 'Invalid Attribute Value',
            501 => 'Administratively Prohibited',
            502 => 'Request Not Routable (Proxy)',
            503 => 'Session Context Not Found',
            504 => 'Session Context Not Removable',
            505 => 'Other Proxy Processing Error',
            506 => 'Resources Unavailable',
            507 => 'Request Initiated',
            508 => 'Multiple Session Selection Unsupported',
        ];

        // Return the error message corresponding to the error code, or 'Unsupported Error-Cause' if the error code is not found.
        return $errorMessages[$error_code] ?? 'Unsupported Error-Cause';
    }
}
