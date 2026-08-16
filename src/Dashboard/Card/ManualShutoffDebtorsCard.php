<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Model\Enum\IpAddressTypeOfUse;
use App\Model\Enum\IpNetworkTypeOfUse;
use App\Model\Table\ContractsTable;
use Override;

/**
 * Running services of debtors that the automatic blocking cannot reach.
 *
 * Blocking works by writing the customer's addresses into a firewall list, so it only
 * bites where the contract has an address of the customer's to write. A layer two circuit
 * has none - at most a technology address, and that is our own device rather than the
 * customer's - so nothing the automation does would stop the service. A contract marked
 * VIP is passed over by the nightly run whatever addresses it has, which leaves it in the
 * same place.
 *
 * The service type is deliberately not asked. What decides this is what is assigned to the
 * contract, not what the type says it usually carries.
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

        $with_address = $this->contracts->IpAddresses
            ->find()
            ->select(['IpAddresses.contract_id'])
            ->where(['IpAddresses.type_of_use IN' => IpAddressTypeOfUse::customerCases()]);

        $with_network = $this->contracts->IpNetworks
            ->find()
            ->select(['IpNetworks.contract_id'])
            ->where(['IpNetworks.type_of_use IN' => IpNetworkTypeOfUse::customerCases()]);

        $query = $this->contracts
            ->find()
            ->contain(['Customers', 'ServiceTypes', 'ContractStates'])
            ->where([
                'Contracts.customer_id IN' => $debtor_ids,
                'ContractStates.active_services' => true,
            ])
            ->where([
                // nothing of the customer's to write into the firewall, or a contract the
                // nightly run passes over anyway
                'OR' => [
                    'AND' => [
                        ['Contracts.id NOT IN' => $with_address],
                        ['Contracts.id NOT IN' => $with_network],
                    ],
                    'Contracts.vip' => true,
                ],
            ])
            ->orderBy(['Contracts.nid' => 'DESC']);

        $total = $query->count();

        return [
            'contracts' => $query->limit($this->maximumRows())->all(),
            'total' => $total,
        ];
    }
}
