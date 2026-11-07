<?php
declare(strict_types=1);

use Cake\Http\ServerRequest;
use Tools\View\Icon\FontAwesome6Icon;

/*
 * The settings of the queue and of the schedule on top of it. A job runs at most once unless its
 * task says otherwise, because running a job twice writes to the customers twice.
 */
$onlyAdmins = fn(ServerRequest $request): bool => ($request->getAttribute('identity')['role'] ?? null) === 'admin';

// Longer than any job takes. The queue hands a job to another worker once this has passed.
$longestJob = 3 * 60 * 60;

// As long as the logs are kept, since this is where the output of the jobs is now
$retention = 180 * 24 * 60 * 60;

return [
    'Queue' => [
        // A worker that has been at it for a minute goes after its job, the next one starts clean
        'workerLifetime' => 60,
        // PHP counts this in real time here, so anything else would kill a long job
        'workerPhpTimeout' => 0,
        'exitwhennothingtodo' => true,
        // How many run at once is the number of workers in the crontab. A worker that was killed
        // counts against a limit until it has timed out, and would keep the next one from starting.
        'maxworkers' => 0,

        // Every scheduled command is one and the same task, so they all go by these two
        'defaultRequeueTimeout' => $longestJob,
        'defaultJobRetries' => 0,

        'captureOutput' => true,
        'cleanuptimeout' => $retention,
        'cleanupAbortedTimeout' => $retention,

        'isSearchEnabled' => false,
        'isStatisticEnabled' => true,
        'adminAccess' => $onlyAdmins,
        'adminBackUrl' => '/',
    ],

    'QueueScheduler' => [
        // Commands of the application only, nothing a shell would run
        'allowRaw' => false,
        'adminAccess' => $onlyAdmins,
        'adminBackUrl' => '/',
    ],

    // The pages of the schedule ask for these two, from the Font Awesome they load anyway
    'Icon' => [
        'sets' => ['fa' => FontAwesome6Icon::class],
        'map' => ['yes' => 'fa:check', 'no' => 'fa:xmark'],
    ],

    'Scheduler' => [
        // How long a job may look unfinished, and a row may stay overdue, before somebody is told.
        // A row stays overdue while its job waits behind a long one.
        'stuckAfter' => 2 * 60 * 60,
        'overdueAfter' => $longestJob,
        'silentDispatcherAfter' => 10 * 60,
    ],
];
