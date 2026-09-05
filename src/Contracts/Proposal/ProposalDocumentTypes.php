<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\ContractPrintType;
use App\Model\Enum\ProposalPurpose;

/**
 * Which documents a given proposal may be printed as.
 *
 * Worked out from what the proposal holds rather than kept on it. Were it kept, a document type
 * added later could not be printed from anything already on file - the history would be frozen into
 * whatever the list was on the day. This way a new type gets its rule and works backwards as well.
 *
 * The rules are written as what is nonsense rather than as what was meant. They keep out a
 * replacement that replaces nothing, a termination on papers that are not an ending and an amendment
 * to a contract nobody concluded; everything else stays open, because only the operator knows
 * whether the paper in hand is a fresh contract or a copy of the one already signed.
 *
 * Two of them read the purpose. That is not the same as keeping the list: the purpose is a field on
 * the proposal like any other, and it is the only one that tells an end date meant as a fixed term
 * from an end date meant as an ending.
 */
final class ProposalDocumentTypes
{
    /**
     * The documents this proposal may be printed as.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param bool $has_equipment Whether the contract is one that has equipment at all.
     * @param bool $version_concluded Whether the version the proposal belongs to has been concluded.
     * @return array<\App\Model\Enum\ContractPrintType>
     */
    public function for(
        ContractVersionProposal $proposal,
        bool $has_equipment,
        bool $version_concluded,
    ): array {
        $replaces = $proposal->terminatesAnotherVersion();
        $purpose = $proposal->purpose;
        $ends = $purpose === ProposalPurpose::Termination;

        return array_values(array_filter(
            ContractPrintType::cases(),
            fn(ContractPrintType $type): bool => match ($type) {
                ContractPrintType::ContractNew => $purpose === ProposalPurpose::NewContract
                    && !$replaces,
                ContractPrintType::ContractNewX => $purpose === ProposalPurpose::NewContract
                    && $replaces,
                ContractPrintType::ContractAmendment => $purpose === ProposalPurpose::ServiceChange
                    && $version_concluded,
                ContractPrintType::ContractTermination => $ends,
                // The summary says what is on offer before anybody is bound by it, and an ending
                // offers nothing.
                ContractPrintType::ContractSummary => !$ends,
                // The installation protocol hangs off the version, which every proposal has, but
                // nothing is installed on the way out. The uninstallation one wants a version to
                // end and a number to name, so it has nothing to go on unless the proposal ends
                // something - and that is deliberate, because the contract is what says which
                // equipment the customer has, so swapping a box is a new version rather than a
                // protocol of its own.
                ContractPrintType::HandoverInstallation => $has_equipment && !$ends,
                ContractPrintType::HandoverUninstallation => $has_equipment && ($ends || $replaces),
            },
        ));
    }

    /**
     * Whether the given document may be printed from the given proposal.
     *
     * @param \App\Model\Enum\ContractPrintType $type Which document.
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param bool $has_equipment Whether the contract is one that has equipment at all.
     * @param bool $version_concluded Whether the version the proposal belongs to has been concluded.
     * @return bool
     */
    public function allows(
        ContractPrintType $type,
        ContractVersionProposal $proposal,
        bool $has_equipment,
        bool $version_concluded,
    ): bool {
        return in_array($type, $this->for($proposal, $has_equipment, $version_concluded), true);
    }
}
