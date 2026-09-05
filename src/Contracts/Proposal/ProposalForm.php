<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Enum\ProposalPurpose;

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
     * The field the version keeps its end under.
     */
    private const ENDS_ON = 'valid_until';

    /**
     * What the head of the form asks of the version and the contract, laid over what the proposal
     * already asks of the billings.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param \App\Contracts\Proposal\ProposalChanges $existing What the proposal asks for now.
     * @param \App\Model\Enum\ProposalPurpose $purpose What the papers are being drawn up for.
     * @return array<string, mixed>
     */
    public function changesFrom(array $data, ProposalChanges $existing, ProposalPurpose $purpose): array
    {
        if ($purpose === ProposalPurpose::Termination) {
            return $this->ending($data, $existing);
        }

        return $existing
            ->withVersion(ProposedVersion::fromArray($this->dates(
                (array)($data['version_change'] ?? []),
                (array)($data['version_change_named'] ?? []),
            )))
            ->withContract(ProposedContract::fromArray([]))
            ->toArray();
    }

    /**
     * What an ending asks for, written from the one day it is given.
     *
     * A contract ends on the day its version stops being valid, so the operator says that day once
     * and both records follow from it. Ending the version alone leaves the contract running, which
     * is how an agreement to end one version with another to follow it is written.
     *
     * @param array<string, mixed> $data What the form sent.
     * @param \App\Contracts\Proposal\ProposalChanges $existing What the proposal asks for now.
     * @return array<string, mixed>
     */
    private function ending(array $data, ProposalChanges $existing): array
    {
        $day = $data['ends_on'] ?? null;
        $said = is_string($day) && trim($day) !== '' ? [self::ENDS_ON => trim($day)] : [];

        return $existing
            ->withVersion(ProposedVersion::fromArray($said))
            ->withContract(ProposedContract::fromArray(
                empty($data['version_only']) ? $this->contractEnd($said) : [],
            ))
            ->toArray();
    }

    /**
     * The same day again, under the name the contract keeps it by.
     *
     * @param array<string, string> $said The day the version ends, if one was given.
     * @return array<string, string>
     */
    private function contractEnd(array $said): array
    {
        return $said === [] ? [] : ['termination_date' => $said[self::ENDS_ON]];
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
