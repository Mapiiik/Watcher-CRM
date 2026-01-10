<?php
declare(strict_types=1);

namespace Bookkeeping\Model\Enum;

use Bookkeeping\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * InvoiceSyncMode Enum
 */
enum InvoiceSyncMode: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case DELTA = 'delta';
    case FULL = 'full';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::DELTA => __d('bookkeeping', 'Delta Synchronization'),
            self::FULL => __d('bookkeeping', 'Full Synchronization'),
        };
    }
}
