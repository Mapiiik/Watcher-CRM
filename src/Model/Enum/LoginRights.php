<?php
declare(strict_types=1);

namespace App\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * LoginRights Enum
 */
enum LoginRights: int implements EnumLabelInterface
{
    case Guest = 0;
    case User = 1;
    case Technician = 2;
    case Administrator = 3;
    case Specialist = 4;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::Guest => __('Guest'),
            self::User => __('User'),
            self::Technician => __('Technician'),
            self::Administrator => __('Administrator'),
            self::Specialist => __('Specialist'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
