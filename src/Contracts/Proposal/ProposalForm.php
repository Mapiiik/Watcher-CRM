<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

/**
 * What the operator filled in, read as what the proposal asks for.
 *
 * The form is a line per billing that is already there, each set to be kept, changed or ended, plus
 * however many lines add something new. Only the ones that are not kept say anything, so a proposal
 * that changes nothing comes out empty - which is what a new contract's papers look like, since its
 * billings were drawn up before them.
 */
final class ProposalForm
{
    /**
     * Leave the billing as it is.
     */
    public const KEEP = 'keep';

    /**
     * Put something else in its place.
     */
    public const REPLACE = 'replace';

    /**
     * End it with nothing taking its place.
     */
    public const END = 'end';

    /**
     * What the proposal asks for, in the shape it is stored in.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param array<string, array<string, mixed>> $services The chosen services, by id.
     * @return array<string, mixed>
     */
    public function changesFrom(array $data, array $services = []): array
    {
        $changes = [];

        $billings = array_merge(
            $this->linesOnExistingBillings((array)($data['lines'] ?? []), $services),
            $this->linesThatAdd((array)($data['additions'] ?? []), $services),
        );

        if ($billings !== []) {
            $changes[ProposalChanges::BILLINGS] = $billings;
        }

        $version = $this->dates(
            (array)($data['version'] ?? []),
            (array)($data['version_named'] ?? []),
        );
        if ($version !== []) {
            $changes[ProposalChanges::VERSION] = $version;
        }

        $contract = $this->dates(
            (array)($data['contract'] ?? []),
            (array)($data['contract_named'] ?? []),
        );
        if ($contract !== []) {
            $changes[ProposalChanges::CONTRACT] = $contract;
        }

        return $changes;
    }

    /**
     * What the operator confirmed, in the shape it is stored in.
     *
     * The form names these after the column they are kept in, so that a complaint about an
     * unanswered one lands on the box that answers it rather than on nothing at all.
     *
     * @param array<string, mixed> $data What the form sent.
     * @return array<string, bool>
     */
    public function confirmationsFrom(array $data): array
    {
        $confirmed = [];

        foreach ((array)($data['acknowledgements'] ?? []) as $question => $answer) {
            if (in_array($question, ProposalAcknowledgements::QUESTIONS, true)) {
                $confirmed[(string)$question] = (bool)$answer;
            }
        }

        return $confirmed;
    }

    /**
     * The lines that act on billings the contract already has.
     *
     * @param array<string, mixed> $lines One entry per billing on the contract.
     * @param array<string, array<string, mixed>> $services The chosen services, by id.
     * @return array<int, array<string, mixed>>
     */
    private function linesOnExistingBillings(array $lines, array $services): array
    {
        $asked = [];

        foreach ($lines as $billing_id => $line) {
            $line = (array)$line;
            $action = (string)($line['action'] ?? self::KEEP);

            if ($action === self::KEEP) {
                continue;
            }

            if ($action === self::END) {
                $asked[] = ['billing_id' => (string)$billing_id, 'terminates_only' => true];

                continue;
            }

            $asked[] = $this->terms($line, $services) + [
                'billing_id' => (string)$billing_id,
                'terminates_only' => false,
            ];
        }

        return $asked;
    }

    /**
     * The lines that put a billing there that was not there before.
     *
     * A row the operator opened and left alone says nothing, so it is not one.
     *
     * @param array<int, mixed> $additions The rows for new billings.
     * @param array<string, array<string, mixed>> $services The chosen services, by id.
     * @return array<int, array<string, mixed>>
     */
    private function linesThatAdd(array $additions, array $services): array
    {
        $asked = [];

        foreach ($additions as $line) {
            $line = (array)$line;

            if (($line['service_id'] ?? '') === '' && ($line['text'] ?? '') === '') {
                continue;
            }

            $asked[] = $this->terms($line, $services) + [
                'billing_id' => null,
                'terminates_only' => false,
            ];
        }

        return $asked;
    }

    /**
     * What one line says the billing is to be.
     *
     * @param array<string, mixed> $line What the form sent for it.
     * @param array<string, array<string, mixed>> $services The chosen services, by id.
     * @return array<string, mixed>
     */
    private function terms(array $line, array $services): array
    {
        $service_id = $this->text($line, 'service_id');

        return [
            'service_id' => $service_id,
            'text' => $this->text($line, 'text'),
            'quantity' => (int)($line['quantity'] ?? 1),
            'price' => $this->text($line, 'price'),
            'fixed_discount' => $this->text($line, 'fixed_discount'),
            'percentage_discount' => $this->text($line, 'percentage_discount') === null
                ? null
                : (int)$line['percentage_discount'],
            'separate_invoice' => (bool)($line['separate_invoice'] ?? false),
            'billing_until' => $this->text($line, 'billing_until'),
            'note' => $this->text($line, 'note'),
            'service' => $service_id === null ? null : ($services[$service_id] ?? null),
        ];
    }

    /**
     * The dates the form says to change, keeping apart the ones it says to clear from the ones it
     * says nothing about.
     *
     * @param array<string, mixed> $values What the date fields hold.
     * @param array<string, mixed> $named Which of them the operator asked to change at all.
     * @return array<string, string|null>
     */
    private function dates(array $values, array $named): array
    {
        $asked = [];

        foreach ($named as $field => $change) {
            if (!$change) {
                continue;
            }

            $asked[(string)$field] = $this->text($values, (string)$field);
        }

        return $asked;
    }

    /**
     * One field of the form, with an empty one meaning nothing was said.
     *
     * @param array<string, mixed> $line What the form sent.
     * @param string $field Which field.
     * @return string|null
     */
    private function text(array $line, string $field): ?string
    {
        $value = $line[$field] ?? null;

        if (is_array($value)) {
            $value = implode('-', array_filter($value, fn(mixed $part): bool => $part !== ''));
        }

        return $value === null || $value === '' ? null : (string)$value;
    }
}
