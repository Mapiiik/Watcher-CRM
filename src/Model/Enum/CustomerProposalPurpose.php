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
 * A consent is asked for and comes back signed. A list of what is provided is only handed over:
 * nothing is asked of the customer, so nothing comes back. Others - a final settlement - arrive
 * with the documents that print them rather than ahead of them.
 */
enum CustomerProposalPurpose: string implements EnumLabelInterface
{
    use EnumOptionsTrait;

    case GdprConsent = 'gdpr-consent';
    case ServicesOverview = 'services-overview';

    /**
     * @return string
     */
    #[Override]
    public function label(): string
    {
        return match ($this) {
            self::GdprConsent => __('Consent to the processing of personal data'),
            self::ServicesOverview => __("List of the customer's contracts and services provided"),
        };
    }

    /**
     * How far papers drawn up for this purpose travel before nobody waits for them any more.
     *
     * A consent is asked for and comes back agreed to, and nothing stands behind it waiting to be
     * written. A paper that is only handed over would say so here and would stop being offered a
     * signature everywhere at once.
     *
     * @return \App\Model\Enum\ProposalStep
     */
    public function lastStep(): ProposalStep
    {
        return match ($this) {
            self::GdprConsent => ProposalStep::Signed,
            self::ServicesOverview => ProposalStep::Delivered,
        };
    }

    /**
     * Whether a round for this purpose waits for the customer's signature.
     *
     * One that is only handed over does not, so no signed copy of it is ever missing, and no
     * contract proposal can go out with it - those come back signed and could not be concluded.
     *
     * @return bool
     */
    public function comesBackSigned(): bool
    {
        return $this->lastStep()->goesThrough(ProposalStep::Signed);
    }

    /**
     * The purposes a round holding contract proposals may have.
     *
     * @return array<string, string>
     */
    public static function forContractProposals(): array
    {
        return array_filter(
            self::options(),
            static fn(string $value): bool => self::from($value)->comesBackSigned(),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * The purposes whose rounds come back signed, as they are stored.
     *
     * @return list<string>
     */
    public static function signed(): array
    {
        return array_keys(self::forContractProposals());
    }

    /**
     * The documents a round for this purpose may be printed as.
     *
     * @return array<\App\Model\Enum\CustomerDocumentType>
     */
    public function documents(): array
    {
        return match ($this) {
            self::GdprConsent => [CustomerDocumentType::GdprNew, CustomerDocumentType::GdprChange],
            self::ServicesOverview => [CustomerDocumentType::ServicesOverview],
        };
    }

    /**
     * Which of them to put in front of the operator first.
     *
     * Asking somebody who has agreed before is not the same paper as asking somebody who never
     * has, and only the earlier rounds know which it is. Only a suggestion - what is printed is
     * whatever the operator picks.
     *
     * @param bool $asked_before Whether the customer has agreed to this before.
     * @return \App\Model\Enum\CustomerDocumentType
     */
    public function suggests(bool $asked_before): CustomerDocumentType
    {
        return match ($this) {
            self::GdprConsent => $asked_before
                ? CustomerDocumentType::GdprChange
                : CustomerDocumentType::GdprNew,
            self::ServicesOverview => CustomerDocumentType::ServicesOverview,
        };
    }
}
