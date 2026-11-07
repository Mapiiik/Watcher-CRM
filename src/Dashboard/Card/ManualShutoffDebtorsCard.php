<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Model\Enum\IpAddressTypeOfUse;
use App\Model\Enum\IpNetworkTypeOfUse;
use App\Model\Table\ContractsTable;
use Override;
use Settings\Utility\Settings;

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
 * contract, not what the type says it usually carries. Television is the one exception: it has
 * no address to write, and the nightly run switches it off at the provider all the same.
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
        return ['bookkeeper', 'sales-manager', 'sales-representative', 'manager'];
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
            ->find('withActiveServices')
            ->contain(['Customers', 'ServiceTypes'])
            ->where(['Contracts.customer_id IN' => $debtor_ids])
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
            ->where($this->notSwitchedOffAtTheProvider())
            ->orderBy(['Contracts.nid' => 'DESC']);

        $total = $query->count();

        return [
            'contracts' => $query->limit($this->maximumRows())->all(),
            'total' => $total,
        ];
    }

    /**
     * Leaves out television the nightly run switches off itself. VIP television stays, as the run
     * passes it over like any other VIP contract.
     *
     * @return array<string, mixed>
     */
    private function notSwitchedOffAtTheProvider(): array
    {
        $television = (array)Settings::get(
            'bookkeeping.debtors.blocking.services.sledovani_tv.service_type_ids',
            [],
        );
        if ($television === [] || !$this->processor()->isDebtorBlockingEnabled('sledovani_tv')) {
            return [];
        }

        return ['OR' => ['Contracts.service_type_id NOT IN' => $television, 'Contracts.vip' => true]];
    }
}
