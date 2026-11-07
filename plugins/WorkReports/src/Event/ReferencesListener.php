<?php
declare(strict_types=1);

namespace WorkReports\Event;

use App\Model\Table\ContractsTable;
use App\Model\Table\CustomersTable;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Override;
use Tasks\Model\Table\TasksTable;

/**
 * Keeps what reported work names from being deleted.
 *
 * The foreign keys would refuse it too, but by raising, which reaches the operator as an error
 * page. The tables of the application know nothing of this plugin, so the rules come from here.
 * An account is seen to by the plugin Access, which reads the foreign keys of every table.
 */
class ReferencesListener implements EventListenerInterface
{
    use LocatorAwareTrait;

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function implementedEvents(): array
    {
        return ['Model.buildRules' => 'addRules'];
    }

    /**
     * Asks a table the work points at not to let go of what it names.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event.
     * @param \Cake\ORM\RulesChecker $rules The rules being built.
     * @return void
     */
    public function addRules(EventInterface $event, RulesChecker $rules): void
    {
        $column = $this->columnFor($event->getSubject());
        if ($column === null) {
            return;
        }

        // without a name, as a process bootstrapping twice would have the second one refused
        $rules->addDelete(
            fn(EntityInterface $entity): bool => !$this->fetchTable('WorkReports.WorkReportItems')->exists([
                $column => $entity->get('id'),
            ]),
            [
                'errorField' => 'id',
                'message' => __d('work_reports', 'Reported work names this record, so it cannot be deleted.'),
            ],
        );
    }

    /**
     * The column of the reported work that points at the table, if one does.
     *
     * @param \Cake\ORM\Table $table The table.
     * @return string|null
     */
    private function columnFor(Table $table): ?string
    {
        return match (true) {
            $table instanceof CustomersTable => 'customer_id',
            $table instanceof ContractsTable => 'contract_id',
            $table instanceof TasksTable => 'task_id',
            default => null,
        };
    }
}
