<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Model\Entity\CustomerProposal;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\DocumentVariant;
use App\Model\Enum\ProposalStep;
use App\Service\ContractPrint\ContractDocuments;
use App\Service\CustomerPrint\CustomerDocuments;
use Cake\I18n\DateTime;
use Files\Service\FileStorage;
use Throwable;

/**
 * Taking back a sending recorded before the papers reached the customer.
 *
 * Papers are printed ahead and written down as sent, and the equipment or the tariff can still
 * change before they are handed over. Only papers handed over in person can be stopped, and only
 * until something signed has come back.
 *
 * @phpstan-type Papers array{
 *     id: string,
 *     said: string,
 *     documents: list<string>,
 *     links: list<\Files\Model\Entity\FileLink>,
 *     received: int,
 *     signed: bool,
 * }
 */
final class TakingBackTheSending
{
    /**
     * Whether the proposal has a sending anybody could ask to take back.
     *
     * Wider than what may be done, so that somebody looking for the step is told why not.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return bool
     */
    public function isOnOffer(CustomerProposal $proposal): bool
    {
        return $proposal->isOpen() && $proposal->hasBeenSent() && !$proposal->hasBeenConcluded();
    }

    /**
     * Why the sending has to stand, or null where it may be taken back.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @param array<array<string, mixed>> $papers What is on file, as {@see papers()} reads it.
     * @return string|null
     * @phpstan-param list<Papers> $papers
     */
    public function whatStopsIt(CustomerProposal $proposal, array $papers): ?string
    {
        foreach ($papers as $record) {
            if ($record['signed']) {
                return __(
                    'The customer has already signed a document from this proposal, so the sending'
                    . ' stands. To agree on something else, revoke this proposal and draw up a new one.',
                );
            }
        }

        if ($proposal->delivery_type !== DocumentsDeliveryType::InPerson) {
            return __(
                'Only papers handed over in person can be taken back, because papers sent any other'
                . ' way may already be with the customer. To agree on something else, revoke this'
                . ' proposal and draw up a new one.',
            );
        }

        return null;
    }

    /**
     * What is on file against the proposal, one record at a time.
     *
     * A contract's papers that were given up on are left out: nothing here concerns them.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return array<array<string, mixed>>
     * @phpstan-return list<Papers>
     */
    public function papers(CustomerProposal $proposal): array
    {
        $papers = [];

        $own = new CustomerDocuments();
        $filed = $own->filedAgainst([$proposal])[(string)$proposal->id] ?? [];

        if ($filed !== []) {
            $papers[] = $this->read((string)$proposal->id, $proposal->whatItIsFor(), $filed, $own->documentLabels());
        }

        $parts = array_filter(
            (new WholeProposal())->partsOf((string)$proposal->id),
            fn($part): bool => !$part->hasBeenRevoked(),
        );

        $theirs = new ContractDocuments();
        $filed = $theirs->filedAgainst($parts);

        foreach ($parts as $part) {
            $papers[] = $this->read(
                (string)$part->id,
                $part->getName(),
                $filed[(string)$part->id] ?? [],
                $theirs->documentLabels(),
            );
        }

        return $papers;
    }

    /**
     * Removes the record of the sending, which unlocks the proposal.
     *
     * @param \App\Model\Entity\CustomerProposal $proposal The proposal.
     * @return bool
     */
    public function takeBack(CustomerProposal $proposal): bool
    {
        $proposal->set('sent_date', null);
        $proposal->set('delivery_type', null);

        return (new WholeProposal())->recordStep($proposal, ProposalStep::Delivered) !== false;
    }

    /**
     * Discards the documents we generated for the records that were ticked.
     *
     * Called after the sending is taken back. A file left behind is untidy rather than wrong, so it
     * is counted instead of undoing the rest.
     *
     * @param array<array<string, mixed>> $papers What is on file, as {@see papers()} reads it.
     * @param array<mixed> $ticked What the form said, by the id of the record.
     * @return array<string, int>
     * @phpstan-param list<Papers> $papers
     * @phpstan-return array{discarded: int, failed: int}
     */
    public function discard(array $papers, array $ticked): array
    {
        $storage = new FileStorage();
        $at = DateTime::now();
        $discarded = 0;
        $failed = 0;

        foreach ($papers as $record) {
            if (($ticked[$record['id']] ?? '0') !== '1') {
                continue;
            }

            foreach ($record['links'] as $link) {
                try {
                    $storage->unlink($link, $at);
                    $discarded++;
                } catch (Throwable) {
                    $failed++;
                }
            }
        }

        return ['discarded' => $discarded, 'failed' => $failed];
    }

    /**
     * Sorts what is on file against one record.
     *
     * @param string $id Which record.
     * @param string $said What it is called.
     * @param array<string, array<string, list<\Files\Model\Entity\FileLink>>> $filed By document, then by variant.
     * @param array<string, string> $labels What the documents are called.
     * @return array<string, mixed>
     * @phpstan-return Papers
     */
    private function read(string $id, string $said, array $filed, array $labels): array
    {
        $documents = [];
        $links = [];
        $received = 0;
        $signed = false;

        foreach ($filed as $document_type => $byVariant) {
            foreach ($byVariant as $variant => $found) {
                $hand = DocumentVariant::tryFrom((string)$variant);

                if ($hand?->isGeneratedByUs() ?? false) {
                    foreach ($found as $link) {
                        $documents[] = sprintf('%s (%s)', $labels[$document_type] ?? $document_type, $hand->label());
                        $links[] = $link;
                    }

                    continue;
                }

                $received += count($found);
                $signed = $signed || ($hand?->carriesTheCustomersSignature() ?? false);
            }
        }

        return [
            'id' => $id,
            'said' => $said,
            'documents' => $documents,
            'links' => $links,
            'received' => $received,
            'signed' => $signed,
        ];
    }
}
