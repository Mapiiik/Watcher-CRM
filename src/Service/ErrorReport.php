<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Log\Log;
use Cake\Mailer\Mailer;

/**
 * Tells whoever is on call that something running unattended has failed.
 *
 * Its own address, apart from the one the application's reports go to (`OperatorReport`), because
 * whoever is woken by a failure is rarely whoever reads what was invoiced overnight.
 *
 * Nobody configured is a deployment's choice rather than an error, so the report is left unsent
 * and noted in the log. Reporting a failure by throwing another one would lose the failure that
 * was worth reporting.
 */
final class ErrorReport
{
    /**
     * Who is to be told. Empty means nobody is configured.
     *
     * @return array<string>
     */
    public static function recipients(): array
    {
        return array_values(array_filter((array)Configure::read('Report.errorEmails', []), is_string(...)));
    }

    /**
     * Send the report, if there is anybody to send it to.
     *
     * @param string $subject What has happened.
     * @param string $body What is known about it.
     * @return bool Whether it was sent.
     */
    public static function send(string $subject, string $body): bool
    {
        $recipients = self::recipients();

        if ($recipients === []) {
            Log::warning('Nobody is configured to be told, so this went unreported: ' . $subject);

            return false;
        }

        $mailer = new Mailer('default');

        foreach ($recipients as $recipient) {
            $mailer->addTo($recipient);
        }

        $mailer->setSubject($subject);
        $mailer->deliver($body);

        return true;
    }
}
