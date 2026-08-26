<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;

/**
 * Who is sent what the application has to report - a task closed, accounts changed, readings taken.
 *
 * A report is more than a subject and a body: it has a template, and often a recipient named on
 * the command line instead. So this hands out the addresses and the report builds its own mailer.
 * Failures are not reports and do not come this way; they go to `ErrorReport`, which has an
 * address of its own so that whoever is on call is not sent the overnight paperwork.
 */
final class OperatorReport
{
    /**
     * Who is to be told. Empty means nobody is configured, which leaves the report unsent.
     *
     * @return array<string>
     */
    public static function recipients(): array
    {
        return array_values(array_filter((array)Configure::read('Report.emails', []), is_string(...)));
    }
}
