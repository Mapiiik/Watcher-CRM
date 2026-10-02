<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use App\Check\AbstractCheckRegistry;
use App\Check\CheckScope;
use App\Model\Table\AddressesTable;
use App\Model\Table\ContractsTable;
use App\Model\Table\CustomersTable;

/**
 * Registry of the checks that can be run against the addresses on record.
 *
 * This is the single extension point: register a check here, give it a template beside the
 * others, and both the dashboard card and the overview pick it up.
 *
 * @extends \App\Check\AbstractCheckRegistry<\App\Addresses\Check\AddressCheckInterface>
 */
final class AddressCheckRegistry extends AbstractCheckRegistry
{
    /**
     * Registered in the order they are listed.
     *
     * @param bool $ignore_inactive Whether the checks keep to what is running. Each applies
     *   it to its own subject - a customer for the ones about customers, the address itself
     *   for the ones about where a service sits - so that the answer is about the record
     *   being reported rather than about something else its customer happens to have. Off,
     *   the checks report the history as well, which is what putting the history straight
     *   needs and what daily work does not.
     * @param string|null $contract_id One contract to ask about, rather than the whole file.
     *   Most of these are about a customer's address book rather than about one contract and
     *   leave themselves out when asked; the one whose subject is the contracts does not.
     * @param string|null $customer_id One customer to ask about, rather than the whole file.
     *   This is what lets a customer show the findings on their own addresses.
     */
    public function __construct(
        bool $ignore_inactive = true,
        ?string $contract_id = null,
        ?string $customer_id = null,
    ) {
        $this->scope = new CheckScope($ignore_inactive, $contract_id, $customer_id);

        /** @var \App\Model\Table\CustomersTable $customers */
        $customers = $this->fetchTable(CustomersTable::class);
        /** @var \App\Model\Table\ContractsTable $contracts */
        $contracts = $this->fetchTable(ContractsTable::class);
        /** @var \App\Model\Table\AddressesTable $addresses */
        $addresses = $this->fetchTable(AddressesTable::class);

        $this->factories = [
            'unclear_billing_address' => fn() => new UnclearBillingAddressCheck($customers, $this->scope),
            'missing_installation_address' => fn() => new MissingInstallationAddressCheck($contracts, $this->scope),
            'unlocated_installation_address' => fn() => new UnlocatedInstallationAddressCheck($addresses, $this->scope),
            'duplicate_address' => fn() => new DuplicateAddressCheck($addresses, $this->scope),
            'unregistered_installation_address' =>
                fn() => new UnregisteredInstallationAddressCheck($addresses, $this->scope),
            'several_contracts_at_one_address' =>
                fn() => new SeveralContractsAtOneAddressCheck($contracts, $this->scope),
        ];
    }
}
