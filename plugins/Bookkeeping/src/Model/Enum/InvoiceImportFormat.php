<?php
declare(strict_types=1);

namespace Bookkeeping\Model\Enum;

use Bookkeeping\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * InvoiceImportFormat Enum
 */
enum InvoiceImportFormat: string implements EnumLabelInterface
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
            self::XML => __('XML Format'),
            self::DBF => __('dBase Format'),
        };
    }
}
