<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Contracts\Proposal\ChangeApplication;
use App\Contracts\Proposal\ChangePlan;
use App\Contracts\Proposal\ChangePreview;
use App\Contracts\Proposal\ProposalDocumentTypes;
use App\Model\Audit\AuditTrail;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\ProposalStep;
use App\Service\ContractPrint\ContractDocuments;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Exception;

/**
 * What one proposal does as a whole, rather than a paper at a time.
 *
 * The papers a customer gets go out together and come back signed together, so the day they went
 * out and the day they came back is one day written on all of them. What is inside is drawn up one
 * at a time, each on the form for the papers it is - this only knows how to move the lot along.
 *
 * Which step is being taken has to be asked rather than assumed: a proposal of a contract stays
 * open until it is applied, long after it was signed, so "what is still open" and "what is
 * still waiting for this" are not the same question.
 */
final class WholeProposal
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
     * What in one envelope is waiting to be applied, each part with what the page shows about it.
     *
     * Spelled out a contract at a time, because that is what each part asks about and what each one
     * would write - one merged list would say what is happening to nothing in particular.
     *
     * @param string $envelope_id Which envelope.
     * @param array<mixed> $handed_over_on The days the operator said equipment changed hands, by part.
     * @return array<array<string, mixed>>
     */
    public function dueToBeApplied(string $envelope_id, array $handed_over_on = []): array
    {
        $preview = new ChangePreview();
        $plan = new ChangePlan();
        $parts = [];

        foreach ($this->partsOf($envelope_id) as $papers) {
            if (!$papers->isDueFor(ProposalStep::Applied)) {
                continue;
            }

            $movesEquipment = $papers->proposedChanges()->equipments !== [];

            $parts[] = [
                'papers' => $papers,
                'found' => $preview->of($papers),
                'planned' => $plan->of($papers),
                'billingsNow' => $preview->billingsNow($papers),
                'billingsAfterwards' => $preview->billingsAfterwards($papers),
                'equipmentMoved' => $preview->equipmentMoved($papers),
                // Asked only where equipment changes hands, and said on the papers' own day
                // unless the operator knows it happened on another.
                'handedOverOn' => $movesEquipment
                    ? $this->parseDay($handed_over_on[(string)$papers->id] ?? null)
                        ?? ChangeApplication::handedOverOnByDefault($papers)
                    : null,
            ];
        }

        return $parts;
    }

    /**
     * Whether any part speaks of a billing or equipment that is no longer on the contract.
     *
     * @param array<array<string, mixed>> $parts What is to be applied.
     * @return bool
     */
    public function anythingIsGone(array $parts): bool
    {
        $preview = new ChangePreview();

        foreach ($parts as $part) {
            /** @var array<int, array{what: string, said: string}> $found */
            $found = $part['found'];

            if ($preview->anythingIsGone($found)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether anything across the package stands in the way of applying it.
     *
     * @param array<array<string, mixed>> $parts What is to be applied.
     * @param bool $leaving_out Whether a line whose billing has gone is passed over.
     * @return bool
     */
    public function anythingStops(array $parts, bool $leaving_out): bool
    {
        $preview = new ChangePreview();

        foreach ($parts as $part) {
            /** @var array<int, array{what: string, said: string}> $found */
            $found = $part['found'];

            if ($preview->anythingStopsIt($found, $leaving_out)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether any part says its equipment changed hands on a day still to come.
     *
     * @param array<array<string, mixed>> $parts What is to be applied.
     * @return bool
     */
    public function anyHandoverIsStillToCome(array $parts): bool
    {
        foreach ($parts as $part) {
            if ($part['handedOverOn'] instanceof Date && $part['handedOverOn']->greaterThan(Date::today())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Writes every part of the package, or none of it.
     *
     * A package half written into the records is the worst of both: nothing says which half, and
     * the papers it came from read as settled either way.
     *
     * @param array<array<string, mixed>> $parts What is to be applied.
     * @param string|null $by Who is applying it.
     * @param bool $reach_into_closed_periods Whether invoiced periods may be written into.
     * @param bool $go_below_minimum Whether any line may price the connection below the minimum.
     * @param bool $leave_out_what_is_gone Whether a line whose billing has gone is passed over and
     *   written down rather than stopping the whole package.
     * @param bool $catching_up Whether an exchange may be handed over before it was drawn up.
     * @return void
     * @throws \Exception When a part cannot be written, and then nothing is.
     */
    public function apply(
        array $parts,
        ?string $by,
        bool $reach_into_closed_periods = false,
        bool $go_below_minimum = false,
        bool $leave_out_what_is_gone = false,
        bool $catching_up = false,
    ): void {
        $proposals = $this->fetchTable('CustomerProposals');

        // Every part under one trail, written out after the transaction: audit-stash queues
        // what it logs and nothing inside a transaction ever reaches the log on its own.
        $trail = new AuditTrail();

        $proposals->getConnection()->transactional(
            function () use (
                $parts,
                $by,
                $reach_into_closed_periods,
                $go_below_minimum,
                $leave_out_what_is_gone,
                $trail,
                $catching_up,
            ): void {
                foreach ($parts as $part) {
                    (new ChangeApplication())->apply(
                        $part['papers'],
                        $by,
                        $reach_into_closed_periods,
                        $go_below_minimum,
                        $leave_out_what_is_gone,
                        $trail,
                        $part['handedOverOn'] ?? null,
                        $catching_up,
                    );
                }
            },
        );

        $trail->flush($proposals, $parts[0]['papers']);
    }

    /**
     * Writes a step onto the proposal, which is what everything in it reads.
     *
     * One place rather than one place per set of papers: the envelope went out in one piece and
     * came back in one, so the day is the envelope's and the papers inside it answer with it.
     *
     * A signed consent is also what the customer's own record has been waiting for. The flag used
     * to be ticked by hand, so a customer could read as having refused while their signed consent
     * sat on file - this is the same thing the contract's side does when papers are applied.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @param \App\Model\Enum\ProposalStep $step Which step is being taken.
     * @return bool
     */
    public function recordStep(CustomerProposal $proposal, ProposalStep $step): bool
    {
        /** @var \App\Model\Table\CustomerProposalsTable $proposals */
        $proposals = $this->fetchTable('CustomerProposals');
        $trail = new AuditTrail();

        $recorded = (bool)$proposals->getConnection()->transactional(
            function () use ($proposal, $proposals, $step, $trail): bool {
                if (!$proposals->save($proposal, $trail->options())) {
                    return false;
                }

                if ($step === ProposalStep::Signed && $proposal->purpose === CustomerProposalPurpose::GdprConsent) {
                    $customers = $proposals->Customers;
                    $customer = $customers->get($proposal->customer_id);

                    if ($customer->agree_gdpr !== true) {
                        $customers->saveOrFail(
                            $customers->patchEntity($customer, ['agree_gdpr' => true]),
                            $trail->options(),
                        );
                    }
                }

                return true;
            },
        );

        if ($recorded) {
            $trail->flush($proposals, $proposal);
        }

        return $recorded;
    }

    /**
     * A day the operator typed, where it is one.
     *
     * @param mixed $said What they typed.
     * @return \Cake\I18n\Date|null
     */
    private function parseDay(mixed $said): ?Date
    {
        if (!is_string($said) || trim($said) === '') {
            return null;
        }

        try {
            return new Date(trim($said));
        } catch (Exception) {
            return null;
        }
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
