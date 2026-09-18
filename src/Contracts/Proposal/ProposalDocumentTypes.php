<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\ContractProposal;
use App\Model\Enum\ContractDocumentType;
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
     * The papers that are drawn up when somebody wants one rather than as a matter of course.
     *
     * @var array<\App\Model\Enum\ContractDocumentType>
     */
    private const WHEN_SOMEBODY_WANTS_ONE = [
        ContractDocumentType::HandoverInstallation,
        ContractDocumentType::HandoverUninstallation,
    ];

    /**
     * The documents this proposal may be printed as.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param bool $has_equipment Whether the contract is one that has equipment at all.
     * @param bool $version_concluded Whether the version the proposal belongs to has been concluded.
     * @return array<\App\Model\Enum\ContractDocumentType>
     */
    public function for(
        ContractProposal $proposal,
        bool $has_equipment,
        bool $version_concluded,
    ): array {
        $replaces = $proposal->terminatesAnotherVersion();
        $purpose = $proposal->purpose;
        $ends = $purpose === ProposalPurpose::Termination;

        // Where the contract keeps no versions nothing of ours is signed, so only what the other
        // side writes may be filed - a notice the customer sent to the provider, say - and since
        // none of it is ever owed, nothing is missing either.
        $ours = $proposal->keepsVersions();

        return array_values(array_filter(
            $this->byPurpose($proposal, $has_equipment, $version_concluded, $ends, $replaces),
            fn(ContractDocumentType $type): bool => $ours || !$type->canBeGenerated(),
        ));
    }

    /**
     * Every document the proposal may have, before asking whether the contract keeps any of ours.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param bool $has_equipment Whether the contract is one that has equipment at all.
     * @param bool $version_concluded Whether the version the proposal belongs to has been concluded.
     * @param bool $ends Whether the proposal ends the contract.
     * @param bool $replaces Whether it ends an earlier version of the contract.
     * @return array<\App\Model\Enum\ContractDocumentType>
     */
    private function byPurpose(
        ContractProposal $proposal,
        bool $has_equipment,
        bool $version_concluded,
        bool $ends,
        bool $replaces,
    ): array {
        $purpose = $proposal->purpose;

        return array_values(array_filter(
            ContractDocumentType::cases(),
            fn(ContractDocumentType $type): bool => match ($type) {
                ContractDocumentType::ContractNew => $purpose === ProposalPurpose::NewContract
                    && !$replaces,
                ContractDocumentType::ContractNewX => $purpose === ProposalPurpose::NewContract
                    && $replaces,
                ContractDocumentType::ContractAmendment => $purpose === ProposalPurpose::ServiceChange
                    && $version_concluded,
                ContractDocumentType::ContractTermination => $ends,
                // The summary says what is on offer before anybody is bound by it, and an ending
                // offers nothing.
                ContractDocumentType::ContractSummary => !$ends,
                // The installation protocol hangs off the version, which every proposal has, but
                // nothing is installed on the way out. The uninstallation one wants a version to
                // end and a number to name, so it has nothing to go on unless the proposal ends
                // something - and that is deliberate, because the contract is what says which
                // equipment the customer has, so swapping a box is a new version rather than a
                // protocol of its own.
                ContractDocumentType::HandoverInstallation => $has_equipment && !$ends,
                ContractDocumentType::HandoverUninstallation => $has_equipment && ($ends || $replaces),
                // What comes from the other side of an ending: the customer's own letter, or the
                // certificate where there is nobody left to write one. Both belong to an ending
                // and to nothing else, and neither is ever owed - an ending has one of them, and
                // which one is not ours to say beforehand.
                ContractDocumentType::TerminationNotice,
                ContractDocumentType::DeathCertificate => $ends,
            },
        ));
    }

    /**
     * The ones of them that have to exist, as against the ones that may.
     *
     * A handover protocol is drawn up when somebody wants one - equipment changes hands without a
     * version ending, and an installation is not always signed for - so its absence is not a gap.
     * Everything else here is the paper the papers are about.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param bool $has_equipment Whether the contract is one that has equipment at all.
     * @param bool $version_concluded Whether the version the proposal belongs to has been concluded.
     * @return array<\App\Model\Enum\ContractDocumentType>
     */
    public function required(
        ContractProposal $proposal,
        bool $has_equipment,
        bool $version_concluded,
    ): array {
        return array_values(array_filter(
            $this->for($proposal, $has_equipment, $version_concluded),
            fn(ContractDocumentType $type): bool => !in_array($type, self::WHEN_SOMEBODY_WANTS_ONE, true),
        ));
    }

    /**
     * The documents this proposal may be printed as, and whether each of them has to exist.
     *
     * Both answers at once, because a page that lists what is missing wants them together and
     * working them out twice would be two chances to disagree.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<string, bool> The document type, and whether it is required.
     */
    public function expectedOf(ContractProposal $proposal): array
    {
        $snapshot = $proposal->stateOfThings();
        $has_equipment = (bool)($snapshot->part('contract')['service_type']['have_equipments'] ?? false);
        $version_concluded = ($snapshot->part('version')['conclusion_date'] ?? null) !== null;

        $expected = [];

        foreach ($this->for($proposal, $has_equipment, $version_concluded) as $type) {
            if (!$type->canBeGenerated()) {
                // Waiting for a paper somebody else writes is not the same as owing one, and an
                // ending never has both of them - so saying either is missing would be wrong
                // whichever way it went.
                continue;
            }

            $expected[$type->value] = !in_array($type, self::WHEN_SOMEBODY_WANTS_ONE, true);
        }

        return $expected;
    }

    /**
     * Whether the given document may be printed from the given proposal.
     *
     * @param \App\Model\Enum\ContractDocumentType $type Which document.
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param bool $has_equipment Whether the contract is one that has equipment at all.
     * @param bool $version_concluded Whether the version the proposal belongs to has been concluded.
     * @return bool
     */
    public function allows(
        ContractDocumentType $type,
        ContractProposal $proposal,
        bool $has_equipment,
        bool $version_concluded,
    ): bool {
        return $type->canBeGenerated()
            && in_array($type, $this->for($proposal, $has_equipment, $version_concluded), true);
    }

    /**
     * The same documents as a list to choose from, read out of the proposal itself.
     *
     * Two pages offer them - the printing form and the papers on a proposal - and neither has any
     * business knowing where in the snapshot the two answers are kept.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return array<string, string> The document type and how it reads.
     */
    public function options(ContractProposal $proposal): array
    {
        $snapshot = $proposal->stateOfThings();

        $offered = $this->for(
            $proposal,
            (bool)($snapshot->part('contract')['service_type']['have_equipments'] ?? false),
            ($snapshot->part('version')['conclusion_date'] ?? null) !== null,
        );

        $documents = [];
        foreach ($offered as $document) {
            $documents[$document->value] = $document->label();
        }

        return $documents;
    }
}
