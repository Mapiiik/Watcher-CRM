<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Utility\Settings;
use Cake\Mailer\Mailer;
use Cake\Queue\Mailer\QueueTrait;

/**
 * Queue mailer.
 */
class QueueMailer extends Mailer
{
    use QueueTrait;

    /**
     * Mailer's name.
     *
     * @var string
     */
    public static string $name = 'Queue';

    /**
     * Service Change
     *
     * @param array<string> $emails
     * @param array<string|int|float> $data
     */
    public function serviceChange(array $emails, array $data): void
    {
        $this
            ->setTo($emails)
            ->setSubject(
                strtr(
                    Settings::getString('core.emails.service_change.subject'),
                    [
                        '{new_billing_from}' => $data['new_billing_from'],
                        '{contract_number}' => $data['contract_number'],
                    ],
                ),
            )
            ->setViewVars(['data' => $data]);
    }
}
