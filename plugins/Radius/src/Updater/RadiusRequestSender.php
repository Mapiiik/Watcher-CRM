<?php
declare(strict_types=1);

namespace Radius\Updater;

use App\Messages\Messages;
use Mapik\RadiusClient\Client;
use Mapik\RadiusClient\Exceptions\ClientException;
use Mapik\RadiusClient\Packet;
use Mapik\RadiusClient\PacketType;
use Radius\Model\Entity\Radacct;

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
     * Send disconnect request method
     *
     * @param \Radius\Model\Entity\Radacct $session RADIUS Accounting Record.
     * @return bool Returns true if the disconnection was successful.
     */
    public function sendDisconnectRequest(Radacct $session): bool
    {
        $disconnected = false;

        $client = new Client('udp://' . $session->nasipaddress . ':1700', /* timeout */ 3);
        try {
            $response = $client->send(
                new Packet(PacketType::DISCONNECT_REQUEST(), /* secret */ env('RADIUS_SECRET'), [
                    'User-Name' => $session->username,
                    'Acct-Session-Id' => $session->acctsessionid,
                    'Framed-IP-Address' => $session->framedipaddress,
                    'NAS-IP-Address' => $session->nasipaddress,
                ])
            );
        } catch (ClientException $e) {
            $this->Messages->error(__d(
                'radius',
                'The RADIUS session for {0} started on {1} could not be disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $e->getMessage()
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
        $error = $this->handleDisconnectErrors($response->getAttributes());

        if ($disconnected) {
            $this->Messages->success(__d(
                'radius',
                'The RADIUS session for {0} started on {1} has been disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $error ? $result . ' - ' . $error : $result
            ));

            return true;
        } else {
            $this->Messages->error(__d(
                'radius',
                'The RADIUS session for {0} started on {1} could not be disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $error ? $result . ' - ' . $error : $result
            ));

            return false;
        }
    }

    /**
     * This private function handles disconnect errors and returns a string representation of the errors.
     *
     * @param array<string, mixed[]> $attributes The attributes containing the disconnect errors.
     * @return string The string representation of the disconnect errors.
     */
    private function handleDisconnectErrors(array $attributes): string
    {
        $errors = [];

        // Check if the 'Error-Cause' attribute exists and is an array
        if (isset($attributes['Error-Cause']) && is_array($attributes['Error-Cause'])) {
            foreach ($attributes['Error-Cause'] as $error_code) {
                $errors[] = $this->getDisconnectErrorMessage($error_code);
            }
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
