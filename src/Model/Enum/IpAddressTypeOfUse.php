<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * IpAddressTypeOfUse Enum
 */
enum IpAddressTypeOfUse: int implements EnumLabelInterface
{
    case CustomerRADIUS = 00;
    case CustomerManually = 10;
    case TechnologyManually = 20;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::CustomerRADIUS => __('Customer address set via RADIUS'),
            self::CustomerManually => __('Customer address set manually'),
            self::TechnologyManually => __('Technology address set manually'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
