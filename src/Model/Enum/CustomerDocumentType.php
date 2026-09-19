<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * CustomerDocumentType Enum
 */
enum CustomerDocumentType: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case GdprNew = 'gdpr-new';
    case GdprChange = 'gdpr-change';
    case ServicesOverview = 'services-overview';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::GdprNew => __('Consent to the processing of personal data'),
            self::GdprChange => __('Consent to the processing of personal data (change)'),
            self::ServicesOverview => __("List of the user's contracts and services provided"),
        };
    }
}
