<?php
declare(strict_types=1);

namespace Scheduler\Model\Enum;

/**
 * How the scheduler is doing, as far as anything here can tell.
 */
enum SchedulerState: string
{
    /**
     * Something is wrong, and the problems say what.
     */
    case Attention = 'attention';

    /**
     * The dispatcher has run lately and nothing is held up.
     */
    case Running = 'running';

    /**
     * Nothing says either way - what the first minute after a deployment looks like.
     */
    case Unknown = 'unknown';
}
