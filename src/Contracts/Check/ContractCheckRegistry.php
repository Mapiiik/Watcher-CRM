<?php
declare(strict_types=1);

namespace App\Contracts\Check;

use App\Check\AbstractCheckRegistry;
use App\Check\CheckScope;
use App\Contracts\Unsigned\UnsignedPaperwork;
use App\Model\Table\BillingsTable;
use App\Model\Table\BorrowedEquipmentsTable;
use App\Model\Table\ContractProposalsTable;
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
        bool $ignore_inactive = true,
        ?string $contract_id = null,
        ?string $customer_id = null,
    ) {
        $this->scope = new CheckScope($ignore_inactive, $contract_id, $customer_id);

        /** @var \App\Model\Table\BillingsTable $billings */
        $billings = $this->fetchTable(BillingsTable::class);
        /** @var \App\Model\Table\ContractVersionsTable $versions */
        $versions = $this->fetchTable(ContractVersionsTable::class);
        /** @var \App\Model\Table\ContractProposalsTable $proposals */
        $proposals = $this->fetchTable(ContractProposalsTable::class);
        /** @var \App\Model\Table\ContractsTable $contracts */
        $contracts = $this->fetchTable(ContractsTable::class);
        /** @var \App\Model\Table\BorrowedEquipmentsTable $equipments */
        $equipments = $this->fetchTable(BorrowedEquipmentsTable::class);

        $this->factories = [
            'billing_gap' => fn() => new BillingGapCheck($billings, $this->scope),
            'overlapping_billings' => fn() => new OverlappingBillingsCheck($billings, $this->scope),
            'impossible_billing_period' => fn() => new ImpossibleBillingPeriodCheck($billings, $this->scope),
            'overlapping_contract_versions' => fn() => new OverlappingContractVersionsCheck($versions, $this->scope),
            'impossible_contract_version_period' =>
                fn() => new ImpossibleContractVersionPeriodCheck($versions, $this->scope),
            'unsettled_obligation' => fn() => new UnsettledObligationCheck($versions, $this->scope),
            'contract_version_gap' => fn() => new ContractVersionGapCheck($versions, $this->scope),
            'active_without_billing' => fn() => new ActiveWithoutBillingCheck($contracts, $this->scope),
            'inactive_with_billing' => fn() => new InactiveWithBillingCheck($contracts, $this->scope),
            'billing_service_type_mismatch' => fn() => new BillingServiceTypeMismatchCheck($billings, $this->scope),
            'non_standard_service' => fn() => new NonStandardServiceCheck($billings, $this->scope),
            'partly_applied_contract_proposal' =>
                fn() => new PartlyAppliedContractProposalCheck($proposals, $this->scope),
            'unapplied_contract_proposal' => fn() => new UnappliedContractProposalCheck($proposals, $this->scope),
            'unsigned_contract_proposal' => fn() => new UnsignedContractProposalCheck($proposals, $this->scope),
            'unsent_contract_proposal' => fn() => new UnsentContractProposalCheck($proposals, $this->scope),
            'unfiled_contract_proposal' => fn() => new UnfiledContractProposalCheck($proposals, $this->scope),
            'unsigned_contract_version' =>
                fn() => new UnsignedContractVersionCheck($versions, new UnsignedPaperwork($versions), $this->scope),
            'missing_installation_date' => fn() => new MissingInstallationDateCheck($contracts, $this->scope),
            'missing_access_point' => fn() => new MissingAccessPointCheck($contracts, $this->scope),
            'impossible_contract_dates' => fn() => new ImpossibleContractDatesCheck($contracts, $this->scope),
            'impossible_borrowed_period' => fn() => new ImpossibleBorrowedPeriodCheck($equipments, $this->scope),
        ];
    }
}
