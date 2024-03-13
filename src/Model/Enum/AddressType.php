<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * AddressType Enum
 */
enum AddressType: int implements EnumLabelInterface
{
    case Installation = 0;
    case Billing = 1;
    case Delivery = 2;
    case Permanent = 3;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Installation => __('Installation Address'),
            self::Billing => __('Billing Address'),
            self::Delivery => __('Delivery Address'),
            self::Permanent => __('Permanent Address'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
