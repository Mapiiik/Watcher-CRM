<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Entity\ContractVersionProposal;
use App\Model\Enum\ContractPrintType;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\I18n\Date;

/**
 * Data Transfer Object for contract PDF printing.
 *
 * This object represents a fully validated and prepared dataset
 * required for generating a contract-related PDF document.
 *
 * It intentionally separates print-specific data from domain entities
 * to avoid mutating Contract or ContractVersion with temporary state.
 *
 * Lifecycle:
 *  - Created by Controller
 *  - Filled by ContractPrintValidator
 *  - Enriched by ContractPrintDataEnricher
 *  - Consumed by PDF generator
 */
final class ContractPrintData
{
    /**
     * Type of document being printed.
     */
    public ContractPrintType $type;

    /**
     * Contract being printed.
     */
    public Contract $contract;

    /**
     * Contract version to be executed used for printing.
     */
    public ?ContractVersion $contractVersionToBeExecuted = null;

    /**
     * Contract version to be terminated used for printing.
     */
    public ?ContractVersion $contractVersionToBeTerminated = null;

    /**
     * Effective date of the amendment.
     *
     * Used for:
     *  - contract-amendment
     */
    public ?Date $effectiveDateOfAmendment = null;

    /**
     * Technical details required for handover protocols.
     */
    public ?ContractPrintTechnicalData $technicalDetails = null;

    /**
     * Number of the contract to be terminated.
     *
     * Used for:
     *  - contract-termination
     *  - contract-new-x
     *  - handover-protocol-uninstallation
     */
    public ?string $contractNumberToBeTerminated = null;

    /**
     * Indicates whether the document should be generated as signed.
     */
    public bool $signed = false;

    /**
     * The proposal the document is printed from.
     *
     * Every document is printed from one: the snapshot it holds is what the pages are drawn from,
     * so that the same paper printed twice is the same paper.
     */
    public ?ContractVersionProposal $proposal = null;

    /**
     * The billings as the proposal would leave them, put together from its snapshot.
     *
     * @var array<\App\Model\Entity\Billing>|null
     */
    public ?array $projectedBillings = null;

    /**
     * Active billings applicable at the time of the contract version.
     *
     * Typically split later into:
     *  - individual billings
     *  - standard billings
     *
     * @var \Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\Billing>
     */
    public CollectionInterface $activeBillings;

    /**
     * Future billings starting after the contract version validity.
     *
     * @var \Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\Billing>
     */
    public CollectionInterface $futureBillings;

    /**
     * Constructor.
     */
    public function __construct(
        ContractPrintType $type,
        Contract $contract,
        ?ContractVersion $contractVersionToBeExecuted,
        ?ContractVersion $contractVersionToBeTerminated,
    ) {
        $this->type = $type;
        $this->contract = $contract;
        $this->contractVersionToBeExecuted = $contractVersionToBeExecuted;
        $this->contractVersionToBeTerminated = $contractVersionToBeTerminated;

        $this->activeBillings = new Collection([]);
        $this->futureBillings = new Collection([]);
    }

    /**
     * @return \Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\Billing>
     */
    public function getActiveStandardBillings(): CollectionInterface
    {
        return $this->activeBillings->filter(
            fn(Billing $billing): bool => $billing->price === null,
        );
    }

    /**
     * @return \Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\Billing>
     */
    public function getActiveIndividualBillings(): CollectionInterface
    {
        return $this->activeBillings->filter(
            fn(Billing $billing): bool => $billing->price !== null,
        );
    }

    /**
     * @return \Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\Billing>
     */
    public function getFutureStandardBillings(): CollectionInterface
    {
        return $this->futureBillings->filter(
            fn(Billing $billing): bool => $billing->price === null,
        );
    }

    /**
     * @return \Cake\Collection\CollectionInterface<array-key, \App\Model\Entity\Billing>
     */
    public function getFutureIndividualBillings(): CollectionInterface
    {
        return $this->futureBillings->filter(
            fn(Billing $billing): bool => $billing->price !== null,
        );
    }
}
