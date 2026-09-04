<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\Billing;
use App\Model\Entity\ContractVersion;
use Cake\I18n\Date;

/**
 * How things would stand if a proposal were carried over.
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

            // What is replaced or ended stops the day before, and what takes its place starts on
            // the day itself - the same two halves the transfer will write.
            $projected[] = $this->ending($billing, $effective_from);

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
     * The billing being replaced, stopped the day before the replacement starts.
     *
     * @param \App\Model\Entity\Billing $billing What is being replaced.
     * @param \Cake\I18n\Date $effective_from The day the proposal takes effect.
     * @return \App\Model\Entity\Billing
     */
    private function ending(Billing $billing, Date $effective_from): Billing
    {
        $ending = clone $billing;
        $ending->set('billing_until', $effective_from->subDays(1));

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
            'billing_from' => $effective_from,
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
