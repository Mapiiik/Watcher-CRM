<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * IpNetworkTypeOfUse Enum
 */
enum IpNetworkTypeOfUse: int implements EnumLabelInterface
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
            self::CustomerRADIUS => __('Customer network set via RADIUS'),
            self::CustomerManually => __('Customer network set manually'),
            self::TechnologyManually => __('Technology network set manually'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
