<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Table;

use Cake\Event\EventInterface;
use ArrayObject;

/**
 * StringModifications behavior
 */
class StringModificationsBehavior extends Behavior
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'trim' => true,
        'emptyAsNull' => true,
        'replaceBadCharacters' => true,
    ];
    
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // trim
                if ($this->_config['trim']) {
                    $data[$key] = trim($value);
                }
                // empty as null
                if ($this->_config['emptyAsNull']) {
                    if ($value === '') $data[$key] = null;
                }
                // replace bad chars
                if ($this->_config['replaceBadCharacters']) {
                    $value = mb_ereg_replace('–', '-', $value);
                    $data[$key] = $value;
                }
            }
        }
    }    
}
