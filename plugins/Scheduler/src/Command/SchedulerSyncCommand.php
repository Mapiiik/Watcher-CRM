<?php
declare(strict_types=1);

namespace Scheduler\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Override;
use Scheduler\Service\Schedule;

/**
 * Adds the rows of the schedule a new version brings. Runs with every start of the application.
 */
class SchedulerSyncCommand extends Command
{
    /**
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'scheduler sync';
    }

    /**
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Add the rows of the schedule that the application describes and this installation lacks.';
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        foreach ((new Schedule())->addMissing(Schedule::defaults()) as $name) {
            $io->out(__d('scheduler', 'Added to the schedule: {0}', $name));
        }

        return static::CODE_SUCCESS;
    }
}
