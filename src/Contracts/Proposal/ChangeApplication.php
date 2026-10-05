<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Audit\AuditTrail;
use App\Model\Entity\Billing;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\ContractVersion;
use App\Model\Table\BillingsTable;
use App\Model\Table\ContractProposalsTable;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;

/**
 * Applies what a proposal asks for to the live records.
 *
 * This is the only place the proposal touches anything outside itself, and it happens once, when
 * somebody who has seen the preview presses the button. Everything before it - drawing the proposal
 * up, printing from it, sending it, having it signed - leaves the records exactly as they were,
 * which is what lets a proposal that is never signed be given up on with one click.
 *
 * All of it goes in one transaction under one audit transaction id, so that the pair of writes
 * behind every changed billing, the version, the contract and the proposal itself are one act in
 * the log rather than several.
 */
final class ChangeApplication
{
    use LocatorAwareTrait;

    /**
     * Applies the changes of the proposal.
     *
     * A proposal that asks for nothing is applied too: it goes through no steps and is marked
     * as done. Without that, the ordinary proposal behind a new contract's papers - which changes
     * nothing, because the billings were drawn up before them - would sit in the checks for ever
     * as signed and not applied.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param string|null $by Who is applying it.
     * @param bool $reach_into_closed_periods Whether invoiced periods may be written into.
     * @param bool $go_below_minimum Whether any line may price the connection below the minimum.
     * @param bool $leave_out_what_is_gone Whether a line whose billing is no longer on the
     *   contract may be passed over and written down rather than stopping the whole thing.
     * @return void
     * @throws \RuntimeException When the proposal is in no state to be applied.
     */
    public function apply(
        ContractProposal $proposal,
        ?string $by = null,
        bool $reach_into_closed_periods = false,
        bool $go_below_minimum = false,
        bool $leave_out_what_is_gone = false,
        ?AuditTrail $trail = null,
    ): void {
        if (!$proposal->hasBeenConcluded()) {
            throw new RuntimeException('A proposal is not carried over before it has been concluded.');
        }

        if (!$proposal->isOpen()) {
            throw new RuntimeException('This proposal has already been settled.');
        }

        $proposals = $this->proposals();

        // A proposal applies its parts under one trail and writes it out itself; a proposal applied
        // on its own is its own act.
        $alone = !$trail instanceof AuditTrail;
        $trail ??= new AuditTrail();

        $proposals->getConnection()->transactional(
            function () use (
                $proposal,
                $by,
                $reach_into_closed_periods,
                $go_below_minimum,
                $leave_out_what_is_gone,
                $proposals,
                $trail,
            ): void {
                $options = [
                    BillingsTable::ALLOW_CLOSED_PERIODS => $reach_into_closed_periods,
                ] + $trail->options();

                // Worked out once and then applied, so that what the preview showed and what is
                // written here are the same list rather than the same rules run twice.
                $planned = (new ChangePlan())->of($proposal);

                $this->applyTheBillings($proposal, $options, $go_below_minimum, $leave_out_what_is_gone);

                // A contract that keeps no versions never gets one, not even from a new contract.
                if ($proposal->keepsVersions()) {
                    $this->applyTheVersions($proposal, $planned, $options);
                }

                $this->applyToTheContract($proposal, $planned, $options);

                $proposal->applied = DateTime::now();
                $proposal->applied_by = $by;
                $proposals->saveOrFail($proposal, ['checkRules' => true] + $options);
            },
        );

        if ($alone) {
            $trail->flush($proposals, $proposal);
        }
    }

    /**
     * Ends what the proposal replaces and starts what replaces it.
     *
     * The billings are read live rather than from the snapshot: the snapshot says what was, and
     * what is being written has to be written onto what is.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param array<string, mixed> $options What to save with.
     * @param bool $go_below_minimum Whether any line may price the connection below the minimum.
     * @param bool $leave_out_what_is_gone Whether a line whose billing has gone is passed over.
     * @return void
     */
    private function applyTheBillings(
        ContractProposal $proposal,
        array $options,
        bool $go_below_minimum,
        bool $leave_out_what_is_gone,
    ): void {
        $changes = $proposal->proposedChanges();

        if ($changes->billings === []) {
            return;
        }

        $billings = $this->fetchTable('Billings');
        $contract = $this->fetchTable('Contracts')->get($proposal->contract_id);

        foreach ($changes->billings as $line) {
            $to_save = [];
            // an administrator who allowed it on the line has already made the decision
            $allowed = [BillingsTable::ALLOW_BELOW_MINIMUM => $go_below_minimum || $line->below_minimum_allowed];

            if (!$line->isAddition()) {
                $ending = $billings->find()
                    ->where(['Billings.id' => $line->billing_id])
                    ->first();

                // Somebody took the billing off the contract while the papers were out. Passed
                // over and written down where that was allowed, because the alternative is to
                // give up on a proposal the customer has signed over a line nobody can write.
                if (!$ending instanceof Billing) {
                    if (!$leave_out_what_is_gone) {
                        throw new RuntimeException(sprintf(
                            'The billing %s this proposal changes is no longer on the contract.',
                            (string)$line->billing_id,
                        ));
                    }

                    $this->leaveTheLineOut($proposal, $line);

                    continue;
                }

                if ($line->neverRunsAfterAll($proposal->effective_from, $ending->billing_from)) {
                    $this->dropTheBilling($ending, $allowed + $options);
                } else {
                    $ends = $line->endsTheBillingOn($proposal->effective_from, $ending->billing_until);

                    if ($ends !== null) {
                        $to_save[] = $billings->patchEntity($ending, [
                            'billing_until' => $ends->toDateString(),
                        ]);
                    }
                }
            }

            if ($line->startsABilling()) {
                $to_save[] = $this->startingBilling($line, $proposal, (string)$contract->customer_id);
            }

            if ($to_save !== [] && $billings->saveMany($to_save, $allowed + $options) === false) {
                throw new RuntimeException($this->whatWentWrong($to_save));
            }
        }
    }

    /**
     * Writes down a line applying the changes could not carry out.
     *
     * Onto the proposal rather than into its changes: the changes are what the papers say and what
     * was signed, and this is what became of them. A line written down here is somebody's to see
     * to by hand, which is what the check over the contract is for.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param \App\Contracts\Proposal\ProposedBilling $line The line that could not be written.
     * @return void
     */
    private function leaveTheLineOut(ContractProposal $proposal, ProposedBilling $line): void
    {
        $proposal->set('left_out', $proposal->whatWasLeftOut() + [
            $line->id => sprintf(
                'The billing %s is no longer on the contract.',
                (string)$line->billing_id,
            ),
        ]);
    }

    /**
     * Takes away a billing the papers end before it ever began.
     *
     * Refused where somebody has been invoiced for it, which only happens where the papers are
     * dated back behind an invoice that has gone out. Nothing here can put that right - it wants
     * a credit note and somebody deciding - so the whole application stops and says so.
     *
     * @param \App\Model\Entity\Billing $billing The billing that never runs.
     * @param array<string, mixed> $options What to delete with.
     * @return void
     * @throws \RuntimeException When the records will not let it go.
     */
    private function dropTheBilling(Billing $billing, array $options): void
    {
        if ($this->fetchTable('Billings')->delete($billing, $options)) {
            return;
        }

        throw new RuntimeException($this->whatWentWrong([$billing]));
    }

    /**
     * The billing a line puts in place.
     *
     * @param \App\Contracts\Proposal\ProposedBilling $line What the proposal asks for.
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param string $customer_id Who it belongs to.
     * @return \App\Model\Entity\Billing
     */
    private function startingBilling(
        ProposedBilling $line,
        ContractProposal $proposal,
        string $customer_id,
    ): Billing {
        /** @var \App\Model\Entity\Billing $starting */
        $starting = $this->fetchTable('Billings')->newEntity([
            'customer_id' => $customer_id,
            'contract_id' => $proposal->contract_id,
            'billing_from' => $line->startsOn($proposal->effective_from)->toDateString(),
            'billing_until' => $line->billing_until?->toDateString(),
            'service_id' => $line->service_id,
            'text' => $line->text,
            'quantity' => $line->quantity,
            // Money is held as an object and the marshaller takes only what a form would send.
            'price' => $line->price?->toString(),
            'fixed_discount' => $line->fixed_discount?->toString(),
            'percentage_discount' => $line->percentage_discount,
            'separate_invoice' => $line->separate_invoice,
            'note' => $line->note,
        ]);

        return $starting;
    }

    /**
     * Writes what the plan says onto the version the proposal belongs to, and onto the one it
     * replaces.
     *
     * Which fields those are, and why some of them are written without anybody having asked, is
     * {@see \App\Contracts\Proposal\ChangePlan}'s to say. Here they are only applied.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param list<\App\Contracts\Proposal\PlannedChange> $planned What is to be written.
     * @param array<string, mixed> $options What to save with.
     * @return void
     */
    private function applyTheVersions(ContractProposal $proposal, array $planned, array $options): void
    {
        // Papers for a new contract may be drawn up before the version they are about exists. The
        // version is the record of a paper's life, so it starts when the paper does rather than
        // before anybody has signed it - and what it becomes is the same projection the papers
        // themselves were printed from. What is planned for it is already in there, so there is
        // nothing further to write onto it.
        if ($proposal->contract_version_id === null) {
            $version = $this->versionFromThePapers($proposal);
            $this->fetchTable('ContractVersions')->saveOrFail($version, $options);

            $proposal->set('contract_version_id', $version->id);
        } else {
            $this->writeOntoVersion(
                $proposal->contract_version_id,
                ChangePlan::VERSION,
                $planned,
                $options,
            );
        }

        // Ending the version the papers take over from belongs to the same act, whether or not they
        // started a version of their own. New papers beside a version that never got its last day
        // are two services where the customer agreed to one.
        $this->writeOntoVersion(
            $proposal->terminates_contract_version_id,
            ChangePlan::REPLACED_VERSION,
            $planned,
            $options,
        );
    }

    /**
     * Writes what the plan has for one version onto it.
     *
     * @param string|null $id The version, where the proposal names one.
     * @param string $target Which of the plan's subjects it is.
     * @param list<\App\Contracts\Proposal\PlannedChange> $planned What is to be written.
     * @param array<string, mixed> $options What to save with.
     * @return void
     */
    private function writeOntoVersion(?string $id, string $target, array $planned, array $options): void
    {
        $writes = array_filter($planned, fn(PlannedChange $one): bool => $one->target === $target);

        // Nothing to write is the ordinary case - a proposal usually asks about the billings alone -
        // and a save with nothing in it would only put the version's rules in the way.
        if ($writes === [] || $id === null) {
            return;
        }

        $versions = $this->fetchTable('ContractVersions');
        $version = $versions->get($id);

        foreach ($writes as $write) {
            $version->set($write->field, $write->to);
        }

        if ($version->isDirty()) {
            $versions->saveOrFail($version, $options);
        }
    }

    /**
     * The version the papers brought into being, as they said it would be.
     *
     * Drawn from the snapshot and the proposed changes rather than from anything live, so that the
     * record ends up saying what the printed paper says. The day it starts is the day the papers
     * take effect, which for a new contract is the same thing said twice.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return \App\Model\Entity\ContractVersion An unsaved record.
     */
    private function versionFromThePapers(ContractProposal $proposal): ContractVersion
    {
        $snapshot = $proposal->stateOfThings();
        $projected = (new ProposalProjection())->projectVersion(
            $snapshot->hydrateVersion(),
            $proposal->proposedChanges()->version,
        );

        /** @var \App\Model\Entity\ContractVersion $version */
        $version = $this->fetchTable('ContractVersions')->newEmptyEntity();

        $version->set('contract_id', $proposal->contract_id);
        $version->set('valid_from', $proposal->effective_from);
        $version->set('valid_until', $projected->valid_until);
        $version->set('obligation_until', $projected->obligation_until);
        $version->set('conclusion_date', $proposal->conclusion_date);

        return $version;
    }

    /**
     * Writes what the plan says onto the contract.
     *
     * The state of the contract is deliberately left alone: it has its own set of requirements to
     * satisfy and switching it blind would only make applying the changes fail in ways nobody asked about.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @param list<\App\Contracts\Proposal\PlannedChange> $planned What is to be written.
     * @param array<string, mixed> $options What to save with.
     * @return void
     */
    private function applyToTheContract(ContractProposal $proposal, array $planned, array $options): void
    {
        $writes = array_filter(
            $planned,
            fn(PlannedChange $one): bool => $one->target === ChangePlan::CONTRACT,
        );

        if ($writes === []) {
            return;
        }

        $contracts = $this->fetchTable('Contracts');
        $contract = $contracts->get($proposal->contract_id);

        foreach ($writes as $write) {
            $contract->set($write->field, $write->to);
        }

        $contracts->saveOrFail($contract, $options);
    }

    /**
     * What to say when the records would not take the change.
     *
     * @param array<\Cake\Datasource\EntityInterface> $entities What was being saved.
     * @return string
     */
    private function whatWentWrong(array $entities): string
    {
        $said = [];

        foreach ($entities as $entity) {
            foreach ($entity->getErrors() as $field => $errors) {
                foreach ((array)$errors as $error) {
                    $said[] = $field . ': ' . (is_array($error) ? implode(' ', $error) : $error);
                }
            }
        }

        return $said === []
            ? 'The billing could not be saved.'
            : implode(' ', $said);
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
