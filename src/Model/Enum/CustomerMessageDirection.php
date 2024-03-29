<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * CustomerMessageDirection Enum
 */
enum CustomerMessageDirection: int implements EnumLabelInterface
{
    case Outgoing = 10;
    case Incoming = 20;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Outgoing => __('Outgoing'),
            self::Incoming => __('Incoming'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
