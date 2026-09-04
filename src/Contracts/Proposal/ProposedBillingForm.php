<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\Billing;
use Cake\I18n\Date;

/**
 * One line of a proposal, as the operator fills it in and as it is read back.
 *
 * A line is edited on a page of its own, the same way a billing is, so what the form sends is one
 * line rather than a whole table. That is the difference from the old shape, where every billing
 * on the contract travelled in one submission and a field name could collide with an association
 * on the proposal.
 */
final class ProposedBillingForm
{
    /**
     * The terms of a line, in the shape the form fills in and sends back.
     *
     * @param \App\Contracts\Proposal\ProposedBilling|null $line The line being edited, if any.
     * @param \App\Model\Entity\Billing|null $replaced What it replaces, when it replaces something.
     * @return array<string, mixed>
     */
    public function fill(?ProposedBilling $line, ?Billing $replaced = null): array
    {
        if ($line !== null) {
            return $line->toArray();
        }

        // Replacing something starts from what is there, so that the operator changes the one
        // thing they came to change rather than typing the rest in again.
        if ($replaced !== null) {
            return [
                'billing_id' => (string)$replaced->id,
                'service_id' => $replaced->service_id,
                'text' => $replaced->text,
                'quantity' => $replaced->quantity,
                'price' => $replaced->price?->toString(),
                'fixed_discount' => $replaced->fixed_discount?->toString(),
                'percentage_discount' => $replaced->percentage_discount,
                'billing_from' => null,
                'billing_until' => null,
                'separate_invoice' => $replaced->separate_invoice,
                'note' => $replaced->note,
            ];
        }

        return ['quantity' => 1, 'separate_invoice' => false];
    }

    /**
     * What the operator filled in, read as a line.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param \App\Contracts\Proposal\ProposedBilling|null $line The line being edited, if any.
     * @param array<string, mixed>|null $service The chosen service as it stands now.
     * @return \App\Contracts\Proposal\ProposedBilling
     */
    public function read(
        array $data,
        ?ProposedBilling $line = null,
        ?array $service = null,
    ): ProposedBilling {
        $said = [
            'id' => $line?->id,
            'billing_id' => $this->text($data, 'billing_id') ?? $line?->billing_id,
            'terminates_only' => false,
            'service_id' => $this->text($data, 'service_id'),
            'text' => $this->text($data, 'text'),
            'quantity' => (int)($data['quantity'] ?? 1),
            'price' => $this->text($data, 'price'),
            'fixed_discount' => $this->text($data, 'fixed_discount'),
            'percentage_discount' => $this->text($data, 'percentage_discount'),
            'billing_from' => $this->day($data, 'billing_from'),
            'billing_until' => $this->day($data, 'billing_until'),
            'separate_invoice' => (bool)($data['separate_invoice'] ?? false),
            'note' => $this->text($data, 'note'),
            // A line that changes the service brings it with it: the contract's snapshot was taken
            // before the operator chose it and has never heard of it.
            'service' => $service ?? $line?->service,
        ];

        return ProposedBilling::fromArray(array_filter(
            $said,
            fn(mixed $value): bool => $value !== null,
        ) + ['billing_id' => null, 'service' => null]);
    }

    /**
     * A line that ends a billing with nothing taking its place.
     *
     * @param string $billing_id Which billing.
     * @param \Cake\I18n\Date|null $on The day it stops being billed for; the proposal's day if none.
     * @return \App\Contracts\Proposal\ProposedBilling
     */
    public function ending(string $billing_id, ?Date $on = null): ProposedBilling
    {
        return ProposedBilling::fromArray([
            'billing_id' => $billing_id,
            'terminates_only' => true,
            'billing_from' => $on?->toDateString(),
        ]);
    }

    /**
     * One field of the form, with an empty one meaning nothing was said.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param string $field Which field.
     * @return string|null
     */
    private function text(array $data, string $field): ?string
    {
        $value = $data[$field] ?? null;

        return $value === null || $value === '' || is_array($value) ? null : (string)$value;
    }

    /**
     * One date field of the form.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param string $field Which field.
     * @return string|null
     */
    private function day(array $data, string $field): ?string
    {
        return $this->text($data, $field);
    }
}
