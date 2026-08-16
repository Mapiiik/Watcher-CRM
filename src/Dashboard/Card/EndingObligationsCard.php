<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Model\Table\ContractVersionsTable;
use Cake\I18n\Date;
use Override;

/**
 * Contract versions whose minimum term runs out shortly.
 *
 * Nothing else in the application looks ahead at a date - every other listing asks what is
 * current or past - so this is the one place a term is noticed before it lapses rather
 * than after.
 */
class EndingObligationsCard extends AbstractDashboardCard
{
    /**
     * @param \App\Model\Table\ContractVersionsTable $contract_versions Contract versions table.
     */
    public function __construct(private ContractVersionsTable $contract_versions)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'ending_obligations';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Obligations Ending Soon');
    }

    /**
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['sales-representative', 'sales-manager'];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        $within_days = $this->days('contracts.obligation_within_days', 60);
        $today = Date::today();

        $query = $this->contract_versions
            ->find()
            ->contain(['Contracts' => ['Customers']])
            ->where([
                'ContractVersions.obligations_settled' => false,
                'ContractVersions.obligation_until >=' => $today,
                'ContractVersions.obligation_until <=' => $today->addDays($within_days),
            ])
            ->orderBy(['ContractVersions.obligation_until' => 'ASC']);

        $total = $query->count();

        return [
            'contract_versions' => $query->limit($this->maximumRows())->all(),
            'total' => $total,
            'within_days' => $within_days,
            'url' => [
                'controller' => 'ContractVersions',
                'action' => 'index',
                'customer_id' => false,
                '?' => ['obligations_ending' => 1, 'search' => ''],
            ],
        ];
    }
}
