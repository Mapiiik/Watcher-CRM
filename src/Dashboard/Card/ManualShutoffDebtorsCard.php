<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Model\Table\ContractsTable;
use Override;

/**
 * Running services of debtors that the automatic blocking cannot reach.
 *
 * Blocking works by writing a customer's addresses into a router's firewall list, so it
 * only bites where the service type keeps RADIUS accounts. Anything else - a service
 * billed but not carried over our own network - keeps running until somebody switches it
 * off by hand, and that is what this card lists.
 */
class ManualShutoffDebtorsCard extends AbstractDebtorCard
{
    /**
     * @param \App\Model\Table\ContractsTable $contracts Contracts table.
     */
    public function __construct(private ContractsTable $contracts)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'manual_shutoff_debtors';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Debts Beyond Automatic Blocking');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['bookkeeper', 'sales-manager'];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        // The subquery stands in an `IN`, which takes one column rather than the two the
        // processor reports.
        $debtor_ids = $this->processor()
            ->findFilteredOverdueDebtorIds()
            ->select(['customer_id' => 'Invoices.customer_id'], true);

        $query = $this->contracts
            ->find()
            ->contain(['Customers', 'ServiceTypes', 'ContractStates'])
            ->where([
                'Contracts.customer_id IN' => $debtor_ids,
                'ServiceTypes.have_radius_accounts' => false,
                'ContractStates.active_services' => true,
            ])
            ->orderBy(['Contracts.nid' => 'DESC']);

        $total = $query->count();

        return [
            'contracts' => $query->limit($this->maximumRows())->all(),
            'total' => $total,
        ];
    }
}
