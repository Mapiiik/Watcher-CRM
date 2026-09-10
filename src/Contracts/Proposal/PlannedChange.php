<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use Cake\I18n\Date;

/**
 * One field a transfer will write, and what it will write over.
 *
 * Read twice: once by the preview, which draws it, and once by the transfer, which does it. That
 * is the whole point of holding it as a value rather than working it out where it is needed - a
 * preview that derived the same rules a second time could disagree with what then happened.
 */
final class PlannedChange
{
    /**
     * @param string $target Which record - one of {@see \App\Contracts\Proposal\TransferPlan}'s.
     * @param string|null $id Which one of them, for a page that wants to link to it.
     * @param string $record What to call it.
     * @param string $field The column.
     * @param string $label What to call the column.
     * @param \Cake\I18n\Date|string|int|null $from What it says now.
     * @param \Cake\I18n\Date|string|int|null $to What it will say.
     * @param bool $asked Whether somebody asked for this, or it follows from carrying the papers over.
     */
    public function __construct(
        public readonly string $target,
        public readonly ?string $id,
        public readonly string $record,
        public readonly string $field,
        public readonly string $label,
        public readonly Date|int|string|null $from,
        public readonly Date|int|string|null $to,
        public readonly bool $asked,
    ) {
    }

    /**
     * What to call the agenda the record belongs to.
     *
     * Worked out from the record rather than carried on it, because it is the same answer for
     * every write onto the same kind of thing.
     *
     * @return string
     */
    public function agenda(): string
    {
        return match ($this->target) {
            TransferPlan::CONTRACT => __('Contract'),
            TransferPlan::VERSION, TransferPlan::REPLACED_VERSION => __('Contract Version'),
            default => $this->target,
        };
    }

    /**
     * Whether the field would end up saying what it already says.
     *
     * Still written, and still shown: what is worth knowing is that the transfer touches the
     * field, not that it happens to leave it where it was.
     *
     * @return bool
     */
    public function changesNothing(): bool
    {
        return (string)$this->from === (string)$this->to;
    }
}
