<?php
declare(strict_types=1);

namespace Scheduler\Command;

use App\Service\ErrorReport;
use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\Date;
use Override;
use Scheduler\Service\SchedulerHealth;

/**
 * Notices what the failure of a job does not report: a worker that died, a job that never ends,
 * a dispatcher that has gone quiet.
 *
 * It runs from the crontab rather than from the queue, because the one worker it is watching
 * would otherwise have to be free for it to run at all.
 */
class SchedulerCheckCommand extends Command
{
    /**
     * @return string
     */
    #[Override]
    public static function defaultName(): string
    {
        return 'scheduler check';
    }

    /**
     * @return string
     */
    #[Override]
    public static function getDescription(): string
    {
        return 'Report the jobs that are stuck, the rows that are overdue and a dispatcher gone quiet.';
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    #[Override]
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $problems = (new SchedulerHealth())->problems();

        if ($problems === []) {
            return static::CODE_SUCCESS;
        }

        foreach ($problems as $problem) {
            $io->warning($problem);
        }

        $news = array_filter($problems, $this->isNewToday(...));

        if ($news !== []) {
            ErrorReport::send(__d('scheduler', 'The scheduler needs attention'), implode("\n", $news));
        }

        return static::CODE_ERROR;
    }

    /**
     * One report a day per problem, since this runs far more often than anybody could respond.
     *
     * @param string $problem What is wrong.
     * @return bool
     */
    private function isNewToday(string $problem): bool
    {
        return Cache::add('scheduler_reported_' . md5(Date::today()->format('Y-m-d') . $problem), true);
    }
}
