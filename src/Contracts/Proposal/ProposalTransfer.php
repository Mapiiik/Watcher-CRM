<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\Billing;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Table\BillingsTable;
use App\Model\Table\ContractVersionProposalsTable;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Text;
use RuntimeException;
use SplObjectStorage;

/**
 * Carries what a proposal asks for into the live records.
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
final class ProposalTransfer
{
    use LocatorAwareTrait;

    /**
     * Carries the proposal over.
     *
     * A proposal that asks for nothing is carried over too: it goes through no steps and is marked
     * as done. Without that, the ordinary proposal behind a new contract's papers - which changes
     * nothing, because the billings were drawn up before them - would sit in the checks for ever
     * as signed and not carried over.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param string|null $by Who is carrying it over.
     * @param bool $reach_into_closed_periods Whether invoiced periods may be written into.
     * @return void
     * @throws \RuntimeException When the proposal is in no state to be carried over.
     */
    public function carryOver(
        ContractVersionProposal $proposal,
        ?string $by = null,
        bool $reach_into_closed_periods = false,
    ): void {
        if (!$proposal->hasBeenConcluded()) {
            throw new RuntimeException('A proposal is not carried over before it has been concluded.');
        }

        if (!$proposal->isOpen()) {
            throw new RuntimeException('This proposal has already been settled.');
        }

        $proposals = $this->proposals();

        $proposals->getConnection()->transactional(
            function () use ($proposal, $by, $reach_into_closed_periods, $proposals): void {
                $options = [
                    BillingsTable::ALLOW_CLOSED_PERIODS => $reach_into_closed_periods,
                    // Without these, audit-stash either logs nothing for a batch or gives every
                    // record a transaction of its own; the carrying over is one act.
                    '_auditQueue' => new SplObjectStorage(),
                    '_auditTransaction' => Text::uuid(),
                ];

                $this->carryBillingsOver($proposal, $options);
                $this->carryVersionsOver($proposal);
                $this->carryContractOver($proposal);

                $proposal->applied = DateTime::now();
                $proposal->applied_by = $by;
                $proposals->saveOrFail($proposal, ['checkRules' => true]);
            },
        );
    }

    /**
     * Ends what the proposal replaces and starts what replaces it.
     *
     * The billings are read live rather than from the snapshot: the snapshot says what was, and
     * what is being written has to be written onto what is.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param array<string, mixed> $options What to save with.
     * @return void
     */
    private function carryBillingsOver(ContractVersionProposal $proposal, array $options): void
    {
        $changes = $proposal->proposedChanges();

        if ($changes->billings === []) {
            return;
        }

        $billings = $this->fetchTable('Billings');
        $contract = $this->fetchTable('Contracts')->get($proposal->contract_id);

        foreach ($changes->billings as $line) {
            $to_save = [];
            $starts = $line->startsOn($proposal->effective_from);

            if (!$line->isAddition()) {
                /** @var \App\Model\Entity\Billing $ending */
                $ending = $billings->get($line->billing_id);
                $to_save[] = $billings->patchEntity($ending, [
                    'billing_until' => $starts->subDays(1)->toDateString(),
                ]);
            }

            if ($line->startsABilling()) {
                $to_save[] = $this->startingBilling($line, $proposal, (string)$contract->customer_id);
            }

            if ($billings->saveMany($to_save, $options) === false) {
                throw new RuntimeException($this->whatWentWrong($to_save));
            }
        }
    }

    /**
     * The billing a line puts in place.
     *
     * @param \App\Contracts\Proposal\ProposedBilling $line What the proposal asks for.
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @param string $customer_id Who it belongs to.
     * @return \App\Model\Entity\Billing
     */
    private function startingBilling(
        ProposedBilling $line,
        ContractVersionProposal $proposal,
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
     * Puts the proposal's dates onto the version it belongs to, and ends the one it replaces.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return void
     */
    private function carryVersionsOver(ContractVersionProposal $proposal): void
    {
        $versions = $this->fetchTable('ContractVersions');
        $asked = $proposal->proposedChanges()->version;

        if (!$asked->isEmpty()) {
            $version = $versions->get($proposal->contract_version_id);

            foreach ($asked->asked() as $field => $value) {
                $version->set($field, $value);
            }

            $versions->saveOrFail($version);
        }

        if ($proposal->terminatesAnotherVersion()) {
            $replaced = $versions->get($proposal->terminates_contract_version_id);
            $replaced->set('valid_until', $proposal->effective_from->subDays(1));
            $versions->saveOrFail($replaced);
        }
    }

    /**
     * Puts the proposal's dates onto the contract.
     *
     * The state of the contract is deliberately left alone: it has its own set of requirements to
     * satisfy and switching it blind would only make the transfer fail in ways nobody asked about.
     *
     * @param \App\Model\Entity\ContractVersionProposal $proposal The proposal.
     * @return void
     */
    private function carryContractOver(ContractVersionProposal $proposal): void
    {
        $asked = $proposal->proposedChanges()->contract;

        if ($asked->isEmpty()) {
            return;
        }

        $contracts = $this->fetchTable('Contracts');
        $contract = $contracts->get($proposal->contract_id);

        foreach ($asked->asked() as $field => $value) {
            $contract->set($field, $value);
        }

        $contracts->saveOrFail($contract);
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
     * @return \App\Model\Table\ContractVersionProposalsTable
     */
    private function proposals(): ContractVersionProposalsTable
    {
        /** @var \App\Model\Table\ContractVersionProposalsTable $proposals */
        $proposals = $this->fetchTable('ContractVersionProposals');

        return $proposals;
    }
}
