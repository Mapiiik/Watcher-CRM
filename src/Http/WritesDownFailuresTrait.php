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
 * Three ways for a reading to come to nothing, and all of them are said the same way:
 *
 *     Watcher NMS is unreachable (https://nms.example.com/api/access-points.json): cURL Error (28)
 *     The CZ - ARES register answered 503 (https://ares.gov.cz/.../vyhledat)
 *     Watcher Agent answered something unexpected (https://agent.example.com/api/ping): no verdict
 *
 * The service is named because a line saying only that something was unreachable is no use in a
 * file where six services can be, and the address because it says which of them was actually
 * asked - a setting pointing at the wrong deployment reads exactly like an outage otherwise.
 *
 * The address never carries the question. Watcher NMS is asked for its key as a query parameter,
 * and a log is read by more people than a configuration file is.
 *
 * How loudly is the client's to say. Being unable to reach a service is an outage; an answer that
 * arrived and was not the one expected is a misunderstanding; and one municipality of forty
 * refusing is a Tuesday.
 */
trait WritesDownFailuresTrait
{
    /**
     * A service that could not be reached at all.
     *
     * @param string $service What the service is called.
     * @param string $where The address it was asked at, without the question.
     * @param string $reason What the transport said.
     * @param string $level How loudly to say it.
     * @return \App\Http\Answer<never>
     */
    protected static function unreachable(
        string $service,
        string $where,
        string $reason,
        string $level = 'error',
    ): Answer {
        return self::unanswered(sprintf('%s is unreachable (%s): %s', $service, $where, $reason), $level);
    }

    /**
     * A service that answered, and answered no.
     *
     * @param string $service What the service is called.
     * @param string $where The address it was asked at, without the question.
     * @param int $status What it answered with.
     * @param string|null $reason What it said about that, where it said anything.
     * @param string $level How loudly to say it.
     * @return \App\Http\Answer<never>
     */
    protected static function refused(
        string $service,
        string $where,
        int $status,
        ?string $reason = null,
        string $level = 'error',
    ): Answer {
        return self::unanswered(
            sprintf('%s answered %d (%s)', $service, $status, $where)
                . ($reason === null || $reason === '' ? '' : ': ' . $reason),
            $level,
        );
    }

    /**
     * A service that answered something, but not an answer to this.
     *
     * @param string $service What the service is called.
     * @param string $where The address it was asked at, without the question.
     * @param string $reason What was wrong with what it said.
     * @param string $level How loudly to say it.
     * @return \App\Http\Answer<never>
     */
    protected static function unexpected(
        string $service,
        string $where,
        string $reason,
        string $level = 'error',
    ): Answer {
        return self::unanswered(
            sprintf('%s answered something unexpected (%s): %s', $service, $where, $reason),
            $level,
        );
    }

    /**
     * A question that went unanswered, written down on the way out.
     *
     * The three above are the shapes worth having; this is for the reading whose failure is none
     * of them - a service that would not issue a token, say.
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
