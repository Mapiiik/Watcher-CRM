<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\Routing\Router;

/**
 * Footprint behavior
 */
class FootprintBehavior extends Behavior
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected $_defaultConfig = [
        'session_key' => 'Auth.id',
        'column_creator' => 'created_by',
        'column_modifier' => 'modified_by',
    ];

    /**
     * Check if needed fields exist
     *
     * @param \Cake\Event\EventInterface $event Event
     * @return bool
     */
    private function fieldsExist(EventInterface $event): bool
    {
        // Make sure the table actually has proper fields
        $table = $event->getSubject();

        return $table->hasField($this->_config['column_creator'])
            && $table->hasField($this->_config['column_modifier']);
    }

    /**
     * Add creator/modifier to actual Entity
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param \ArrayObject $options Options
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $session = Router::getRequest()->getSession();

        if ($this->fieldsExist($event)) {
            if ($entity->isNew()) {
                $entity->set($this->_config['column_creator'], $session->read($this->_config['session_key']));
                $entity->set($this->_config['column_modifier'], $session->read($this->_config['session_key']));
            } else {
                $entity->set($this->_config['column_modifier'], $session->read($this->_config['session_key']));
            }
        }
    }
}
