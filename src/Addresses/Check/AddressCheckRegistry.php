<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use App\Model\Table\AddressesTable;
use App\Model\Table\ContractsTable;
use App\Model\Table\CustomersTable;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Registry of the checks that can be run against the addresses on record.
 *
 * This is the single extension point: register a check here, give it a template beside the
 * others, and both the dashboard card and the overview pick it up. Checks are built lazily,
 * so registering one costs nothing until it is asked something - which matters on the
 * overview, where a check nobody ticked must not run its query.
 */
final class AddressCheckRegistry
{
    use LocatorAwareTrait;

    /**
     * @var array<string, callable(): \App\Addresses\Check\AddressCheckInterface>
     */
    private array $factories = [];

    /**
     * @var array<string, \App\Addresses\Check\AddressCheckInterface>
     */
    private array $built = [];

    /**
     * Registered in the order they are listed.
     */
    public function __construct()
    {
        /** @var \App\Model\Table\CustomersTable $customers */
        $customers = $this->fetchTable(CustomersTable::class);
        /** @var \App\Model\Table\ContractsTable $contracts */
        $contracts = $this->fetchTable(ContractsTable::class);
        /** @var \App\Model\Table\AddressesTable $addresses */
        $addresses = $this->fetchTable(AddressesTable::class);

        $this->factories = [
            'unclear_billing_address' =>
                fn(): AddressCheckInterface => new UnclearBillingAddressCheck($customers),
            'missing_installation_address' =>
                fn(): AddressCheckInterface => new MissingInstallationAddressCheck($contracts),
            'unlocated_installation_address' =>
                fn(): AddressCheckInterface => new UnlocatedInstallationAddressCheck($addresses),
            'duplicate_address' =>
                fn(): AddressCheckInterface => new DuplicateAddressCheck($addresses),
            'unregistered_installation_address' =>
                fn(): AddressCheckInterface => new UnregisteredInstallationAddressCheck($addresses),
            'several_contracts_at_one_address' =>
                fn(): AddressCheckInterface => new SeveralContractsAtOneAddressCheck($contracts),
        ];
    }

    /**
     * The check registered under the given id, or null where there is none.
     *
     * @param string $id Registry key.
     * @return \App\Addresses\Check\AddressCheckInterface|null
     */
    public function get(string $id): ?AddressCheckInterface
    {
        if (!isset($this->factories[$id])) {
            return null;
        }

        // A check is asked how many records it found and then asked for them, so it is kept
        // rather than built twice.
        return $this->built[$id] ??= ($this->factories[$id])();
    }

    /**
     * Every check, in the order they are registered.
     *
     * @return list<\App\Addresses\Check\AddressCheckInterface>
     */
    public function all(): array
    {
        $checks = [];
        foreach (array_keys($this->factories) as $id) {
            $check = $this->get($id);
            if ($check !== null) {
                $checks[] = $check;
            }
        }

        return $checks;
    }

    /**
     * The checks the dashboard card counts.
     *
     * @return list<\App\Addresses\Check\AddressCheckInterface>
     */
    public function forDashboard(): array
    {
        return array_values(array_filter(
            $this->all(),
            fn(AddressCheckInterface $check): bool => $check->onDashboard(),
        ));
    }
}
