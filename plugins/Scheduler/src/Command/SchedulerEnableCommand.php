<?php
declare(strict_types=1);

namespace Scheduler\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Override;
use Scheduler\Service\Schedule;

/**
 * Switches rows of the schedule on, each to wait for its next time.
 */
class SchedulerEnableCommand extends Command
{
    /**
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'scheduler enable';
    }

    /**
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Switch on the rows of the schedule of the given names.';
    }

    /**
     * Whether this command switches rows on. The other one is the same but for this.
     *
     * @return bool
     */
    protected function enables(): bool
    {
        return true;
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $schedule = new Schedule();
        $code = static::CODE_SUCCESS;

        foreach (array_filter($args->getArguments(), is_string(...)) as $name) {
            $row = $schedule->switchRow($name, $this->enables());

            if ($row === null) {
                $io->error(__d('scheduler', 'There is no row of that name: {0}', $name));
                $code = static::CODE_ERROR;
            } elseif ($this->enables()) {
                $io->out(__d('scheduler', '{0}: on, next run at {1}', $name, (string)$row->next_run));
            } else {
                $io->out(__d('scheduler', '{0}: off', $name));
            }
        }

        return $code;
    }
}
