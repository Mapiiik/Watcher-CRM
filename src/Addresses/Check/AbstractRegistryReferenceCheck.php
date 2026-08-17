<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use App\Model\Table\AddressesTable;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Shared ground for the two checks on addresses that carry no reference into the address
 * registry.
 *
 * Both look only at addresses a running service actually sits at. An address nobody is
 * connected at costs nothing by being incomplete, and there are enough of those to bury the
 * ones that matter.
 *
 * The reference matters beyond tidiness. `OverviewsController::overviewOfCzechCustomer-
 * ConnectionSpeeds()` and the connection points beside it select on
 * `address_registry_reference IS NOT NULL`, so a connection without one is left out of what
 * is reported to the regulator without saying so; and `RegistryAddressFilter` cannot reach
 * the customer with a message addressed to their building.
 */
abstract class AbstractRegistryReferenceCheck extends AbstractAddressCheck
{
    /**
     * @param \App\Model\Table\AddressesTable $addresses Addresses table.
     */
    public function __construct(protected AddressesTable $addresses)
    {
    }

    /**
     * Both report the same thing about the same kind of record, so they are listed by the
     * same template rather than by two copies of it.
     *
     * @return string
     */
    #[Override]
    public function template(): string
    {
        return 'installation_address';
    }

    /**
     * Addresses a contract with running services sits at, and which have no reference into
     * the registry.
     *
     * The live contracts are asked for as a subquery rather than joined, so that an address
     * with several contracts on it stays one row.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    protected function unregistered(): SelectQuery
    {
        $live = $this->addresses->Contracts
            ->find()
            ->select(['Contracts.installation_address_id'])
            ->innerJoinWith('ContractStates')
            ->where([
                'ContractStates.active_services' => true,
                'Contracts.installation_address_id IS NOT' => null,
            ]);

        return $this->addresses
            ->find()
            ->contain(['Customers'])
            ->where([
                'Addresses.id IN' => $live,
                'Addresses.address_registry_reference IS' => null,
            ])
            ->orderBy(['Addresses.city' => 'ASC', 'Addresses.street' => 'ASC']);
    }
}
