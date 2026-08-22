<?php
declare(strict_types=1);

namespace App\Test\TestCase\Http;

use App\Http\Answer;
use App\Http\WritesDownFailuresTrait;

/**
 * A client whose every reading comes to nothing.
 *
 * The trait is what every client of this application reports a failure through, and what it does
 * besides reporting - writing the reason down - is the thing worth a test. Standing in for a real
 * client keeps that test off the network and off any one service's vocabulary.
 */
class FailingClient
{
    use WritesDownFailuresTrait;

    /**
     * A reading that came to nothing, said as loudly as an outage.
     *
     * @return \App\Http\Answer<never>
     */
    public static function readSomething(): Answer
    {
        return self::unanswered('The other side did not answer.');
    }

    /**
     * The same, said as quietly as the client thinks it deserves.
     *
     * @return \App\Http\Answer<never>
     */
    public static function readSomethingUnremarkable(): Answer
    {
        return self::unanswered('Nothing came of that either.', 'warning');
    }
}
