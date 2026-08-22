<?php
declare(strict_types=1);

namespace App\Http;

use Cake\Log\Log;

/**
 * Writing down a reading that came to nothing.
 *
 * A client reports rather than throws, so a failure it hands back may be turned into a word to the
 * operator, or quietly passed over by a run that can go on without it - and either way there would
 * be nothing left of it afterwards. What actually went wrong is worth keeping: an operator asking
 * why a page was empty last Tuesday has only the log to go on.
 *
 * The message is written for the log rather than for the operator, and it names the service it is
 * about, because a line saying only that something was unreachable is no use in a file where six
 * services can be.
 *
 * How loudly is the client's to say. Being unable to reach a service is an outage; an answer that
 * arrived and was not the one expected is a misunderstanding, and one municipality of forty
 * refusing is a Tuesday.
 */
trait WritesDownFailuresTrait
{
    /**
     * A question that went unanswered, written down on the way out.
     *
     * @param string $why What went wrong, in a line fit for a log.
     * @param string $level How loudly to say it.
     * @return \App\Http\Answer<never>
     */
    protected static function unanswered(string $why, string $level = 'error'): Answer
    {
        Log::write($level, $why);

        return Answer::failed($why);
    }
}
