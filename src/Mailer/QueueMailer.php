<?php
declare(strict_types=1);

namespace App\Mailer;

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
     * @param array<string> $data
     */
    public function serviceChange(array $emails, array $data): void
    {
        $this
            ->setTo($emails)
            ->setSubject(
                sprintf(
                    'NETAIR - změna služeb od %s na Vaší přípojce č. %s',
                    $data['new_billing_from'],
                    $data['contract_number']
                )
            )
            ->setViewVars(['data' => $data]);
    }
}
