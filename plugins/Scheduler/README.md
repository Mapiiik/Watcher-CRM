# Scheduler

The jobs of a Watcher application: what runs when, from inside the application rather than from a
crontab on the host. Built on `dereuromark/cakephp-queue-scheduler`, which keeps the schedule in the
database, and `dereuromark/cakephp-queue`, which runs what is due.

A row of the schedule says what runs when. `bin/cake scheduler run` puts what is due in the queue,
`bin/cake queue run` runs it. In a container the service `scheduler` starts both, every minute, from
`docker/scheduler.crontab`.

## The schedule

The application describes its jobs in `config/scheduler.php`. `bin/cake scheduler sync`, run with
every start, adds the rows that are missing and changes none that exist, so whatever an installation
has set stays. A new job is therefore a new entry and a release. An entry with `'enabled' => true`
starts running by itself on every installation, without one it waits for somebody to switch it on.

The pages are `/admin/queue-scheduler` for the schedule and `/admin/queue` for the runs, their
duration and output. Administrators only.

    bin/cake scheduler enable "Process emails" "Process SMS"
    bin/cake scheduler disable "Process SMS"
    bin/cake scheduler import rows.json
    bin/cake scheduler check

`import` writes rows described as in `config/scheduler.php`, in JSON, and never switches one on.

The defaults are a starting point, all of them off. What an installation runs differently, or on top
of them, goes into such a file: a name the schedule has changes that row, a new name adds one. A row
that is on is left alone, switch it off first. A CRM that issues its invoices in the accounting
system, reads them in full every night and only warns its debtors could import:

    {
        "Issue invoices": {
            "command": "Bookkeeping\\Command\\IssueInvoicesCommand",
            "args": ["--accounting-profile-id", "<id of the accounting profile>", "--schedule", "prev-month-on-first"],
            "frequency": "0 6 * * *",
            "window": {"from": "06:00", "to": "18:00"}
        },
        "Load invoices in full": {
            "command": "Bookkeeping\\Command\\LoadInvoicesCommand",
            "args": ["--mode", "full"],
            "frequency": "15 23 * * *"
        },
        "Process debtors": {
            "command": "Bookkeeping\\Command\\ProcessDebtorsCommand",
            "args": ["--only_notify"],
            "frequency": "0 10 * * 1-5",
            "window": {"from": "10:00", "to": "18:00"}
        }
    }

## What to know about a row

- **It runs at most once.** A failed job is not run again until its next time. Most jobs here write
  to somebody, and doing that twice is worse than doing it a day late.
- **What was missed runs late.** A row due while the scheduler was down runs as soon as it is back.
  A job that must not run at any hour gets a `window`, opening at the time of the row: a job that
  caught up in the morning would otherwise run again at its time, the same day.
- **A row switched on waits for its next time**, even after a long while off.
- **Jobs run one after another.** Each worker line of the crontab is one job at a time.

## Stopping

Stopping the service waits for what is running and starts nothing new. Before a new version goes in,
stop it first, so that no job runs while the migrations do:

    docker compose stop scheduler
    docker compose up -d

A worker that is killed leaves its job looking unfinished. The row waits for it until the job times
out, or until `bin/cake queue job reset <id>` hands it back.

## Failures

The first failure of a row is mailed to `ERROR_EMAILS`, and the next after it has run well again.
`scheduler check`, also from the crontab, reports what no failure ever announces: a worker that was
killed, a job that never ends, a row that does not run, a dispatcher gone quiet.

## Tasks of the queue

The worker runs anything in the queue, not only what the schedule puts there. Something that should
not hold up a page becomes a task in `src/Queue/Task/` and goes in with
`fetchTable('Queue.QueuedJobs')->createJob()`, in the same transaction as what it belongs to.

A task says for itself what the commands share through `config/queue.php`, as properties of its class:

- `$retries`, none by default. Only for a task that does no harm when repeated.
- `$timeout`, after which the queue gives it to another worker. It must be longer than the task takes
  and cannot exceed `defaultRequeueTimeout`.

A task starts within a minute, or after the job in front of it. When that is too long behind a long
synchronisation, give the long rows a `group` and add a worker line that serves the others.
