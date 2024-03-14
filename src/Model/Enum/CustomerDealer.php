<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * CustomerDealer Enum
 */
enum CustomerDealer: int implements EnumLabelInterface
{
    case Never = 0;
    case Current = 1;
    case Former = 2;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Never => __('No'),
            self::Current => __('Yes') . ' (' . __('current') . ')',
            self::Former => __('Yes') . ' (' . __('former') . ')',
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
