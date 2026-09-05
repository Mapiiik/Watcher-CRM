<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\Model\Entity\Trait\SendingTrait;
use App\Model\Enum\ContractDeliveryMethod;
use Cake\I18n\Date;
use RuntimeException;

/**
 * ContractVersion Entity
 *
 * @property string $id
 * @property string $contract_id
 * @property \Cake\I18n\Date $valid_from
 * @property \Cake\I18n\Date|null $valid_until
 * @property \Cake\I18n\Date|null $obligation_until
 * @property bool $obligations_settled
 * @property \Cake\I18n\Date|null $conclusion_date
 * @property-read string $name
 * @property int $number_of_amendments
 * @property string|null $note
 * @property int|null $minimum_duration
 * @property string $style
 *
 * @property \App\Model\Entity\Contract $contract
 * @property array<\App\Model\Entity\ContractVersionProposal> $contract_version_proposals
 *
 * Of the proposals rather than of the version: when its papers last went out, and how. A version
 * that was fetched without them does not carry these - it raises instead of answering null, because
 * "nobody sent anything" and "nobody asked" are not the same.
 * @property \Cake\I18n\Date|null $sent_date
 * @property \App\Model\Enum\ContractDeliveryMethod|null $sent_by
 *
 * Of the query rather than of the record, and only where
 * {@see \App\Contracts\Unsigned\UnsignedPaperwork::withDeadlines()} has put them there: the
 * days an unsigned version is written to about and cut off for. Fetched any other way, a
 * version does not carry them.
 * @property \Cake\I18n\Date|null $notify_due
 * @property \Cake\I18n\Date|null $block_due
 */
class ContractVersion extends AppEntity
{
    use SendingTrait;

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'contract_id' => true,
        'valid_from' => true,
        'valid_until' => true,
        'obligation_until' => true,
        'obligations_settled' => true,
        'conclusion_date' => true,
        'number_of_amendments' => true,
        'note' => true,
        'created' => true,
        'created_by' => true,
        'modified' => true,
        'modified_by' => true,
        'contract' => true,
    ];

    /**
     * getter for minumum duration of contract in months (based on valid_from a obligation_until params)
     *
     * @return int|null
     */
    protected function _getMinimumDuration(): ?int
    {
        if (isset($this->obligation_until) && ($this->valid_from < $this->obligation_until)) {
            return $this->valid_from->diffInMonths($this->obligation_until->addDays(1));
        }

        return null;
    }

    /**
     * When the papers for this version last went out, and how.
     *
     * A version does not hold this itself: the sending belongs to the papers, and the papers belong
     * to the proposal they were drawn from - a version may have several of those behind it. The
     * latest one is what these answer with, which is also what the wait for a signature counts from.
     *
     * @return \Cake\I18n\Date|null
     * @throws \RuntimeException When the proposals were not fetched.
     */
    protected function _getSentDate(): ?Date
    {
        return $this->lastSending()?->sent_date;
    }

    /**
     * @return \App\Model\Enum\ContractDeliveryMethod|null
     * @throws \RuntimeException When the proposals were not fetched.
     */
    protected function _getSentBy(): ?ContractDeliveryMethod
    {
        return $this->lastSending()?->sent_by;
    }

    /**
     * The proposal whose papers went out last, of the ones drawn up on this version.
     *
     * @return \App\Model\Entity\ContractVersionProposal|null
     * @throws \RuntimeException When the proposals were not fetched.
     */
    private function lastSending(): ?ContractVersionProposal
    {
        if (!isset($this->contract_version_proposals)) {
            throw new RuntimeException(__('Contract version proposal data not available.'));
        }

        $latest = null;

        foreach ($this->contract_version_proposals as $proposal) {
            if ($proposal->sent_date === null) {
                continue;
            }

            if ($latest === null || $proposal->sent_date > $latest->sent_date) {
                $latest = $proposal;
            }
        }

        return $latest;
    }

    /**
     * How a version reads where one has to be named: the stretch it covers.
     *
     * A version has no name of its own - the contract carries the number - so wherever one is
     * offered for choosing or pointed at, it is said by the days it runs between. Written here
     * rather than at each of those places, which is where it used to live five times over.
     *
     * @return string
     */
    protected function _getName(): string
    {
        return $this->valid_from . ' - ' . ($this->valid_until ?: __('indefinitely'));
    }

    /**
     * getter for style
     *
     * @return string
     */
    protected function _getStyle(): string
    {
        $style = '';
        $now = Date::now();

        if (isset($this->valid_from) && $this->valid_from > $now) {
            $style = 'color: darkorange;';
        }

        if (isset($this->valid_until) && $this->valid_until < $now) {
            if ($this->valid_until < $now->firstOfMonth()) {
                $style = 'color: darkgray; text-decoration: line-through;';
            } else {
                $style = 'color: darkgray;';
            }
        }

        return $style;
    }
}
