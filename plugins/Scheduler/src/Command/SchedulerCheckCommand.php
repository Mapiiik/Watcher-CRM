<?php
declare(strict_types=1);

namespace Scheduler\Command;

use App\Service\ErrorReport;
use Cake\Cache\Cache;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Override;
use Queue\Model\Table\QueuedJobsTable;
use QueueScheduler\Command\RunCommand;

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
        $problems = [...$this->stuckJobs(), ...$this->overdueRows(), ...$this->quietDispatcher()];

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
     * @return list<string>
     */
    private function stuckJobs(): array
    {
        $since = DateTime::now()->subSeconds((int)Configure::read('Scheduler.stuckAfter'));

        /** @var iterable<\Queue\Model\Entity\QueuedJob> $jobs */
        $jobs = $this->fetchTable('Queue.QueuedJobs')
            ->find()
            ->where([
                'fetched <' => $since,
                'completed IS' => null,
                'OR' => ['status IS' => null, 'status !=' => QueuedJobsTable::STATUS_ABORTED],
            ])
            ->all();

        $problems = [];

        foreach ($jobs as $job) {
            $problems[] = __d(
                'scheduler',
                'Job {0} ({1}) was started at {2} and has not finished.',
                $job->id,
                $job->reference ?? $job->job_task,
                (string)$job->fetched,
            );
        }

        return $problems;
    }

    /**
     * @return list<string>
     */
    private function overdueRows(): array
    {
        $since = DateTime::now()->subSeconds((int)Configure::read('Scheduler.overdueAfter'));

        /** @var iterable<\QueueScheduler\Model\Entity\SchedulerRow> $rows */
        $rows = $this->fetchTable('QueueScheduler.SchedulerRows')
            ->find()
            ->where(['enabled' => true, 'next_run <' => $since])
            ->all();

        $problems = [];

        foreach ($rows as $row) {
            $problems[] = __d(
                'scheduler',
                'Row {0} was due at {1} and has not run.',
                $row->name,
                (string)$row->next_run,
            );
        }

        return $problems;
    }

    /**
     * Nothing known says nothing: the cache is emptied by every deployment, and an overdue row
     * tells of a dispatcher that has been gone for long.
     *
     * @return list<string>
     */
    private function quietDispatcher(): array
    {
        $config = (string)(Configure::read('QueueScheduler.cacheConfig') ?? 'default');
        $beat = Cache::read(RunCommand::HEARTBEAT_KEY, $config);

        if (!is_int($beat) || time() - $beat < (int)Configure::read('Scheduler.silentDispatcherAfter')) {
            return [];
        }

        return [__d('scheduler', 'The dispatcher last ran at {0}.', (string)DateTime::createFromTimestamp($beat))];
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
