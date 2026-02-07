<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Model\Entity\Billing;
use App\Model\Entity\Contract;
use App\Model\Entity\ContractVersion;
use App\Model\Enum\ContractPrintType;
use Cake\Collection\Collection;
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
 *  - Filled by ContractValidator / ContractPrintValidator
 *  - Enriched by ContractPrintService
 *  - Consumed by PDF generator
 */
final class ContractPrintData
{
    /**
     * Type of document being printed.
     *
     * @var \App\Model\Enum\ContractPrintType
     */
    public ContractPrintType $type;

    /**
     * Contract being printed.
     *
     * @var \App\Model\Entity\Contract
     */
    public Contract $contract;

    /**
     * Contract version used for printing.
     *
     * @var \App\Model\Entity\ContractVersion|null
     */
    public ?ContractVersion $contractVersion = null;

    /**
     * Contract version to be replaced used for printing.
     *
     * @var \App\Model\Entity\ContractVersion|null
     */
    public ?ContractVersion $contractVersionToBeReplaced = null;

    /**
     * Effective date of the amendment.
     *
     * Used for:
     *  - contract-amendment
     *
     * @var \Cake\I18n\Date|null
     */
    public ?Date $effectiveDateOfAmendment = null;

    /**
     * Technical details required for handover protocols.
     *
     * @var \App\Service\ContractPrint\ContractPrintTechnicalData|null
     */
    public ?ContractPrintTechnicalData $technicalDetails = null;

    /**
     * Number of the contract to be terminated.
     *
     * Used for:
     *  - contract-termination
     *  - contract-new-x
     *  - handover-protocol-uninstallation
     *
     * @var string|null
     */
    public ?string $numberOfContractToBeTerminated = null;

    /**
     * Indicates whether the document should be generated as signed.
     *
     * @var bool
     */
    public bool $signed = false;

    /**
     * Active billings applicable at the time of the contract version.
     *
     * Typically split later into:
     *  - individual billings
     *  - standard billings
     *
     * @var \Cake\Collection\Collection<array-key, \App\Model\Entity\Billing>
     */
    public Collection $activeBillings;

    /**
     * Future billings starting after the contract version validity.
     *
     * @var \Cake\Collection\Collection<array-key, \App\Model\Entity\Billing>
     */
    public Collection $futureBillings;

    /**
     * Constructor.
     *
     * @param \App\Model\Enum\ContractPrintType $type
     * @param \App\Model\Entity\Contract $contract
     * @param \App\Model\Entity\ContractVersion|null $contractVersion
     * @param \App\Model\Entity\ContractVersion|null $contractVersionToBeReplaced
     */
    public function __construct(
        ContractPrintType $type,
        Contract $contract,
        ?ContractVersion $contractVersion,
        ?ContractVersion $contractVersionToBeReplaced,
    ) {
        $this->type = $type;
        $this->contract = $contract;
        $this->contractVersion = $contractVersion;
        $this->contractVersionToBeReplaced = $contractVersionToBeReplaced;

        $this->activeBillings = new Collection([]);
        $this->futureBillings = new Collection([]);
    }

    /**
     * @return \Cake\Collection\Collection<array-key, \App\Model\Entity\Billing>
     */
    public function getActiveStandardBillings(): Collection
    {
        return $this->activeBillings->filter(
            fn(Billing $billing) => !$billing->__isset('price'),
        );
    }

    /**
     * @return \Cake\Collection\Collection<array-key, \App\Model\Entity\Billing>
     */
    public function getActiveIndividualBillings(): Collection
    {
        return $this->activeBillings->filter(
            fn(Billing $billing) => $billing->__isset('price'),
        );
    }

    /**
     * @return \Cake\Collection\Collection<array-key, \App\Model\Entity\Billing>
     */
    public function getFutureStandardBillings(): Collection
    {
        return $this->futureBillings->filter(
            fn(Billing $billing) => !$billing->__isset('price'),
        );
    }

    /**
     * @return \Cake\Collection\Collection<array-key, \App\Model\Entity\Billing>
     */
    public function getFutureIndividualBillings(): Collection
    {
        return $this->futureBillings->filter(
            fn(Billing $billing) => $billing->__isset('price'),
        );
    }    
}
