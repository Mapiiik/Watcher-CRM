<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use App\Model\Enum\AddressType;
use App\Model\Table\ContractsTable;
use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Contracts that are supposed to sit at an address and do not.
 *
 * Which service types need one is on the type itself, as `installation_address_required`.
 * Three ways of not having one are reported together, because they read the same to whoever
 * has to fix it - the contract has no place on record:
 *
 * - no installation address at all;
 * - one whose type has since been changed to something other than Installation, which makes
 *   the `InstallationAddresses` association on `ContractsTable` stop containing it, so the
 *   contract looks addressless everywhere without anything having been deleted;
 * - one belonging to a different customer, which is a plain mix-up.
 *
 * The last two find nothing today. They are here so that the day they do, somebody is told
 * rather than the address quietly going missing from the contract's pages.
 */
class MissingInstallationAddressCheck extends AbstractAddressCheck
{
    /**
     * @param \App\Model\Table\ContractsTable $contracts Contracts table.
     * @param bool $ignore_inactive Whether to pass over contracts with nothing running.
     * @param string|null $contract_id The one contract being asked about, where there is one.
     * @param string|null $customer_id The one customer being asked about, where there is one.
     */
    public function __construct(
        private ContractsTable $contracts,
        private bool $ignore_inactive = true,
        ?string $contract_id = null,
        ?string $customer_id = null,
    ) {
        parent::__construct($contract_id, $customer_id);
    }

    /**
     * @return string|null
     */
    #[Override]
    protected function customerField(): ?string
    {
        return 'Contracts.customer_id';
    }

    /**
     * @return string|null
     */
    #[Override]
    protected function contractField(): ?string
    {
        return 'Contracts.id';
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'missing_installation_address';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Contract Without an Installation Address');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('Every contract that needs an installation address has one.');
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        // The association carries a type condition, so it cannot be used to look for an
        // address of the wrong type - it would leave those out. This joins the addresses
        // plainly and asks about the type itself.
        $query = $this->contracts
            ->find()
            ->contain(['Customers', 'ServiceTypes', 'ContractStates'])
            ->leftJoin(
                ['AnyInstallationAddress' => 'addresses'],
                ['AnyInstallationAddress.id' => new IdentifierExpression('Contracts.installation_address_id')],
            )
            ->where(['ServiceTypes.installation_address_required' => true]);

        if ($this->ignore_inactive) {
            $query->where(['Contracts.id IN' => $this->activeContractIds()]);
        }

        $this->scoped($query);

        return $query
            ->where([
                'OR' => [
                    ['Contracts.installation_address_id IS' => null],
                    // The alias is a plain table rather than an association, so the enum
                    // the column is mapped to elsewhere has to be spelled out here - bound
                    // as the enum itself it would be handed to the driver as a string.
                    $query->expr()->notEq(
                        new IdentifierExpression('AnyInstallationAddress.type'),
                        AddressType::Installation->value,
                        'integer',
                    ),
                    $query->expr()->notEq(
                        new IdentifierExpression('AnyInstallationAddress.customer_id'),
                        new IdentifierExpression('Contracts.customer_id'),
                    ),
                ],
            ])
            ->orderBy(['Contracts.nid' => 'DESC']);
    }
}
