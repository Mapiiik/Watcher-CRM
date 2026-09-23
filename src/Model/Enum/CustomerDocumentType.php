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

    // Whatever else was handed over: a power of attorney, anything the operator was given and has
    // nowhere else to keep. One case rather than a list of them, because the list has no end -
    // what each paper is, its own file name says.
    case Other = 'other';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::GdprNew => __('Consent to the processing of personal data'),
            self::GdprChange => __('Consent to the processing of personal data (change)'),
            self::ServicesOverview => __("List of the customer's contracts and services provided"),
            self::Other => __('Other document'),
        };
    }

    /**
     * Whether this is a paper the application generates.
     *
     * The same question the papers of a contract are asked, and for the same reason: what is only
     * ever filed is never owed, never offered to be drawn and never printed.
     *
     * @return bool
     */
    public function canBeGenerated(): bool
    {
        return $this !== self::Other;
    }
}
