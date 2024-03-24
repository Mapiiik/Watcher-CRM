<?php
declare(strict_types=1);

namespace Radius\Model\Enum;

use Cake\Database\Type\EnumLabelInterface;

/**
 * AccountType Enum
 */
enum AccountType: int implements EnumLabelInterface
{
    case PPPoE = 0;

    /**
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PPPoE => __d('radius', 'PPPoE'),
        };

        //return Inflector::humanize(Inflector::underscore($this->name));
    }
}
