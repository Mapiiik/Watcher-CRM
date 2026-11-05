<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\ProposalPurpose;
use App\Model\Table\ContractProposalsTable;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Exception;

/**
 * How papers are drawn up: from what the form said to a proposal with a snapshot, ready to be saved.
 *
 * Anything that keeps a proposal from getting that far is said on the field the operator has to
 * look at, with everything they typed still there, so nothing here throws at them.
 */
final class ProposalDrafting
{
    use LocatorAwareTrait;

    /**
     * What a contract has to be loaded with for a snapshot to be taken of it - the same as printing
     * used to load it, because that is what the documents read.
     *
     * @var array<mixed>
     */
    private const FOR_A_SNAPSHOT = [
        'Billings' => ['Services' => ['ConnectionProfiles']],
        'ContractStates',
        'ContractVersions',
        'Customers' => ['Addresses', 'Emails', 'Phones', 'AccountingProfiles'],
        'InstallationAddresses',
        'IpAddresses',
        'IpNetworks',
        'ServiceTypes',
    ];

    /**
     * Puts what the form said onto the papers being drawn up, and photographs the contract.
     *
     * Only ever while they are being drawn: every field here settles something that is then either
     * photographed or written into the changes, so reading any of it a second time would leave the
     * papers printing one thing and the records doing another. Papers already written are put
     * right on a page of their own, which asks for none of this.
     *
     * Which contract the papers are about comes from the form like everything else: the address
     * only fills that field in, so what was chosen there stands even where it is not the contract
     * the form was opened under.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal being drawn up.
     * @param array<string, mixed> $data What the form sent.
     * @param bool $redrawing Whether the form is only asking to be drawn again, which is not an
     *   attempt to save, so a half-filled one is expected rather than wrong.
     * @return \App\Model\Entity\ContractProposal
     */
    public function fill(ContractProposal $proposal, array $data, bool $redrawing = false): ContractProposal
    {
        $form = new ProposalForm();
        $purpose = $this->purposeFrom($data, $proposal);
        $data['purpose'] = $purpose->value;

        // A contract whose service keeps no versions has none to choose, change or photograph.
        $contract = $this->contractFor((string)($data['contract_id'] ?? $proposal->contract_id));
        $keepsVersions = $contract?->service_type->have_contract_versions ?? true;

        if (!$keepsVersions) {
            $data['contract_version_id'] = null;
            $data['terminates_contract_version_id'] = null;
        }

        // An exchange of borrowed equipments names no version and replaces nothing.
        if (!$purpose->needsAVersion()) {
            $data['contract_version_id'] = null;
            $data['terminates_contract_version_id'] = null;
            $data['terminated_contract_number'] = null;
        }

        // The billings are added a line at a time afterwards and never travel in this submission.
        $data['changes'] = $form->changesFrom($data, $purpose, $keepsVersions);
        $data['confirmations'] = $form->confirmationsFrom($data);
        // Setting the connection up again is asked only of a new contract replacing an earlier one.
        $data['reactivates'] = $purpose === ProposalPurpose::NewContract
            && !empty($data['terminates_contract_version_id'])
            && !empty($data['reactivates']);
        $ends = $this->endOfTheVersion($data);

        // The form's own fields have been read by now, and the marshaller must not see them: it
        // would take a name it shares with an association for a record of its own.
        unset(
            $data['version_change'],
            $data['version_change_named'],
            $data['ends_on'],
            $data['version_only'],
            $data['refresh'],
        );

        $version = $this->versionFor(
            (string)($data['contract_version_id'] ?? $proposal->contract_version_id),
        );

        // A form asking to be drawn again is not an attempt to save, so nothing is held against it
        // - and the day is left exactly as it was typed. Filling it in here is what would make the
        // day of one version stay behind in the field after another was chosen.
        if ($redrawing) {
            return $this->proposals()->patchEntity($proposal, $data, ['validate' => false]);
        }

        // The day the papers take effect is asked for only where they are a change agreed while the
        // version runs, and even there it may be left empty. An ending works it out from the day it
        // ends on, because what is billed for stops the day before the papers apply. Anything else
        // takes effect with its version.
        $said = $data['effective_from'] ?? null;
        $saidNothing = !is_string($said) || trim($said) === '';

        if ($purpose === ProposalPurpose::Termination) {
            if ($ends === null) {
                $proposal = $this->proposals()->patchEntity($proposal, $data, [
                    'validate' => false,
                ]);
                $proposal->setError('ends_on', [__('Enter the last day of the service.')]);

                return $proposal;
            }

            $data['effective_from'] = $ends->addDays(1)->toDateString();
        } elseif (!$purpose->needsAVersion()) {
            // The day it is drawn up. The day the equipment changes hands is written in by hand on
            // the protocol and said again when the changes are applied.
            $data['effective_from'] = Date::today()->toDateString();
        } elseif (!$keepsVersions) {
            // With no version to take the day from, the day has to be said.
            if ($saidNothing) {
                $proposal = $this->proposals()->patchEntity($proposal, $data, [
                    'validate' => false,
                ]);
                $proposal->setError('effective_from', [__('Enter the day the proposal takes'
                    . ' effect.')]);

                return $proposal;
            }
        } elseif ($version !== null && ($saidNothing || !$purpose->asksForItsOwnDay())) {
            $data['effective_from'] = $version->valid_from->toDateString();
        }

        if ($contract !== null && (!$keepsVersions || !$purpose->needsAVersion())) {
            $data['snapshot'] = (new ProposalSnapshotBuilder())->take($contract, null);
            $data['snapshot_taken'] = DateTime::now();
        } else {
            $terminates = $data['terminates_contract_version_id']
                ?? $proposal->terminates_contract_version_id;
            $terminated = $terminates === null ? null : $this->versionFor((string)$terminates);

            // Papers for a new contract may be about a version that is still to come, and carrying
            // them over is what brings it into being. Anything else is about a version that is
            // already there, so one has to be named.
            $mayStartOne = $purpose->mayStartAVersion();

            if ($contract === null || ($version === null && !$mayStartOne)) {
                // Without those there is nothing to take a snapshot of, and the columns that would
                // hold one are not on the form - so it is said where the operator is looking,
                // with everything they typed still in front of them.
                $proposal = $this->proposals()->patchEntity($proposal, $data, [
                    'validate' => false,
                ]);
                $proposal->setError(
                    $contract === null ? 'contract_id' : 'contract_version_id',
                    [__('Choose which contract and which version of it this contract proposal'
                        . ' is for.')],
                );

                return $proposal;
            }

            // A version that is still to come is photographed as it will be, so that the papers
            // print the same either way and the record ends up saying what they said.
            $snapshotted = $version ?? $this->versionToCome($contract->id, $data);

            if ($snapshotted === null) {
                $proposal = $this->proposals()->patchEntity($proposal, $data, [
                    'validate' => false,
                ]);
                $proposal->setError(
                    'effective_from',
                    [__('Enter the day the new contract starts.')],
                );

                return $proposal;
            }

            $data['snapshot'] = (new ProposalSnapshotBuilder())->take($contract, $snapshotted, $terminated);
            $data['snapshot_taken'] = DateTime::now();
        }

        return $this->proposals()->patchEntity($proposal, $data);
    }

    /**
     * What the papers are being drawn up for.
     *
     * The purpose settles what the rest of the submission means, so it is read before any of it.
     * A form that has never named one is drawing up a new contract, which is the common case.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param \App\Model\Entity\ContractProposal $proposal The proposal being filled in.
     * @return \App\Model\Enum\ProposalPurpose
     */
    private function purposeFrom(array $data, ContractProposal $proposal): ProposalPurpose
    {
        $said = $data['purpose'] ?? null;

        if (is_string($said) && ProposalPurpose::tryFrom($said) !== null) {
            return ProposalPurpose::from($said);
        }

        return $proposal->purpose ?? ProposalPurpose::NewContract;
    }

    /**
     * The day an ending says the version stops being valid on, as the form gave it.
     *
     * @param array<string, mixed> $data What the form sent.
     * @return \Cake\I18n\Date|null
     */
    private function endOfTheVersion(array $data): ?Date
    {
        $day = $data['ends_on'] ?? null;

        if (!is_string($day) || trim($day) === '') {
            return null;
        }

        try {
            return new Date(trim($day));
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Photographs the contract again, as it stands now.
     *
     * The same reading the papers were drawn up from, asked of the same records - what the papers
     * are about does not change here, so it is read off the proposal rather than off a form.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The papers.
     * @return bool Whether there was anything left to photograph.
     */
    public function takeANewSnapshot(ContractProposal $proposal): bool
    {
        $contract = $this->contractFor((string)$proposal->contract_id);
        // A contract whose service keeps no versions is photographed without one.
        $keepsVersions = $contract?->service_type->have_contract_versions ?? true;
        $terminates = $keepsVersions ? $proposal->terminates_contract_version_id : null;
        $needsOne = $proposal->purpose->needsAVersion();
        $version = match (true) {
            $contract === null, !$keepsVersions, !$needsOne => null,
            $proposal->contract_version_id !== null => $this->versionFor($proposal->contract_version_id),
            // Papers for a new contract may be about a version that is still to come, and are
            // photographed as it will be, the same way they were when they were drawn up.
            default => $this->versionToCome($contract->id, [
                'effective_from' => $proposal->effective_from?->toDateString(),
                'changes' => $proposal->changes,
            ]),
        };

        if ($contract === null || ($version === null && $keepsVersions && $needsOne)) {
            return false;
        }

        $proposal->set('snapshot', (new ProposalSnapshotBuilder())->take(
            $contract,
            $version,
            $terminates === null ? null : $this->versionFor($terminates),
        ));
        $proposal->set('snapshot_taken', DateTime::now());

        return true;
    }

    /**
     * Brings the lines up to a fresh snapshot.
     *
     * Something moving underneath the proposal is why anybody asks for a fresh snapshot, so a line
     * left pointing at what is gone would only have the saving refused. What the papers have to
     * stop is put there the same way it was when they were drawn up.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal and its new snapshot.
     * @param \App\Contracts\Proposal\ProposalSnapshot $before The snapshot it replaces.
     * @return array{billings: int, returns: int} How many lines of each kind were taken back.
     */
    public function updateLinesForNewSnapshot(ContractProposal $proposal, ProposalSnapshot $before): array
    {
        $asked = $proposal->proposedChanges();
        $snapshot = $proposal->stateOfThings();
        $billingsKept = $asked->withoutBillingsGoneFrom($snapshot);
        $kept = $billingsKept->withoutReturnsGoneFrom($snapshot);

        if ($kept !== $asked) {
            $proposal->set('changes', $kept->toArray());
        }

        (new ProposalTerm())->endWhatIsBilledFor($proposal, $before);

        return [
            'billings' => count($asked->billings) - count($billingsKept->billings),
            'returns' => count($billingsKept->equipments) - count($kept->equipments),
        ];
    }

    /**
     * The contract, loaded with everything a snapshot and the readiness checks read.
     *
     * @param string $id Which contract.
     * @return \App\Model\Entity\Contract|null
     */
    public function contractFor(string $id): ?Contract
    {
        if ($id === '') {
            return null;
        }

        /** @var \App\Model\Entity\Contract|null $contract */
        $contract = $this->proposals()->Contracts
            ->find()
            ->contain(self::FOR_A_SNAPSHOT)
            ->contain('BorrowedEquipments.EquipmentTypes', fn(SelectQuery $q): SelectQuery => $q->where([
                'BorrowedEquipments.borrowed_until IS' => null,
            ]))
            ->contain('SoldEquipments.EquipmentTypes', fn(SelectQuery $q): SelectQuery => $q->where([
                'SoldEquipments.date_of_sale IS' => null,
            ]))
            ->where(['Contracts.id' => $id])
            ->first();

        return $contract;
    }

    /**
     * The version the papers will bring into being, as they say it will be.
     *
     * Unsaved, and only ever photographed: what actually creates it is applying the proposal.
     * It starts on the day the papers take effect, which for a new contract is the same day said
     * twice, so without that day there is nothing to draw.
     *
     * @param string $contract_id Whose version it will be.
     * @param array<string, mixed> $data What the form said.
     * @return \App\Model\Entity\ContractVersion|null
     */
    private function versionToCome(string $contract_id, array $data): ?ContractVersion
    {
        $said = $data['effective_from'] ?? null;

        if (!is_string($said) || trim($said) === '') {
            return null;
        }

        $asked = ProposedVersion::fromArray((array)($data['changes']['version'] ?? []));

        /** @var \App\Model\Entity\ContractVersion $version */
        $version = $this->proposals()->ContractVersions->newEmptyEntity();

        $version->set('contract_id', $contract_id);
        $version->set('valid_from', new Date($said));
        $version->set('valid_until', $asked->names('valid_until') ? $asked->get('valid_until') : null);
        $version->set(
            'obligation_until',
            $asked->names('obligation_until') ? $asked->get('obligation_until') : null,
        );

        return $version;
    }

    /**
     * One contract version.
     *
     * @param string $id Which version.
     * @return \App\Model\Entity\ContractVersion|null
     */
    public function versionFor(string $id): ?ContractVersion
    {
        if ($id === '') {
            return null;
        }

        /** @var \App\Model\Entity\ContractVersion|null $version */
        $version = $this->proposals()->ContractVersions
            ->find()
            ->where(['ContractVersions.id' => $id])
            ->first();

        return $version;
    }

    /**
     * @return \App\Model\Table\ContractProposalsTable
     */
    private function proposals(): ContractProposalsTable
    {
        /** @var \App\Model\Table\ContractProposalsTable $proposals */
        $proposals = $this->fetchTable('ContractProposals');

        return $proposals;
    }
}
