<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * CustomerMessageBodyFormat Enum
 */
enum CustomerMessageBodyFormat: int implements EnumLabelInterface
{
    case Plaintext = 0;
    case Html = 10;

    /**
     * @return string
     */
    public function label(): string
    {
        switch ($this->value) {
            case $this::Plaintext->value:
                return __('Plain Text');
            case $this::Html->value:
                return __('HTML');
        }

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
