<?php
declare(strict_types=1);

namespace App\Contracts\Proposal;

use App\Model\Entity\ContractProposal;
use App\Service\ContractPrint\ContractDocuments;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * What a page needs to show the papers of one contract.
 *
 * Their own page and the page of the whole proposal ask the same question, so a new thing to show
 * is added here once.
 */
final class ContractProposalViewData
{
    use LocatorAwareTrait;

    /**
     * The view variables of the papers.
     *
     * @param \App\Model\Entity\ContractProposal $papers The papers of one contract.
     * @return array<string, mixed>
     */
    public function of(ContractProposal $papers): array
    {
        $changes = $papers->proposedChanges();
        $snapshot = $papers->stateOfThings();
        $asItStood = $snapshot->hydrate();
        $projection = new ProposalProjection();

        /** @var \App\Model\Table\ContractProposalsTable $table */
        $table = $this->fetchTable('ContractProposals');

        return [
            'confirmations' => $papers->confirmations(),
            'mayBeEdited' => $table->mayBeEdited($papers),
            'linesLock' => (new ContractDocuments())->linesLock($papers),
            // The same projection the documents print from, so the table and the paper cannot
            // disagree.
            'rows' => $projection->explain(
                $asItStood->billings,
                $changes,
                $papers->effective_from,
                $snapshot->servicesChosenBy($changes),
            ),
            'equipmentRows' => $projection->explainEquipment(
                $asItStood->borrowed_equipments ?? [],
                $changes,
                $snapshot->equipmentLentBy($changes),
            ),
            // Only what the papers ask for. The rest of what applying them would write is worked
            // out against the records as they stand, which means something in the moment before
            // it happens and nothing here.
            'planned' => array_values(array_filter(
                (new ChangePlan())->of($papers),
                fn(PlannedChange $one): bool => $one->asked,
            )),
        ];
    }
}
