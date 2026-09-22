<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * What the last stretch to the customer runs over.
 */
enum AccessMedium: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case Wireless = 'wireless';
    case Fibre = 'fibre';
    case Coaxial = 'coaxial';
    case Copper = 'copper';
    case Satellite = 'satellite';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Wireless => __('Wireless'),
            self::Fibre => __('Fibre'),
            self::Coaxial => __('Coaxial Cable'),
            self::Copper => __('Telephone Copper'),
            self::Satellite => __('Satellite'),
        };
    }
}
