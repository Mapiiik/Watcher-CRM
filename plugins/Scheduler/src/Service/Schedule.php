<?php
declare(strict_types=1);

namespace Scheduler\Service;

use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use QueueScheduler\Model\Entity\SchedulerRow;
use QueueScheduler\Model\Table\SchedulerRowsTable;

/**
 * The rows of the schedule, as the application and the installation describe them.
 *
 * A row is a description of a job under its name: `command`, `frequency`, and optionally `args`,
 * `priority`, `window` and `enabled`. Whatever an installation changes afterwards is its own,
 * so the descriptions in the code only ever add a row that is missing.
 *
 * @phpstan-type Description array{
 *     command: class-string,
 *     frequency: string,
 *     args?: list<string>,
 *     priority?: int,
 *     window?: array{from: string, to: string},
 *     enabled?: bool,
 * }
 */
class Schedule
{
    use LocatorAwareTrait;

    /**
     * @var \QueueScheduler\Model\Table\SchedulerRowsTable
     */
    private SchedulerRowsTable $rows;

    /**
     * Constructor.
     */
    public function __construct()
    {
        /** @var \QueueScheduler\Model\Table\SchedulerRowsTable $rows */
        $rows = $this->fetchTable('QueueScheduler.SchedulerRows');
        $this->rows = $rows;
    }

    /**
     * What the application schedules by default.
     *
     * @return array<string, array<string, mixed>>
     * @phpstan-return array<string, Description>
     */
    public static function defaults(): array
    {
        return Configure::read('Scheduler.defaults', []);
    }

    /**
     * Add the rows that are missing.
     *
     * @param array<string, array<string, mixed>> $descriptions The rows, under their names.
     * @return list<string> The names of the rows added.
     * @phpstan-param array<string, Description> $descriptions
     */
    public function addMissing(array $descriptions): array
    {
        $added = [];

        foreach ($descriptions as $name => $description) {
            if ($this->find($name) === null) {
                $this->rows->saveOrFail($this->rows->newEntity($this->rowData($name, $description)));
                $added[] = $name;
            }
        }

        return $added;
    }

    /**
     * Bring the rows to what an installation describes, leaving alone the ones that are running.
     *
     * A row that is switched on is out of reach so that a description written once cannot change
     * what is going on. Nothing here switches a row on, whatever the description says.
     *
     * @param array<string, array<string, mixed>> $descriptions The rows, under their names.
     * @return array<string, list<string>> The names of the rows `added`, `changed` and `skipped`.
     * @phpstan-param array<string, Description> $descriptions
     */
    public function import(array $descriptions): array
    {
        $result = ['added' => [], 'changed' => [], 'skipped' => []];

        foreach ($descriptions as $name => $description) {
            $data = ['enabled' => false] + $this->rowData($name, $description);
            $row = $this->find($name);

            if ($row === null) {
                $this->rows->saveOrFail($this->rows->newEntity($data));
                $result['added'][] = $name;
            } elseif ($row->enabled) {
                $result['skipped'][] = $name;
            } else {
                $this->rows->saveOrFail($this->rows->patchEntity($row, $data));
                $result['changed'][] = $name;
            }
        }

        return $result;
    }

    /**
     * Switch a row on or off.
     *
     * @param string $name The name of the row.
     * @param bool $enabled Whether it is to run.
     * @return \QueueScheduler\Model\Entity\SchedulerRow|null The row, null when there is none of that name.
     */
    public function switchRow(string $name, bool $enabled): ?SchedulerRow
    {
        $row = $this->find($name);

        if ($row !== null) {
            $row->enabled = $enabled;
            $this->rows->saveOrFail($row);
        }

        return $row;
    }

    /**
     * @param string $name The name of the row.
     * @return \QueueScheduler\Model\Entity\SchedulerRow|null
     */
    private function find(string $name): ?SchedulerRow
    {
        /** @var \QueueScheduler\Model\Entity\SchedulerRow|null */
        return $this->rows->find()->where(['name' => $name])->first();
    }

    /**
     * @param string $name The name of the row.
     * @param array<string, mixed> $description What it is to do.
     * @return array<string, mixed>
     * @phpstan-param Description $description
     */
    private function rowData(string $name, array $description): array
    {
        $args = $description['args'] ?? [];
        $priority = $description['priority'] ?? null;

        return [
            'name' => $name,
            'type' => SchedulerRow::TYPE_CAKE_COMMAND,
            'content' => $description['command'],
            'param' => $args === [] ? null : json_encode($args, JSON_THROW_ON_ERROR),
            'frequency' => $description['frequency'],
            'job_config' => $priority === null ? null : json_encode(['priority' => $priority], JSON_THROW_ON_ERROR),
            'window_start_time' => $description['window']['from'] ?? null,
            'window_end_time' => $description['window']['to'] ?? null,
            'allow_concurrent' => false,
            'enabled' => $description['enabled'] ?? false,
        ];
    }
}
