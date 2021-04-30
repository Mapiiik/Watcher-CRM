<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;

use Cake\Event\EventInterface;
use Cake\Datasource\EntityInterface;
use ArrayObject;

/**
 * Footprint behavior
 */
class FootprintBehavior extends Behavior
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'session_key' => 'login_id',
        'column_creator' => 'created_by',
        'column_modifier' => 'modified_by',
    ];
    
    private function fieldsExist(EventInterface $event) {
        // Make sure the table actually has proper fields
        $table = $event->getSubject();
        return ($table->hasField($this->_config['column_creator']) && $table->hasField($this->_config['column_modifier']));
    }  

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        if ($this->fieldsExist($event)) {
            if ($entity->isNew()) {
                $entity->set($this->_config['column_creator'], $_SESSION[$this->_config['session_key']]);
                $entity->set($this->_config['column_modifier'], $_SESSION[$this->_config['session_key']]);
            } else {
                $entity->set($this->_config['column_modifier'], $_SESSION[$this->_config['session_key']]);
            }
        }
    } 
}