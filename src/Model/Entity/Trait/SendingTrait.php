<?php
declare(strict_types=1);

namespace App\Model\Entity\Trait;

/**
 * Shared by the records that carry when papers went out to the customer and how.
 *
 * The two belong together and are read together, so they are written as one: the day, and the way
 * in brackets after it. A version reads them off its own proposals; a proposal holds them itself.
 * Either way the listing beside them shows one column rather than two half-empty ones.
 */
trait SendingTrait
{
    /**
     * When the papers went out and how, as one line for a listing to print.
     *
     * @return string Empty when nothing has gone out yet.
     */
    public function getSending(): string
    {
        if ($this->sent_date === null) {
            return '';
        }

        // A rule keeps the two together, so the way is only missing on records from before there
        // was one to keep.
        return $this->sent_by === null
            ? (string)$this->sent_date
            : sprintf('%s (%s)', $this->sent_date, $this->sent_by->label());
    }
}
