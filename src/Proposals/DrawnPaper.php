<?php
declare(strict_types=1);

namespace App\Proposals;

use App\Contracts\Proposal\ProposalProjection;
use App\Documents\PrintedDocument;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\CustomerDocumentType;
use App\Model\Enum\DocumentVariant;
use App\Service\ContractPrint\ContractDocuments;
use App\Service\ContractPrint\ContractPrintData;
use App\Service\ContractPrint\ContractPrintValidator;
use App\Service\CustomerPrint\CustomerDocuments;
use App\Service\CustomerPrint\CustomerPrintData;
use App\Service\CustomerPrint\CustomerPrintValidator;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Closure;
use RuntimeException;

/**
 * One paper, drawn from the round it belongs to.
 *
 * What used to be a form - pick a round, pick a document, press print - is now a link beside the
 * paper that is missing, so the two agendas are asked the same question in the same place. The
 * printing services themselves are untouched; this only works out what to hand them.
 *
 * A paper already on file is handed back rather than drawn again, which is the services' own rule
 * and the reason a document printed a year ago comes back saying what it said then.
 */
final class DrawnPaper
{
    use LocatorAwareTrait;

    /**
     * What a contract's papers need loaded to be drawn.
     *
     * @var array<mixed>
     */
    private const FOR_PRINTING = [
        'Contracts' => [
            'Commissions',
            'ContractStates',
            'Customers' => ['Addresses', 'Emails', 'Phones', 'AccountingProfiles'],
            'InstallationAddresses',
            'InstallationTechnicians',
            'ServiceTypes',
            'UninstallationTechnicians',
        ],
    ];

    /**
     * The paper itself.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $round The round.
     * @param string $document_type Which paper.
     * @param bool $signed Whether it is the copy carrying our signature.
     * @return \App\Documents\PrintedDocument
     * @throws \RuntimeException When the round cannot produce that paper.
     */
    public function of(
        ContractProposal|CustomerProposal $round,
        string $document_type,
        bool $signed = false,
    ): PrintedDocument {
        $problems = $this->problemsWith($round, $document_type, $signed);

        if ($problems !== []) {
            throw new RuntimeException(implode(' ', $problems));
        }

        if ($round instanceof CustomerProposal) {
            return (new CustomerDocuments())->for($this->whatTheCustomersSays($round, $document_type));
        }

        return (new ContractDocuments())->for($this->whatTheContractsSays($round, $document_type, $signed));
    }

    /**
     * Why the paper cannot be drawn yet, where it cannot.
     *
     * Asked before the link is offered rather than after it is followed, so that a gap in the
     * records reads as what is missing instead of as a page that did nothing.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $round The round.
     * @param string $document_type Which paper.
     * @param bool $signed Whether it is the copy carrying our signature.
     * @return array<string> What stands in the way, in words.
     */
    public function problemsWith(
        ContractProposal|CustomerProposal $round,
        string $document_type,
        bool $signed = false,
    ): array {
        if ($round instanceof CustomerProposal) {
            $type = CustomerDocumentType::tryFrom($document_type);

            if ($type === null) {
                return [__('This document does not belong to this proposal.')];
            }

            $errors = (new CustomerPrintValidator())->validate(
                $this->whatTheCustomersSays($round, $document_type),
            );
        } else {
            // Asked first: there is no version to print from, and nothing of ours to print.
            if (!$round->keepsVersions()) {
                return [__('No documents are generated for a contract whose service type does'
                    . ' not use contract versions.')];
            }

            $type = ContractDocumentType::tryFrom($document_type);

            if ($type === null) {
                return [__('This document does not belong to this proposal.')];
            }

            $errors = (new ContractPrintValidator())->validate(
                $this->whatTheContractsSays($round, $document_type, $signed),
                $signed ? ['signed' => '1'] : [],
            );
        }

        return $this->inWords($errors);
    }

    /**
     * Every complaint, however deep it was filed.
     *
     * @param array<string, mixed> $errors What the validator said.
     * @return array<string>
     */
    private function inWords(array $errors): array
    {
        $said = [];

        foreach ($errors as $one) {
            $said = array_merge($said, is_array($one) ? $this->inWords($one) : [(string)$one]);
        }

        return $said;
    }

    /**
     * What a paper about the customer themselves is drawn from.
     *
     * @param \App\Model\Entity\CustomerProposal $round The round.
     * @param string $document_type Which paper.
     * @return \App\Service\CustomerPrint\CustomerPrintData
     */
    private function whatTheCustomersSays(
        CustomerProposal $round,
        string $document_type,
    ): CustomerPrintData {
        $type = CustomerDocumentType::from($document_type);

        $contain = [
            'AccountingProfiles',
            'Addresses' => ['Countries'],
            'Emails',
            'Phones',
        ];

        if ($type === CustomerDocumentType::ServicesOverview) {
            $contain['Contracts'] = $this->whatIsProvided(Date::now());
        }

        $customer = $this->fetchTable('Customers')->get($round->customer_id, contain: $contain);

        return new CustomerPrintData(
            type: $type,
            customer: $customer,
            proposal: $round,
        );
    }

    /**
     * The contracts a list of what is provided names, with what it says about each.
     *
     * Those whose services are provided or about to be, a notice period included. One that has
     * ended is not on the list even while it is still being billed for the last time. Billings
     * that ended before the day are history, and those still to start are listed as such.
     *
     * @param \Cake\I18n\Date $day The day the list is drawn up on.
     * @return \Closure(\Cake\ORM\Query\SelectQuery<\App\Model\Entity\Contract>): \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Contract>
     */
    private function whatIsProvided(Date $day): Closure
    {
        return static fn(SelectQuery $contracts): SelectQuery => $contracts
            ->contain([
                'ContractStates',
                'ServiceTypes',
                'InstallationAddresses',
                'ContractVersions',
                'Billings' => static fn(SelectQuery $billings): SelectQuery => $billings
                    ->contain(['Services'])
                    ->where([
                        'OR' => [
                            'Billings.billing_until IS' => null,
                            'Billings.billing_until >=' => $day,
                        ],
                    ])
                    ->orderBy(['Billings.billing_from' => 'ASC', 'Billings.id' => 'ASC']),
            ])
            ->where(['ContractStates.active_services' => true])
            ->orderBy(['Contracts.nid' => 'ASC']);
    }

    /**
     * What a paper about a contract is drawn from.
     *
     * The projection rather than the live records: what the paper says was settled when the
     * proposal was drawn up, and a version that is still to come has no record to read anyway.
     *
     * @param \App\Model\Entity\ContractProposal $round The proposal.
     * @param string $document_type Which paper.
     * @param bool $signed Whether it is the copy carrying our signature.
     * @return \App\Service\ContractPrint\ContractPrintData
     */
    private function whatTheContractsSays(
        ContractProposal $round,
        string $document_type,
        bool $signed,
    ): ContractPrintData {
        $proposal = $this->fetchTable('ContractProposals')->get($round->id, contain: self::FOR_PRINTING);

        $snapshot = $proposal->stateOfThings();
        $projection = new ProposalProjection();
        $changes = $proposal->proposedChanges();

        $asItStood = $snapshot->hydrate();
        $executed = $projection->projectVersion($snapshot->hydrateVersion(), $changes->version);

        // A proposal that replaces an earlier version names it; one that ends the contract ends the
        // version it belongs to, so that is the one the termination paper is about.
        $replaced = $snapshot->hydrateTerminatedVersion();
        $terminated = match (true) {
            $replaced !== null => $projection->projectTerminatedVersion(
                $replaced,
                $proposal->effective_from,
            ),
            $changes->endsTheContract() => $executed,
            default => null,
        };

        $data = new ContractPrintData(
            ContractDocumentType::from($document_type),
            $asItStood,
            $executed,
            $terminated,
        );
        $data->proposal = $proposal;
        $data->signed = $signed;
        $data->contractNumberToBeTerminated = $proposal->terminated_contract_number;
        $data->effectiveDateOfAmendment = $proposal->effective_from;
        $data->projectedBillings = $projection->projectBillings(
            $asItStood->billings,
            $changes,
            $proposal->effective_from,
            $snapshot->servicesChosenBy($changes),
        );

        return $data;
    }

    /**
     * Whether the paper may also be had with our signature on it.
     *
     * @param \App\Model\Entity\ContractProposal|\App\Model\Entity\CustomerProposal $round The round.
     * @param string $document_type Which paper.
     * @return bool
     */
    public function mayCarryOurSignature(
        ContractProposal|CustomerProposal $round,
        string $document_type,
    ): bool {
        if ($round instanceof CustomerProposal) {
            return false;
        }

        return ContractDocumentType::tryFrom($document_type)?->mayCarryOurSignature() ?? false;
    }

    /**
     * Which variant a paper is asked for as.
     *
     * @param bool $signed Whether it carries our signature.
     * @return \App\Model\Enum\DocumentVariant
     */
    public function variantFor(bool $signed): DocumentVariant
    {
        return DocumentVariant::forPrinting($signed);
    }
}
