<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\Billing;
use App\Model\Entity\ContractVersion;
use Cake\I18n\Date;

/**
 * How things would stand if a proposal were applied.
 *
 * This is what the documents are drawn from: the snapshot as it was taken, with the proposed
 * changes laid over it. Nothing here touches the live records - a paper describes what is being
 * asked for, and whether it happens is settled later, by somebody pressing a button.
 *
 * The order is fixed on the way out. Nothing downstream sorts, so leaving it to whatever order the
 * lines happened to be written in would make two printings of one proposal look different.
 */
final class ProposalProjection
{
    /**
     * The billing lines as the proposal would leave them.
     *
     * @param array<\App\Model\Entity\Billing> $billings The billings as the snapshot took them.
     * @param \App\Contracts\Proposal\ProposalChanges $changes What the proposal asks for.
     * @param \Cake\I18n\Date $effective_from The day the proposal takes effect.
     * @param array<string, \App\Model\Entity\Service> $services The services the lines name, by id.
     * @return array<\App\Model\Entity\Billing> Unsaved records; never put them back on a contract.
     */
    public function projectBillings(
        array $billings,
        ProposalChanges $changes,
        Date $effective_from,
        array $services = [],
    ): array {
        $acted_on = $changes->billingsByBillingId();
        $projected = [];

        foreach ($billings as $billing) {
            $line = $acted_on[(string)$billing->id] ?? null;

            if ($line === null) {
                $projected[] = $billing;

                continue;
            }

            // What is replaced or ended stops the day before what replaces it starts - the same
            // two halves applying the changes will write. A line that starts later than the proposal
            // leaves the old billing running until then.
            $projected[] = $this->ending($billing, $line, $effective_from);

            if ($line->startsABilling()) {
                $projected[] = $this->starting($line, $billing, $effective_from, $services);
            }
        }

        foreach ($changes->billings as $line) {
            if ($line->isAddition()) {
                $projected[] = $this->starting($line, null, $effective_from, $services);
            }
        }

        return $this->inOrder($projected);
    }

    /**
     * The same projection, with each line saying where it comes from.
     *
     * This is what the proposal's own table is drawn from: the operator reads what would be billed
     * for and, on each row, whether it stands as it is, was changed, was added, or stops here.
     *
     * @param array<\App\Model\Entity\Billing> $billings The billings as the snapshot took them.
     * @param \App\Contracts\Proposal\ProposalChanges $changes What the proposal asks for.
     * @param \Cake\I18n\Date $effective_from The day the proposal takes effect.
     * @param array<string, \App\Model\Entity\Service> $services The services the lines name, by id.
     * @return array<array{billing: \App\Model\Entity\Billing, line: \App\Contracts\Proposal\ProposedBilling|null, ending: bool, stopped: bool}>
     */
    public function explain(
        array $billings,
        ProposalChanges $changes,
        Date $effective_from,
        array $services = [],
    ): array {
        $acted_on = $changes->billingsByBillingId();
        $rows = [];

        foreach ($billings as $billing) {
            $line = $acted_on[(string)$billing->id] ?? null;

            // The snapshot holds everything the contract has ever billed for. What ran out before
            // the papers take effect has nothing left to stop, so neither a line nor the button
            // that would draw one has anything to do with it.
            $stopped = $billing->billing_until !== null
                && $billing->billing_until->lessThan($effective_from);

            if ($line === null) {
                $rows[] = ['billing' => $billing, 'line' => null, 'ending' => false, 'stopped' => $stopped];

                continue;
            }

            $rows[] = [
                'billing' => $this->ending($billing, $line, $effective_from),
                'line' => $line->terminatesOnly() ? $line : null,
                // Said only where it is true: applying the changes leaves a billing that stopped of its
                // own accord alone, and a row claiming otherwise would be a promise it breaks.
                'ending' => $line->endsTheBillingOn($effective_from, $billing->billing_until) !== null,
                'stopped' => $stopped,
            ];

            if ($line->startsABilling()) {
                $rows[] = [
                    'billing' => $this->starting($line, $billing, $effective_from, $services),
                    'line' => $line,
                    'ending' => false,
                    'stopped' => false,
                ];
            }
        }

        foreach ($changes->billings as $line) {
            if ($line->isAddition()) {
                $rows[] = [
                    'billing' => $this->starting($line, null, $effective_from, $services),
                    'line' => $line,
                    'ending' => false,
                    'stopped' => false,
                ];
            }
        }

        usort(
            $rows,
            fn(array $one, array $other): int => [(string)$one['billing']->billing_from, (string)$one['billing']->name]
                <=> [(string)$other['billing']->billing_from, (string)$other['billing']->name],
        );

        return $rows;
    }

    /**
     * The version as the proposal would leave it.
     *
     * @param \App\Model\Entity\ContractVersion $version The version as the snapshot took it.
     * @param \App\Contracts\Proposal\ProposedVersion $asked What the proposal asks of it.
     * @return \App\Model\Entity\ContractVersion An unsaved record.
     */
    public function projectVersion(ContractVersion $version, ProposedVersion $asked): ContractVersion
    {
        $projected = clone $version;

        foreach ($asked->asked() as $field => $value) {
            $projected->set($field, $value);
        }

        return $projected;
    }

    /**
     * A version an earlier proposal is replacing, as it would stand once it has.
     *
     * @param \App\Model\Entity\ContractVersion $version The version being replaced.
     * @param \Cake\I18n\Date $effective_from The day the replacement takes effect.
     * @return \App\Model\Entity\ContractVersion An unsaved record.
     */
    public function projectTerminatedVersion(
        ContractVersion $version,
        Date $effective_from,
    ): ContractVersion {
        $projected = clone $version;
        $projected->set('valid_until', $effective_from->subDays(1));

        return $projected;
    }

    /**
     * The billing being replaced, stopped the day before its replacement starts.
     *
     * Handed back untouched where it stopped earlier of its own accord - the line has nothing
     * left to end, and the table has to show what applying the changes would actually do.
     *
     * @param \App\Model\Entity\Billing $billing What is being replaced.
     * @param \App\Contracts\Proposal\ProposedBilling $line The line that ends it.
     * @param \Cake\I18n\Date $effective_from The day the proposal takes effect.
     * @return \App\Model\Entity\Billing
     */
    private function ending(Billing $billing, ProposedBilling $line, Date $effective_from): Billing
    {
        $ends = $line->endsTheBillingOn($effective_from, $billing->billing_until);

        if ($ends === null) {
            return $billing;
        }

        $ending = clone $billing;
        $ending->set('billing_until', $ends);

        return $ending;
    }

    /**
     * The billing a line puts in place, whether it replaces one or adds one.
     *
     * A replacement keeps whatever the line does not speak of - who it belongs to and what it is
     * called - from the billing it replaces; an addition has nothing to keep.
     *
     * @param \App\Contracts\Proposal\ProposedBilling $line What the proposal asks for.
     * @param \App\Model\Entity\Billing|null $replaced What it replaces, where it replaces one.
     * @param \Cake\I18n\Date $effective_from The day the proposal takes effect.
     * @param array<string, \App\Model\Entity\Service> $services The services the lines name, by id.
     * @return \App\Model\Entity\Billing
     */
    private function starting(
        ProposedBilling $line,
        ?Billing $replaced,
        Date $effective_from,
        array $services,
    ): Billing {
        $starting = $replaced === null ? new Billing() : clone $replaced;

        $starting->unset('id');
        $starting->patch([
            'billing_from' => $line->startsOn($effective_from),
            'billing_until' => $line->billing_until,
            'service_id' => $line->service_id,
            'text' => $line->text,
            'quantity' => $line->quantity,
            'price' => $line->price,
            'fixed_discount' => $line->fixed_discount,
            'percentage_discount' => $line->percentage_discount,
            'separate_invoice' => $line->separate_invoice,
            'note' => $line->note,
        ]);

        // The service is what the document names and prices the line by. A line that changes it
        // brings the one it chose - the contract's own snapshot has never heard of it - and a line
        // that leaves it alone keeps what was there.
        if ($replaced === null || $line->service_id !== $replaced->service_id) {
            $chosen = $services[(string)$line->service_id] ?? null;

            if ($chosen === null) {
                $starting->unset('service');
            } else {
                $starting->set('service', $chosen);
            }
        }

        return $starting;
    }

    /**
     * The lines in the order a document reads them.
     *
     * @param array<\App\Model\Entity\Billing> $billings The lines.
     * @return array<\App\Model\Entity\Billing>
     */
    private function inOrder(array $billings): array
    {
        usort(
            $billings,
            fn(Billing $one, Billing $other): int => [(string)$one->billing_from, (string)$one->name]
                <=> [(string)$other->billing_from, (string)$other->name],
        );

        return $billings;
    }
}
