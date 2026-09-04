<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

/**
 * What the head of a proposal says, read as what it asks for.
 *
 * Only the version and the contract are asked about here. The billing lines are edited one at a
 * time from the proposal's own table, so they never travel in this submission - which is what keeps
 * a field name from meeting an association on the proposal, as `contract` once did.
 *
 * The fields are named apart from the records they speak of for the same reason.
 */
final class ProposalForm
{
    /**
     * What the head of the form asks of the version and the contract, laid over what the proposal
     * already asks of the billings.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param \App\Contracts\Proposal\ProposalChanges $existing What the proposal asks for now.
     * @return array<string, mixed>
     */
    public function changesFrom(array $data, ProposalChanges $existing): array
    {
        return $existing
            ->withVersion(ProposedVersion::fromArray($this->dates(
                (array)($data['version_change'] ?? []),
                (array)($data['version_change_named'] ?? []),
            )))
            ->withContract(ProposedContract::fromArray($this->dates(
                (array)($data['contract_change'] ?? []),
                (array)($data['contract_change_named'] ?? []),
            )))
            ->toArray();
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

        foreach ((array)($data['confirmations'] ?? []) as $question => $answer) {
            if (in_array($question, ProposalConfirmations::QUESTIONS, true)) {
                $confirmed[(string)$question] = (bool)$answer;
            }
        }

        return $confirmed;
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

            $value = $values[(string)$field] ?? null;
            $asked[(string)$field] = $value === null || $value === '' || is_array($value)
                ? null
                : (string)$value;
        }

        return $asked;
    }
}
