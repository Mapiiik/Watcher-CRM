<?php
declare(strict_types=1);

namespace Bookkeeping\Model\Enum;

use Bookkeeping\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * InvoiceExportFormat Enum
 */
enum InvoiceExportFormat: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case XML = 'xml';
    case DBF = 'dbf';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::XML => __d('bookkeeping', 'XML Format'),
            self::DBF => __d('bookkeeping', 'dBase Format'),
        };
    }
}
