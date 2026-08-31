<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\AbstractCheckRegistry;
use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Model\Table\BillingsTable;
use App\Model\Table\BorrowedEquipmentsTable;
use App\Model\Table\ContractsTable;
use App\Model\Table\ContractVersionsTable;

/**
 * Registry of the checks that can be run against the contracts on file.
 *
 * This is the single extension point: register a check here, give it a template beside the
 * others, and the dashboard card, the overview and the contract itself pick it up.
 *
 * @extends \App\Check\AbstractCheckRegistry<\App\Contracts\Check\ContractCheckInterface>
 */
final class ContractCheckRegistry extends AbstractCheckRegistry
{
    /**
     * Registered in the order they are listed: what is billed first, because that is where a
     * mistake costs money, then what was agreed, then the days the contract itself carries,
     * then what is missing from it.
     *
     * @param bool $ignore_inactive Whether the checks keep to what is running. Each applies
     *   it to its own subject - the contract for most of them, the finding itself where the
     *   contract is beside the point - so that the answer is about the record being reported.
     *   Off, the checks report the history as well, which is what putting the history
     *   straight needs and what daily work does not.
     * @param string|null $contract_id One contract to ask about, rather than the whole file.
     *   This is what lets a contract show its own findings.
     * @param string|null $customer_id One customer to ask about, rather than the whole file.
     *   This is what lets a customer show the findings on every contract they hold.
     */
    public function __construct(
        private bool $ignore_inactive = true,
        private ?string $contract_id = null,
        private ?string $customer_id = null,
    ) {
        /** @var \App\Model\Table\BillingsTable $billings */
        $billings = $this->fetchTable(BillingsTable::class);
        /** @var \App\Model\Table\ContractVersionsTable $versions */
        $versions = $this->fetchTable(ContractVersionsTable::class);
        /** @var \App\Model\Table\ContractsTable $contracts */
        $contracts = $this->fetchTable(ContractsTable::class);
        /** @var \App\Model\Table\BorrowedEquipmentsTable $equipments */
        $equipments = $this->fetchTable(BorrowedEquipmentsTable::class);

        $this->factories = [
            'billing_gap' =>
                fn(): ContractCheckInterface => new BillingGapCheck(
                    $billings,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'overlapping_billings' =>
                fn(): ContractCheckInterface => new OverlappingBillingsCheck(
                    $billings,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'impossible_billing_period' =>
                fn(): ContractCheckInterface => new ImpossibleBillingPeriodCheck(
                    $billings,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'overlapping_contract_versions' =>
                fn(): ContractCheckInterface => new OverlappingContractVersionsCheck(
                    $versions,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'impossible_contract_version_period' =>
                fn(): ContractCheckInterface => new ImpossibleContractVersionPeriodCheck(
                    $versions,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'unsettled_obligation' =>
                fn(): ContractCheckInterface => new UnsettledObligationCheck(
                    $versions,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'contract_version_gap' =>
                fn(): ContractCheckInterface => new ContractVersionGapCheck(
                    $versions,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'active_without_billing' =>
                fn(): ContractCheckInterface => new ActiveWithoutBillingCheck(
                    $contracts,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'inactive_with_billing' =>
                fn(): ContractCheckInterface => new InactiveWithBillingCheck(
                    $contracts,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'billing_service_type_mismatch' =>
                fn(): ContractCheckInterface => new BillingServiceTypeMismatchCheck(
                    $billings,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'non_standard_service' =>
                fn(): ContractCheckInterface => new NonStandardServiceCheck(
                    $billings,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'unsigned_contract' =>
                fn(): ContractCheckInterface => new UnsignedContractCheck(
                    $versions,
                    new UnsignedPaperwork($versions),
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'missing_installation_date' =>
                fn(): ContractCheckInterface => new MissingInstallationDateCheck(
                    $contracts,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'missing_access_point' =>
                fn(): ContractCheckInterface => new MissingAccessPointCheck(
                    $contracts,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'impossible_contract_dates' =>
                fn(): ContractCheckInterface => new ImpossibleContractDatesCheck(
                    $contracts,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
            'impossible_borrowed_period' =>
                fn(): ContractCheckInterface => new ImpossibleBorrowedPeriodCheck(
                    $equipments,
                    $this->ignore_inactive,
                    $this->contract_id,
                    $this->customer_id,
                ),
        ];
    }
}
