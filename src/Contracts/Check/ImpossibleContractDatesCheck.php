<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Model\Table\ContractsTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * A contract carrying a day that cannot be right.
 *
 * A service taken away or stopped before it was ever installed is not something that
 * happened, and neither is an installation in the year 20015 - both are in the file. What
 * they have in common is that there is nothing to weigh up: the day that was meant is
 * obvious, it was simply typed wrong.
 *
 * A service stopped before it is taken away is ordinary, so the two are not compared: the
 * kit usually comes back some days after the last one is billed for.
 */
class ImpossibleContractDatesCheck extends AbstractContractCheck
{
    /**
     * @param \App\Model\Table\ContractsTable $contracts Contracts table.
     * @param bool $ignore_inactive Whether to count only the contracts that are running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private ContractsTable $contracts,
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
        return 'impossible_contract_dates';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Impossible Contract Dates');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract carries days that can be right.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->ignore_inactive
            ? $this->contracts->find('withActiveServices')
            : $this->contracts->find();

        $query
            ->contain(['Customers'])
            ->where([
                $query->expr()->or([
                    // taken away before it was installed
                    $query->expr()->lt(
                        'Contracts.uninstallation_date',
                        $query->identifier('Contracts.installation_date'),
                    ),
                    // stopped before it was installed
                    $query->expr()->lt(
                        'Contracts.termination_date',
                        $query->identifier('Contracts.installation_date'),
                    ),
                    $this->implausibleDate($query, 'Contracts.installation_date'),
                    $this->implausibleDate($query, 'Contracts.uninstallation_date'),
                    $this->implausibleDate($query, 'Contracts.termination_date'),
                ]),
            ])
            ->orderBy(['Contracts.number' => 'ASC']);

        return $this->scoped($query, 'Contracts.id');
    }
}
