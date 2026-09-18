<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Model\Enum\ProposalStep;
use Cake\I18n\Date;
use Cake\I18n\DateTime;

/**
 * Where one set of papers stands, worked out from how far it was ever meant to travel.
 *
 * The two agendas keep different fields and used to answer this each for itself, which is why a
 * paper that is only handed over had nowhere to fit: it never gets signed, so it would have read as
 * waiting for ever. Here the last step the papers are meant to reach is asked once and everything
 * follows from it.
 *
 * Nothing is read from a record, so every combination can be put to it directly.
 */
final class Settlement
{
    /**
     * @param \App\Model\Enum\ProposalStep $ends_at The last step these papers are meant to reach.
     * @param \Cake\I18n\Date|null $sent The day the papers went out.
     * @param \Cake\I18n\Date|null $concluded The day they stopped being work.
     * @param \Cake\I18n\DateTime|null $applied When what they asked for reached the live records.
     * @param \Cake\I18n\DateTime|null $revoked When they were given up on.
     */
    public function __construct(
        private readonly ProposalStep $ends_at,
        private readonly ?Date $sent,
        private readonly ?Date $concluded,
        private readonly ?DateTime $applied = null,
        private readonly ?DateTime $revoked = null,
    ) {
    }

    /**
     * Whether anybody is still waiting for these papers.
     *
     * Being given up on settles anything. Otherwise it is the last step that says so, and only that
     * one - a paper applied is settled by applying the changes, however long it was signed first.
     *
     * @return bool
     */
    public function isSettled(): bool
    {
        if ($this->revoked !== null) {
            return true;
        }

        return $this->ends_at === ProposalStep::Applied
            ? $this->applied !== null
            : $this->concluded !== null;
    }

    /**
     * Whether the papers ever go through the given step.
     *
     * What the step is called in front of an operator is the page's to say; this only answers
     * whether there is anything to offer.
     *
     * @param \App\Model\Enum\ProposalStep $step The step being asked about.
     * @return bool
     */
    public function expects(ProposalStep $step): bool
    {
        return $this->ends_at->goesThrough($step);
    }

    /**
     * Whether the step has been taken.
     *
     * @param \App\Model\Enum\ProposalStep $step The step being asked about.
     * @return bool
     */
    public function hasTaken(ProposalStep $step): bool
    {
        return match ($step) {
            ProposalStep::Issued => true,
            ProposalStep::Delivered => $this->sent !== null,
            ProposalStep::Signed => $this->concluded !== null,
            ProposalStep::Applied => $this->applied !== null,
        };
    }

    /**
     * Whether the step is still outstanding.
     *
     * A step that has been taken, one the papers never go through, and anything on papers nobody
     * is waiting for any more all answer no. The steps are not held to their order: a signature
     * turns up whether or not anybody wrote down that the papers went out.
     *
     * @param \App\Model\Enum\ProposalStep $step The step being asked about.
     * @return bool
     */
    public function isDueFor(ProposalStep $step): bool
    {
        return !$this->isSettled() && $this->expects($step) && !$this->hasTaken($step);
    }

    /**
     * Where the papers stand, in one word for a listing to print.
     *
     * Read backwards, so that what settled them comes before what only moved them along. Which word
     * the last step gets is the point: papers that are only handed over are issued rather than
     * signed, and ones that change something are not finished by the signature.
     *
     * @return string
     */
    public function state(): string
    {
        return match (true) {
            $this->applied !== null && $this->expects(ProposalStep::Applied) => __('Changes'
                . ' applied'),
            $this->revoked !== null => __('Revoked'),
            $this->concluded !== null => $this->wordForConcluded(),
            $this->sent !== null && $this->expects(ProposalStep::Delivered) => __('Sent'),
            default => __('Being prepared'),
        };
    }

    /**
     * What the day the papers stopped being work is called, which depends on what ended them.
     *
     * @return string
     */
    private function wordForConcluded(): string
    {
        return match ($this->ends_at) {
            ProposalStep::Issued => __('Issued'),
            ProposalStep::Delivered => __('Delivered'),
            ProposalStep::Signed => __('Signed'),
            ProposalStep::Applied => __('Waiting for the changes to be applied'),
        };
    }
}
