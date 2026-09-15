<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Contracts\Proposal\ProposalDocumentTypes;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\ProposalStep;
use App\Service\ContractPrint\ContractDocuments;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * What one envelope of papers does as a whole.
 *
 * The papers a customer gets go out together and come back signed together, so the day they went
 * out and the day they came back is one day written on all of them. What is inside is drawn up one
 * at a time, each on the form for the papers it is - this only knows how to move the lot along.
 *
 * Which step is being taken has to be asked rather than assumed: a proposal of a contract stays
 * open until it is carried over, long after it was signed, so "what is still open" and "what is
 * still waiting for this" are not the same question.
 */
final class RoundOfPapers
{
    use LocatorAwareTrait;

    /**
     * Everything in one envelope: what it does for each contract, in the order the contracts read.
     *
     * @param string|null $envelope_id Which envelope.
     * @return array<\App\Model\Entity\ContractProposal>
     */
    public function partsOf(?string $envelope_id): array
    {
        if ($envelope_id === null) {
            return [];
        }

        /** @var array<\App\Model\Entity\ContractProposal> $parts */
        $parts = $this->fetchTable('ContractProposals')
            ->find()
            // What state a set of papers is in is read off the envelope, so it comes with them.
            ->contain(['Contracts', 'CustomerProposals'])
            ->where(['ContractProposals.customer_proposal_id' => $envelope_id])
            ->orderBy(['Contracts.number' => 'ASC'])
            ->toArray();

        return $parts;
    }

    /**
     * Every paper the whole package may have, by the record each hangs on.
     *
     * Wider than what was printed, because a scan may come back for a paper nobody drew here - one
     * printed before any of this was kept, or signed on a copy that came from somewhere else. Not
     * wider than what each of them may be: a termination filed against a new contract is a mistake,
     * not a document.
     *
     * @param \App\Model\Entity\CustomerProposal $envelope The envelope.
     * @return array<string, array<string, mixed>> By the id the papers hang on.
     */
    public function documentsAcross(CustomerProposal $envelope): array
    {
        $across = [];
        $its = [];

        foreach ($envelope->purpose?->documents() ?? [] as $document) {
            $its[$document->value] = $document->label();
        }

        if ($its !== []) {
            $across[(string)$envelope->id] = [
                'model' => CustomerDocuments::MODEL,
                'said' => $envelope->whatItIsFor(),
                'documents' => $its,
            ];
        }

        $allowed = new ProposalDocumentTypes();

        foreach ($this->partsOf((string)$envelope->id) as $part) {
            $theirs = $allowed->options($part);

            if ($theirs === []) {
                continue;
            }

            $across[(string)$part->id] = [
                'model' => ContractDocuments::MODEL,
                'said' => $part->getName(),
                'documents' => $theirs,
            ];
        }

        return $across;
    }

    /**
     * What in one envelope a step reaches: everything that goes through it and is not settled.
     *
     * Not only what has yet to take the step. Papers go out more than once - by another means, or
     * after the first attempt came back - and a signature may be corrected; the envelope moved
     * together the first time, so it moves together the second. What is settled is history and is
     * left where it is.
     *
     * @param string|null $envelope_id Which envelope.
     * @param \App\Model\Enum\ProposalStep $step The step being taken.
     * @return array<\App\Model\Entity\ContractProposal>
     */
    public function whateverTheStepReaches(?string $envelope_id, ProposalStep $step): array
    {
        if ($envelope_id === null) {
            return [];
        }

        $found = [];

        $papers = $this->fetchTable('ContractProposals')
            ->find()
            ->contain(['Contracts', 'CustomerProposals'])
            ->where(['ContractProposals.customer_proposal_id' => $envelope_id])
            ->orderBy(['Contracts.number' => 'ASC']);

        /** @var \App\Model\Entity\ContractProposal $one */
        foreach ($papers as $one) {
            if (!$one->hasBeenSettled() && $one->expects($step)) {
                $found[] = $one;
            }
        }

        return $found;
    }
}
