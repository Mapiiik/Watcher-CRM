<?php
declare(strict_types=1);

namespace Scheduler\Service;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Queue\Model\Table\QueuedJobsTable;
use QueueScheduler\Command\RunCommand;
use Throwable;

/**
 * What no failure of a job announces: a worker that died, a job that never ends, a row that does
 * not run, a dispatcher gone quiet.
 *
 * `scheduler check` mails it, and the dashboard shows it, because the check runs in the scheduler
 * and says nothing once the scheduler itself has stopped.
 */
class SchedulerHealth
{
    use LocatorAwareTrait;

    /**
     * When the dispatcher last finished a pass, or null where nothing says.
     *
     * @return \Cake\I18n\DateTime|null
     */
    public function lastRun(): ?DateTime
    {
        $config = (string)(Configure::read('QueueScheduler.cacheConfig') ?? 'default');

        try {
            $beat = Cache::read(RunCommand::HEARTBEAT_KEY, $config);
        } catch (Throwable) {
            return null;
        }

        // without a zone the timestamp comes back in UTC, an hour or two off the time the
        // installation lives in
        return is_int($beat) ? DateTime::createFromTimestamp($beat, date_default_timezone_get()) : null;
    }

    /**
     * Whether the dispatcher has been silent for longer than it should.
     *
     * Nothing known says nothing: the cache is emptied by every deployment, and an overdue row
     * tells of a dispatcher that has been gone for long.
     *
     * @return bool
     */
    public function isQuiet(): bool
    {
        return $this->isLongAgo($this->lastRun());
    }

    /**
     * @param \Cake\I18n\DateTime|null $lastRun When the dispatcher last finished a pass.
     * @return bool
     */
    private function isLongAgo(?DateTime $lastRun): bool
    {
        return $lastRun !== null
            && time() - $lastRun->getTimestamp() >= (int)Configure::read('Scheduler.silentDispatcherAfter');
    }

    /**
     * Jobs a worker took longer ago than any job should take, and never finished.
     *
     * @return list<\Queue\Model\Entity\QueuedJob>
     */
    public function stuckJobs(): array
    {
        $since = DateTime::now()->subSeconds((int)Configure::read('Scheduler.stuckAfter'));

        /** @var list<\Queue\Model\Entity\QueuedJob> $jobs */
        $jobs = $this->fetchTable('Queue.QueuedJobs')
            ->find()
            ->where([
                'fetched <' => $since,
                'completed IS' => null,
                'OR' => ['status IS' => null, 'status !=' => QueuedJobsTable::STATUS_ABORTED],
            ])
            ->orderBy(['fetched' => 'ASC'])
            ->all()
            ->toList();

        return $jobs;
    }

    /**
     * Rows that are on and were due longer ago than the dispatcher could explain.
     *
     * @return list<\QueueScheduler\Model\Entity\SchedulerRow>
     */
    public function overdueRows(): array
    {
        $since = DateTime::now()->subSeconds((int)Configure::read('Scheduler.overdueAfter'));

        /** @var list<\QueueScheduler\Model\Entity\SchedulerRow> $rows */
        $rows = $this->fetchTable('QueueScheduler.SchedulerRows')
            ->find()
            ->where(['enabled' => true, 'next_run <' => $since])
            ->orderBy(['next_run' => 'ASC'])
            ->all()
            ->toList();

        return $rows;
    }

    /**
     * Everything above in words, one sentence per problem.
     *
     * The same sentence for the same problem every time, which is what lets the check mail each
     * of them once a day.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $problems = [];

        foreach ($this->stuckJobs() as $job) {
            $problems[] = __d(
                'scheduler',
                'Job {0} ({1}) was started at {2} and has not finished.',
                $job->id,
                $job->reference ?? $job->job_task,
                (string)$job->fetched,
            );
        }

        foreach ($this->overdueRows() as $row) {
            $problems[] = __d(
                'scheduler',
                'Row {0} was due at {1} and has not run.',
                $row->name,
                (string)$row->next_run,
            );
        }

        $lastRun = $this->lastRun();

        if ($lastRun !== null && $this->isLongAgo($lastRun)) {
            $problems[] = __d('scheduler', 'The dispatcher last ran at {0}.', (string)$lastRun);
        }

        return $problems;
    }
}
