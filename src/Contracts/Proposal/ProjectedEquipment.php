<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

/**
 * The equipment lent with a contract as a proposal would leave it.
 *
 * Kept in three lists because the papers ask three questions: what the customer goes on having,
 * what is handed over now and what is taken back now.
 */
final class ProjectedEquipment
{
    /**
     * @param list<\App\Model\Entity\BorrowedEquipment> $continuing What stays lent as it is.
     * @param list<\App\Model\Entity\BorrowedEquipment> $lent What the proposal lends.
     * @param list<\App\Model\Entity\BorrowedEquipment> $returned What it takes back.
     */
    public function __construct(
        public readonly array $continuing,
        public readonly array $lent,
        public readonly array $returned,
    ) {
    }

    /**
     * Everything lent once the proposal has been applied.
     *
     * @return list<\App\Model\Entity\BorrowedEquipment>
     */
    public function afterwards(): array
    {
        return array_merge($this->continuing, $this->lent);
    }

    /**
     * Whether something is handed over one way and something else the other.
     *
     * @return bool
     */
    public function isAnExchange(): bool
    {
        return $this->lent !== [] && $this->returned !== [];
    }
}
