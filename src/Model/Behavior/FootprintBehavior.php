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
    protected array $_defaultConfig = [
        'identity_key' => 'id',
        'column_creator' => 'created_by',
        'column_modifier' => 'modified_by',
    ];

    /**
     * Check if needed fields exist
     *
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event
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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param \ArrayObject<string, mixed> $options Options
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        $request = Router::getRequest();

        if ($request != null) {
            $identity = $request->getAttribute('identity');

            if ($identity != null && $this->fieldsExist($event)) {
                if ($entity->isNew()) {
                    $entity->set($this->_config['column_creator'], $identity[$this->_config['identity_key']]);
                    $entity->set($this->_config['column_modifier'], $identity[$this->_config['identity_key']]);
                } else {
                    $entity->set($this->_config['column_modifier'], $identity[$this->_config['identity_key']]);
                }
            }
        }
    }
}
