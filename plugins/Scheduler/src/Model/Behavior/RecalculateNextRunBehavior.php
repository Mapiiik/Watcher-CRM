<?php
declare(strict_types=1);

namespace Scheduler\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use QueueScheduler\Model\Entity\SchedulerRow;

/**
 * Makes a row that is switched on wait for its next time.
 *
 * The row keeps the time it was last due, and anything due in the past runs at once. A row left
 * off for a day would otherwise run the moment it is switched back on, and a job that writes to
 * the customers would write to them a second time that day.
 */
class RecalculateNextRunBehavior extends Behavior
{
    /**
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event The event.
     * @param \Cake\Datasource\EntityInterface $entity The row.
     * @param \ArrayObject<string, mixed> $options The options of the save.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (!$entity instanceof SchedulerRow || $entity->isNew()) {
            return;
        }

        if ($entity->enabled && !$entity->getOriginal('enabled')) {
            $entity->next_run = $entity->calculateNextRun();
        }
    }
}
