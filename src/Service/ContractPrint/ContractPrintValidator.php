<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Contracts\Proposal\ProposalDocumentTypes;
use App\Model\Entity\ContractVersionProposal;

/**
 * Validator for contract print requests.
 *
 * There is very little left to ask here, and that is the point. What this used to check has moved
 * to where it belongs: whether the contract is ready for papers at all is now asked once when the
 * proposal is drawn up and the answer kept, and what a proposal may say about ending things is a
 * rule of the proposals table. A reprint therefore asks nothing - which is what lets last year's
 * paper be printed again after the equipment on it has been handed back.
 *
 * Two things remain. There has to be a proposal, because a document without one would be assembled
 * from whatever the records happen to say today. And the document has to be one that proposal can
 * be printed as, so that the offered list cannot be stepped around by typing a URL.
 *
 * This validator:
 *  - does NOT perform redirects
 *  - does NOT use Flash messages
 *  - does NOT validate general contract consistency
 *
 * Validation errors are collected per field.
 */
final class ContractPrintValidator
{
    /**
     * Collected validation errors.
     *
     * Errors are grouped by field name and may contain
     * multiple messages per field.
     *
     * @var array<string, array<string>>
     */
    private array $errors = [];

    /**
     * Adds a validation error message for the given field.
     *
     * Multiple messages may be added for the same field.
     *
     * @return void
     */
    private function setError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * Validates print data.
     *
     * @return array<string, array<string>>
     */
    public function validate(
        ContractPrintData $data,
        array $query,
    ): array {
        $this->errors = [];

        $data->signed = !empty($query['signed']);

        if (!$data->type->requiresProposal()) {
            return $this->errors;
        }

        if (!$data->proposal instanceof ContractVersionProposal) {
            $this->setError(
                'proposal_id',
                __('Please choose the proposal these papers are for, or draw one up.'),
            );

            return $this->errors;
        }

        if (!$this->documentSuitsTheProposal($data)) {
            $this->setError(
                'document_type',
                __('This proposal cannot be printed as that document.'),
            );
        }

        return $this->errors;
    }

    /**
     * Whether the chosen document is one the proposal may be printed as.
     *
     * @return bool
     */
    private function documentSuitsTheProposal(ContractPrintData $data): bool
    {
        /** @var \App\Model\Entity\ContractVersionProposal $proposal */
        $proposal = $data->proposal;

        return (new ProposalDocumentTypes())->allows(
            $data->type,
            $proposal,
            (bool)($data->contract->service_type->have_equipments ?? false),
            $data->contractVersionToBeExecuted?->conclusion_date !== null,
        );
    }
}
