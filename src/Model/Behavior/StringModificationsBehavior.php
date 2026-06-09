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
     * @param \Cake\Event\EventInterface<\Cake\ORM\Table> $event Event
     * @param \ArrayObject<string, mixed> $data Data
     * @param \ArrayObject<string, mixed> $options Options
     * @psalm-suppress PossiblyUnusedParam
     * @return void
     */
    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options): void
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // replace bad chars
                if ($this->_config['replaceBadCharacters']) {
                    $replaced = mb_ereg_replace('–', '-', $value);

                    if (is_string($replaced)) {
                        $value = $replaced;
                    }
                }
                // trim
                if ($this->_config['trim']) {
                    $value = trim($value);
                }
                // empty as null
                if ($this->_config['emptyAsNull'] && $value === '') {
                    $value = null;
                }
                // update value in data
                $data[$key] = $value;
            }
        }
    }
}
