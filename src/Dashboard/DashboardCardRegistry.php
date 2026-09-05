<?php
declare(strict_types=1);

namespace App\Dashboard;

use App\Addresses\Check\AddressCheckRegistry;
use App\Contracts\Check\ContractCheckRegistry;
use App\Contracts\Check\UnsentProposalCheck;
use App\Contracts\Check\UnsignedProposalCheck;
use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Customers\Check\CustomerCheckRegistry;
use App\Dashboard\Card\AddressProblemsCard;
use App\Dashboard\Card\ContractProblemsCard;
use App\Dashboard\Card\ContractStatesCard;
use App\Dashboard\Card\CustomerProblemsCard;
use App\Dashboard\Card\DebtorsCard;
use App\Dashboard\Card\EndingObligationsCard;
use App\Dashboard\Card\LabelsCard;
use App\Dashboard\Card\ManualShutoffDebtorsCard;
use App\Dashboard\Card\UnsignedContractsCard;
use App\Model\Table\ContractsTable;
use App\Model\Table\ContractStatesTable;
use App\Model\Table\ContractVersionProposalsTable;
use App\Model\Table\ContractVersionsTable;
use App\Model\Table\LabelsTable;
use App\Model\Table\TasksTable;
use Cake\Core\Plugin;
use Cake\ORM\Locator\LocatorAwareTrait;
use Dashboard\Card\CardRegistryInterface;
use Dashboard\Card\DashboardCardInterface;
use Tasks\Dashboard\Card\MyTasksCard;
use Tasks\Dashboard\Card\PressingTasksCard;
use Tasks\Dashboard\Card\StaleTasksCard;
use Tasks\Dashboard\Card\UnassignedTasksCard;

/**
 * Registry of the cards the dashboard can draw.
 *
 * This is the single extension point for cards: register one here and it appears, in this
 * order, for the roles it names. Cards are built lazily, so registering one costs nothing
 * until it is actually drawn.
 */
final class DashboardCardRegistry implements CardRegistryInterface
{
    use LocatorAwareTrait;

    /**
     * @var array<string, callable(): \Dashboard\Card\DashboardCardInterface>
     */
    private array $factories = [];

    /**
     * @param string|null $role The role of the signed-in operator.
     * @param string|null $user_id The signed-in operator.
     */
    public function __construct(private ?string $role, ?string $user_id)
    {
        /** @var \App\Model\Table\TasksTable $tasks */
        $tasks = $this->fetchTable(TasksTable::class);

        $this->factories = [
            'pressing_tasks' => fn(): DashboardCardInterface => new PressingTasksCard($tasks),
            'my_tasks' => fn(): DashboardCardInterface => new MyTasksCard($tasks, $user_id),
            'unassigned_tasks' => fn(): DashboardCardInterface => new UnassignedTasksCard($tasks),
            'stale_tasks' => fn(): DashboardCardInterface => new StaleTasksCard($tasks),
        ];

        /** @var \App\Model\Table\LabelsTable $labels */
        $labels = $this->fetchTable(LabelsTable::class);
        /** @var \App\Model\Table\ContractVersionsTable $contract_versions */
        $contract_versions = $this->fetchTable(ContractVersionsTable::class);
        /** @var \App\Model\Table\ContractStatesTable $contract_states */
        $contract_states = $this->fetchTable(ContractStatesTable::class);

        $this->factories['labels'] = fn(): DashboardCardInterface => new LabelsCard($labels, $this->role);
        $this->factories['contract_states'] =
            fn(): DashboardCardInterface => new ContractStatesCard($contract_states, $this->role);

        // The three that report findings stand together, and in the order the customer's file
        // is read: where they live, who they are, what they hold. What is ending comes after
        // them, because it is work that is coming rather than work that is waiting.
        $this->factories['address_problems'] =
            fn(): DashboardCardInterface => new AddressProblemsCard(new AddressCheckRegistry());
        $this->factories['customer_problems'] =
            fn(): DashboardCardInterface => new CustomerProblemsCard(new CustomerCheckRegistry());
        $this->factories['contract_problems'] =
            fn(): DashboardCardInterface => new ContractProblemsCard(new ContractCheckRegistry());

        $this->factories['ending_obligations'] =
            fn(): DashboardCardInterface => new EndingObligationsCard($contract_versions);

        // Papers nobody has signed end in a disconnection like an unpaid invoice does, but
        // they are the contract office's work rather than the bookkeepers', and nothing
        // about them is asked of the accounting records - so this stands ahead of the
        // debtors, and stands whether or not the plugin is there.
        /** @var \App\Model\Table\ContractVersionProposalsTable $proposals */
        $proposals = $this->fetchTable(ContractVersionProposalsTable::class);

        $this->factories['unsigned_contracts'] =
            fn(): DashboardCardInterface => new UnsignedContractsCard(
                new UnsignedPaperwork($contract_versions),
                new UnsignedProposalCheck($proposals),
                new UnsentProposalCheck($proposals),
            );

        // The debtor cards read the accounting records, which only exist with the plugin.
        // They come last, so that what the whole office looks at stands ahead of what only
        // the bookkeepers do.
        if (Plugin::isLoaded('Bookkeeping')) {
            /** @var \App\Model\Table\ContractsTable $contracts */
            $contracts = $this->fetchTable(ContractsTable::class);

            $this->factories['debtors'] = fn(): DashboardCardInterface => new DebtorsCard();
            $this->factories['manual_shutoff_debtors'] =
                fn(): DashboardCardInterface => new ManualShutoffDebtorsCard($contracts);
        }
    }

    /**
     * The card registered under the given id, or null where there is none.
     *
     * @param string $id Registry key.
     * @return \Dashboard\Card\DashboardCardInterface|null
     */
    public function get(string $id): ?DashboardCardInterface
    {
        $factory = $this->factories[$id] ?? null;

        return $factory === null ? null : $factory();
    }

    /**
     * The card registered under the given id, but only where the signed-in role may see
     * it. Cards are fetched one URL at a time, so a card has to check who is asking.
     *
     * @param string $id Registry key.
     * @return \Dashboard\Card\DashboardCardInterface|null
     */
    public function getAllowed(string $id): ?DashboardCardInterface
    {
        $card = $this->get($id);

        return $card !== null && $this->isAllowed($card) ? $card : null;
    }

    /**
     * The cards the signed-in role is offered, in the order they are registered.
     *
     * @return list<\Dashboard\Card\DashboardCardInterface>
     */
    public function forRole(): array
    {
        $cards = [];
        foreach (array_keys($this->factories) as $id) {
            $card = $this->get($id);
            if ($card !== null && $this->isAllowed($card)) {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    /**
     * Whether the signed-in role is offered the given card. Administrators are offered
     * every card, as they are everywhere else.
     *
     * @param \Dashboard\Card\DashboardCardInterface $card The card to ask about.
     * @return bool
     */
    private function isAllowed(DashboardCardInterface $card): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        $roles = $card->roles();

        return $roles === [] || ($this->role !== null && in_array($this->role, $roles, true));
    }
}
