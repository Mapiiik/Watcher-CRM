<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;

/**
 * StringModifications behavior
 */
class StringModificationsBehavior extends Behavior
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'trim' => true,
        'emptyAsNull' => true,
        'replaceBadCharacters' => true,
    ];

    /**
     * String modifications
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \ArrayObject $data Data
     * @param \ArrayObject $options Options
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // replace bad chars
                if ($this->_config['replaceBadCharacters']) {
                    $value = mb_ereg_replace('–', '-', $value);
                    $data[$key] = $value;
                }
                // trim
                if ($this->_config['trim']) {
                    $data[$key] = trim($value);
                }
                // empty as null
                if ($this->_config['emptyAsNull']) {
                    if ($value === '') {
                        $data[$key] = null;
                    }
                }
            }
        }
    }
}
