<?php
declare(strict_types=1);

namespace Radius\Updater;

use App\Agent\ApiClient as AgentApiClient;
use App\Messages\Messages;
use Radius\Model\Entity\Radacct;
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
     * Send a RADIUS disconnect request via the Watcher Agent.
     *
     * @param \Radius\Model\Entity\Radacct $session RADIUS Accounting Record.
     * @return bool Returns true if the disconnection was successful.
     */
    public function sendDisconnectRequest(Radacct $session): bool
    {
        return $this->sendDisconnectRequestViaAgent($session);
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
        $answer = AgentApiClient::radiusDisconnect($session);

        if (!$answer->ok()) {
            $this->Messages->error(__d(
                'radius',
                'The RADIUS session for {0} started on {1} could not be disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                $answer->failure ?? __d('radius', 'Watcher Agent is not configured.'),
            ));

            // skip further processing and return false
            return false;
        }

        /** @var \App\Agent\Dto\DisconnectResult $result */
        $result = $answer->data;
        $error = $this->formatDisconnectErrors($result->errorCauses);

        if ($result->success) {
            $this->Messages->success(__d(
                'radius',
                'The RADIUS session for {0} started on {1} has been disconnected ({2}).',
                $session->username,
                $session->acctstarttime,
                trim(($result->result ?? 'OK') . ($error ? ' - ' . $error : '')),
            ));

            return true;
        }

        $this->Messages->error(__d(
            'radius',
            'The RADIUS session for {0} started on {1} could not be disconnected ({2}).',
            $session->username,
            $session->acctstarttime,
            trim(($result->result ?? 'Error') . ($error ? ' - ' . $error : '')),
        ));

        return false;
    }

    /**
     * This private function formats disconnect errors and returns a string representation of the errors.
     *
     * @param array<array-key, int> $error_codes The list containing the disconnect errors.
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
