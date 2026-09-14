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

    /**
     * What is wrong with the links a report is about to send, if anything.
     *
     * A report goes out from the command line, where there is no request to take the host from.
     * Asking for a full link there without `App.fullBaseUrl` written down does not fail: it
     * quietly produces a relative one, which is a dead link once it is in somebody's mail. A
     * scheduled report can carry those for months before anybody clicks one, so it is said out
     * loud at the moment of sending rather than left to be discovered.
     *
     * @return string|null The complaint, or null when the links will be whole.
     */
    public static function linkWarning(): ?string
    {
        $base = Configure::read('App.fullBaseUrl');

        if (is_string($base) && trim($base) !== '') {
            return null;
        }

        return __(
            'APP_FULL_BASE_URL is not set, so the links in this report are relative and will not'
            . ' lead anywhere from somebody\'s mail.',
        );
    }
}
