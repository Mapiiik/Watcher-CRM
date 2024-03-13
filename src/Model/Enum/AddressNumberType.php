<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * AddressType Enum
 */
enum AddressNumberType: int implements EnumLabelInterface
{
    case House = 0;
    case Registration = 1;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::House => __('House Number'),
            self::Registration => __('Registration Number'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
