<?php
declare(strict_types=1);

namespace Scheduler\Event;

use AuditStash\Persister\TablePersister;
use Cake\Event\EventInterface;
use Cake\Event\EventListenerInterface;
use Override;
use QueueScheduler\Model\Table\SchedulerRowsTable;

/**
 * Gives the table of the schedule what the tables of the application have.
 */
class SchedulerRowsListener implements EventListenerInterface
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function implementedEvents(): array
    {
        return ['Model.initialize' => 'initializeTable'];
    }

    /**
     * Who changed the schedule belongs in the audit log, and a row switched on waits for its time.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event.
     * @return void
     */
    public function initializeTable(EventInterface $event): void
    {
        $table = $event->getSubject();

        if (!$table instanceof SchedulerRowsTable) {
            return;
        }

        // after the table's own beforeSave, which is what leaves the old time in place
        $table->addBehavior('Scheduler.RecalculateNextRun', ['priority' => 20]);

        $table->addBehavior('AuditStash.AuditLog');
        /** @var \AuditStash\Model\Behavior\AuditLogBehavior $auditLog */
        $auditLog = $table->getBehavior('AuditLog');
        /** @var \AuditStash\Persister\TablePersister $persister */
        $persister = $auditLog->persister();
        $persister->setConfig([
            'serializeFields' => false,
            'primaryKeyExtractionStrategy' => TablePersister::STRATEGY_RAW,
        ]);
    }
}
