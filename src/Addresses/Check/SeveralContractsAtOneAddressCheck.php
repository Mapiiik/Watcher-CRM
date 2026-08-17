<?php
declare(strict_types=1);

namespace App\Addresses\Check;

use App\Model\Table\ContractsTable;
use Cake\Database\Expression\IdentifierExpression;
use Cake\ORM\Query\SelectQuery;
use Override;

/**
 * Customers running more than one contract at the very same address.
 *
 * A customer with services at several places is ordinary and says nothing. Several running
 * services at one address is the unusual way round: sometimes it is right - a building with
 * its own connection per floor, a company with two lines on purpose - and sometimes the
 * address is simply wrong, picked by mistake when the contract was made or carried in by an
 * import and never put right.
 *
 * Only contracts whose state still runs services are counted. Without that, an ended
 * contract and the one that replaced it at the same address would be reported as a pair,
 * which is what ordinary history looks like rather than anything worth reading.
 *
 * Wrongly typed installation addresses fall out of this by way of the association's own
 * condition; {@see \App\Addresses\Check\MissingInstallationAddressCheck} is what reports
 * those.
 */
class SeveralContractsAtOneAddressCheck extends AbstractAddressCheck
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
        return 'several_contracts_at_one_address';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Several Contracts at One Address');
    }

    /**
     * @return string
     */
    #[Override]
    public function emptyMessage(): string
    {
        return __('No customer runs more than one contract at the same address.');
    }

    /**
     * @return bool
     */
    #[Override]
    public function onDashboard(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    #[Override]
    public function optional(): bool
    {
        return true;
    }

    /**
     * One row per customer and address, with how many contracts sit there.
     *
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    #[Override]
    public function find(): SelectQuery
    {
        $query = $this->contracts->find();

        return $query
            ->select([
                'customer_id' => 'Contracts.customer_id',
                'installation_address_id' => 'Contracts.installation_address_id',
                'total' => $query->func()->count('*'),
            ], true)
            // grouped by both keys, so the rest of either table comes along with it
            ->select($this->contracts->Customers)
            ->select($this->contracts->InstallationAddresses)
            ->contain(['Customers', 'InstallationAddresses'])
            ->innerJoinWith('ContractStates')
            ->where([
                'ContractStates.active_services' => true,
                // Contracts with no installation address would otherwise gather into a
                // group of their own per customer, all of them sharing a null, and be
                // reported as sitting at one address when they sit at none. That is what
                // `MissingInstallationAddressCheck` is for. The association's type
                // condition nulls a wrongly typed address the same way, and it belongs to
                // that check too.
                'InstallationAddresses.id IS NOT' => null,
            ])
            ->groupBy([
                new IdentifierExpression('Customers.id'),
                new IdentifierExpression('InstallationAddresses.id'),
                'Contracts.customer_id',
                'Contracts.installation_address_id',
            ])
            ->having([$query->expr()->gt($query->func()->count('*'), 1, 'integer')])
            ->orderBy(['COUNT(*)' => 'DESC']);
    }
}
