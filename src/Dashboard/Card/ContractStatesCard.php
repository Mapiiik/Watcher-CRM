<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use App\Model\Entity\ContractState;
use App\Model\Table\ContractStatesTable;
use Override;

/**
 * How many contracts stand in each state picked out for the dashboard.
 *
 * Which states appear, and to whom, is set on the state itself rather than here, so a
 * state worth watching - waiting to be uninstalled, say - reaches the dashboard without a
 * deploy.
 */
class ContractStatesCard extends AbstractCustomerListingCard
{
    /**
     * @param \App\Model\Table\ContractStatesTable $contract_states Contract states table.
     * @param string|null $role The role of the signed-in operator.
     */
    public function __construct(private ContractStatesTable $contract_states, private ?string $role)
    {
    }

    /**
     * @return string
     */
    #[Override]
    public function id(): string
    {
        return 'contract_states';
    }

    /**
     * @return string
     */
    #[Override]
    public function title(): string
    {
        return __('Contract States');
    }

    /**
     * The listing behind the counts is only offered to the roles that may search customers
     * by state at all.
     *
     * @return list<string>
     */
    #[Override]
    public function roles(): array
    {
        return ['network-manager', 'sales-representative', 'sales-manager', 'bookkeeper'];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function data(): array
    {
        /** @var list<\App\Model\Entity\ContractState> $states */
        $states = $this->contract_states
            ->find()
            ->where(['ContractStates.show_on_dashboard' => true])
            ->orderBy(['ContractStates.name' => 'ASC'])
            ->all()
            ->filter(fn(ContractState $state): bool => $state->isOnDashboardFor($this->role))
            ->toList();

        if ($states === []) {
            return ['states' => [], 'counts' => [], 'urls' => []];
        }

        $counts = $this->contract_states->Contracts->find();
        $counts = $counts
            ->select([
                'contract_state_id' => 'Contracts.contract_state_id',
                'total' => $counts->func()->count('*'),
            ])
            ->where(['Contracts.contract_state_id IN' => array_column($states, 'id')])
            ->groupBy('Contracts.contract_state_id')
            ->disableHydration()
            ->all()
            ->combine('contract_state_id', 'total')
            ->toArray();

        $urls = [];
        foreach ($states as $state) {
            $urls[$state->id] = $this->customerListingUrl(['contract_state_id' => $state->id]);
        }

        return [
            'states' => $states,
            'counts' => $counts,
            'urls' => $urls,
        ];
    }
}
