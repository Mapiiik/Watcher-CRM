<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * CustomerPrintType Enum
 */
enum CustomerPrintType: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case GdprNew = 'gdpr-new';
    case GdprChange = 'gdpr-change';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::GdprNew => __('Consent to the processing of personal data'),
            self::GdprChange => __('Consent to the processing of personal data (change)'),
        };
    }
}
