<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use App\Model\Table\FulltextSearchCustomersTable;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * FulltextSearchCustomers behavior
 *
 * Keeps the customer's search document up to date with the table it is attached to. Belongs on
 * every table the document is built from - the customer itself and its contracts, addresses,
 * e-mails, phone numbers and IP addresses - and nowhere else.
 *
 * Rebuilding one customer costs about 0.15 ms and happens inside the save's transaction, so a
 * document is never a step behind what it describes. What it cannot see is a write that never
 * came through the ORM; `bin/cake fulltext_search_customers rebuild` is what puts the table right after
 * one of those.
 */
class FulltextSearchCustomersBehavior extends Behavior
{
    use LocatorAwareTrait;

    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'customerField' => 'customer_id',
    ];

    /**
     * Rebuilds the document of the customer whose record has been saved.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity): void
    {
        $this->refreshSearchDocument($entity);
    }

    /**
     * Rebuilds the document of the customer whose record has been deleted.
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function afterDelete(EventInterface $event, EntityInterface $entity): void
    {
        $this->refreshSearchDocument($entity);
    }

    /**
     * Rebuilds the document of the customer the given record belongs to.
     *
     * A record moved from one customer to another has to be taken out of the document it was in
     * as well as put into the one it now belongs to, so both ends of the move are rebuilt.
     *
     * @param \Cake\Datasource\EntityInterface $entity The record that has changed.
     * @return void
     */
    protected function refreshSearchDocument(EntityInterface $entity): void
    {
        $field = (string)$this->getConfig('customerField');

        $customerIds = [];
        foreach ([$entity->get($field), $entity->getOriginal($field)] as $customerId) {
            if (is_string($customerId)) {
                $customerIds[] = $customerId;
            }
        }

        if ($customerIds === []) {
            return;
        }

        $this->fetchTable(FulltextSearchCustomersTable::class)->refresh($customerIds);
    }
}
