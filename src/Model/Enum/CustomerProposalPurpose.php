<?php
declare(strict_types=1);

namespace App\Model\Enum;

use App\Model\Enum\Trait\EnumOptionsTrait;
use Cake\Database\Type\EnumLabelInterface;
use Override;

/**
 * What a paper that concerns the customer rather than any one contract is being drawn up for.
 *
 * The counterpart of {@see \App\Model\Enum\ProposalPurpose}, and it answers the same question in
 * the same place: why the papers are going out. Which document that turns into is settled when one
 * is printed, and the store keeps that on the file - so a consent asked of somebody who has never
 * given one and a consent asked again after what we hold about them changed are one purpose and
 * two papers.
 *
 * One case today. The others - a summary of what is provided, a final settlement - arrive with the
 * documents that print them rather than ahead of them.
 */
enum CustomerProposalPurpose: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case GdprConsent = 'gdpr-consent';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::GdprConsent => __('Consent to the processing of personal data'),
        };
    }
}
