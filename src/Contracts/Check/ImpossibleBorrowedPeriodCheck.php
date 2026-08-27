<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\BorrowedEquipmentsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Equipment lent over a stretch of time that cannot exist.
 *
 * Returned before it went out, or a day naming a year that cannot be right - the file holds
 * kit returned in the year 24, which was meant to be 2024. Both leave the equipment counted
 * as somewhere it is not, and both are put right by typing the day that was meant.
 */
class ImpossibleBorrowedPeriodCheck extends AbstractContractCheck
{
    /**
     * @param \App\Model\Table\BorrowedEquipmentsTable $equipments Borrowed equipments table.
     * @param bool $ignore_inactive Whether to count only the contracts that are running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private BorrowedEquipmentsTable $equipments,
        bool $ignore_inactive = true,
        ?string $contract_id = null,
        ?string $customer_id = null,
    ) {
        parent::__construct($ignore_inactive, $contract_id, $customer_id);
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'impossible_borrowed_period';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Impossible Borrowing Period');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every piece of equipment is lent over a stretch of time that can exist.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->equipments->find();

        $query
            ->contain(['Contracts', 'EquipmentTypes'])
            ->where([
                $query->expr()->or([
                    // back before it went out
                    $query->expr()->lt(
                        'BorrowedEquipments.borrowed_until',
                        $query->identifier('BorrowedEquipments.borrowed_from'),
                    ),
                    $this->implausibleDate($query, 'BorrowedEquipments.borrowed_from'),
                    $this->implausibleDate($query, 'BorrowedEquipments.borrowed_until'),
                ]),
            ])
            ->orderBy(['BorrowedEquipments.borrowed_from' => 'ASC']);

        if ($this->ignore_inactive) {
            $this->onlyRunningContracts($query);
        }

        return $this->scoped($query, 'BorrowedEquipments.contract_id');
    }
}
