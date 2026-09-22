<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * Who a record of an available connection comes from.
 */
enum AvailableConnectionOrigin: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    /**
     * Written down by the operator, or taken over by them from a contract. The synchronisation
     * leaves it as it is.
     */
    case Manual = 'manual';

    /**
     * Put there by the synchronisation from a contract, which keeps it up to date.
     */
    case Contract = 'contract';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::Manual => __('Entered by hand'),
            self::Contract => __('From a contract'),
        };
    }
}
