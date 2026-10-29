<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * Which way a piece of equipment goes with a proposal: lent to the customer, or taken back.
 */
enum EquipmentMove: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case Lend = 'lend';
    case Return = 'return';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        // The words the billing lines of a proposal use, because the two tables stand side by side.
        return match ($this) {
            self::Lend => __('Added by this proposal'),
            self::Return => __('Ends with this proposal'),
        };
    }
}
