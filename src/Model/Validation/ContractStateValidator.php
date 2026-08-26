<?php
declare(strict_types=1);

namespace App\Model\Validation;

use App\Model\Entity\Contract;
use App\Model\Entity\ServiceType;
use App\Model\Table\BillingsTable;
use App\Model\Table\BorrowedEquipmentsTable;
use App\Model\Table\ContractStatesTable;
use App\Model\Table\ContractVersionsTable;
use App\Model\Table\IpAddressesTable;
use App\Model\Table\IpNetworksTable;
use App\Model\Table\ServiceTypesTable;
use App\Model\Table\TasksTable;
use App\Model\Table\TaskTypesTable;
use Cake\I18n\Date;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Radius\Model\Table\AccountsTable;

/**
 * Contract state transition validator.
 *
 * Validates whether a contract can be transitioned to its target state
 * based on validation flags defined on the contract state.
 *
 * Each validation flag represents a mandatory condition that must be
 * satisfied before the state change is allowed (e.g. no open tasks,
 * required dates present, no active billings).
 *
 * This validator is executed during contract persistence (typically from
 * ContractsTable::beforeSave) and blocks saving when any required condition
 * is not met by attaching validation errors to the contract entity.
 *
 * The validation logic is intentionally explicit and flag-driven to ensure
 * clarity, maintainability, and predictable behavior when introducing new
 * contract state requirements.
 */
class ContractStateValidator
{
    use LocatorAwareTrait;

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
     * The service type of the contract being validated, once it has been asked for.
     *
     * @var \App\Model\Entity\ServiceType|null
     */
    private ?ServiceType $serviceType = null;

    /**
     * Adds a validation error message for the given field.
     *
     * Multiple messages may be added for the same field.
     *
     * @param string $field Field name
     * @param string $message Error message
     * @return void
     */
    private function setError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * The service type of the contract, read from the entity when it is there.
     *
     * @return \App\Model\Entity\ServiceType
     */
    private function serviceType(Contract $contract): ServiceType
    {
        return $this->serviceType ??= $contract->service_type
            ?? $this->fetchTable(ServiceTypesTable::class)->get($contract->service_type_id);
    }

    /**
     * Whether any of the contract's records in the given table has no end date,
     * and so reaches on without one.
     *
     * @param \Cake\ORM\Table $table Table holding the records.
     * @param string $contractId Contract the records belong to.
     * @param string $column Column carrying the end date.
     * @return bool
     */
    private function hasOpenEnd(Table $table, string $contractId, string $column): bool
    {
        return $table->find()
            ->where([
                'contract_id' => $contractId,
                $column . ' IS' => null,
            ])
            ->limit(1)
            ->count() > 0;
    }

    /**
     * The furthest end date the contract's records in the given table reach to.
     *
     * The last row is taken rather than `MAX()`, which Postgres answers with a string.
     *
     * @param \Cake\ORM\Table $table Table holding the records.
     * @param string $contractId Contract the records belong to.
     * @param string $column Column carrying the end date.
     * @return \Cake\I18n\Date|null
     */
    private function findLastEnd(Table $table, string $contractId, string $column): ?Date
    {
        $last = $table->find()
            ->where([
                'contract_id' => $contractId,
                $column . ' IS NOT' => null,
            ])
            ->orderBy([$column => 'DESC'])
            ->first();

        return $last?->get($column);
    }

    /**
     * Validates contract state transition requirements.
     *
     * Ensures that all validation flags defined on the target contract state
     * are satisfied before allowing the contract state change.
     *
     * Validation is context-aware and respects service type capabilities.
     * For service types that do not support contract versions (e.g. reseller
     * services), all contract version–related validations are intentionally
     * skipped to avoid enforcing impossible or irrelevant requirements.
     *
     * This method is intended to be executed during contract persistence
     * (e.g. in ContractsTable::beforeSave) and blocks saving when any
     * required condition is not met by attaching validation errors
     * to the contract entity.
     *
     * @return array<string, array<string>>
     */
    public function validate(Contract $contract): array
    {
        // Load contract state if not already present
        $contractState = $contract->contract_state
            ?? $this->fetchTable(ContractStatesTable::class)->get($contract->contract_state_id);

        $this->errors = [];
        $this->serviceType = null;

        if (!$contractState) {
            // No target contract state available, nothing to validate
            return [];
        }

        // Validations for new contracts
        if ($contract->isNew()) {
            if (!$contractState->usable_for_new_contract) {
                $this->setError(
                    'contract_state_id',
                    __('The selected contract state cannot be used for new contracts.'),
                );
            }
            // Skip further validations when creating a new contract for now
            return $this->errors;
        }

        // Dates
        if ($contractState->requires_installation_date) {
            $this->validateRequiresInstallationDate($contract);
        }

        if ($contractState->requires_uninstallation_date) {
            $this->validateRequiresUninstallationDate($contract);
        }

        if ($contractState->requires_termination_date) {
            $this->validateRequiresTerminationDate($contract);
        }

        // The end dates the contract carries must agree with how far its records reach. Asked
        // when the state changes and when a date itself moves, so that a record kept from before
        // does not stand in the way of an unrelated edit.
        $changingState = $contract->isDirty('contract_state_id');
        $changingTermination = $changingState || $contract->isDirty('termination_date');

        if ($contractState->requires_billings_matching_termination && $changingTermination) {
            $this->validateTerminationDateMatchingBillings($contract);
        }

        if (
            $contractState->requires_versions_matching_termination
            && $changingTermination
            && $this->serviceType($contract)->have_contract_versions
        ) {
            $this->validateTerminationDateMatchingContractVersions($contract);
        }

        if (
            $contractState->requires_equipments_matching_uninstallation
            && ($changingState || $contract->isDirty('uninstallation_date'))
        ) {
            $this->validateUninstallationDateMatchingBorrowedEquipments($contract);
        }

        // Skip further validations if contract state is not being changed
        if (!$changingState) {
            // No contract state change, nothing more to validate for now
            return $this->errors;
        }

        // Tasks
        if ($contractState->requires_open_task_type_id) {
            $this->validateRequiresOpenTaskType(
                $contract,
                $contractState->requires_open_task_type_id,
            );
        }

        if ($contractState->requires_no_open_tasks) {
            $this->validateRequiresNoOpenTasks($contract);
        }

        // Billings
        if ($contractState->requires_no_active_billings) {
            $this->validateRequiresNoActiveBillings($contract);
        }

        if ($contractState->requires_no_future_billings) {
            $this->validateRequiresNoFutureBillings($contract);
        }

        // Network
        if ($contractState->requires_no_assigned_ip_addresses_or_networks) {
            $this->validateRequiresNoAssignedIpAddressesOrNetworks($contract);
        }

        if ($contractState->requires_no_active_radius_accounts) {
            $this->validateRequiresNoActiveRadiusAccounts($contract);
        }

        // Hardware
        if ($contractState->requires_no_borrowed_equipments) {
            $this->validateRequiresNoBorrowedEquipments($contract);
        }

        // Contract versions
        if ($this->serviceType($contract)->have_contract_versions) {
            // Contract version validations are skipped for service types
            // that do not support contract versions (e.g. reseller services),
            // to avoid enforcing impossible or irrelevant requirements.

            if ($contractState->requires_contract_version) {
                $this->validateRequiresContractVersion($contract);
            }

            if ($contractState->requires_active_contract_version) {
                $this->validateRequiresActiveContractVersion($contract);
            }

            if ($contractState->requires_active_or_future_contract_version) {
                $this->validateRequiresActiveOrFutureContractVersion($contract);
            }

            if ($contractState->requires_no_active_or_future_contract_versions) {
                $this->validateRequiresNoActiveOrFutureContractVersions($contract);
            }

            if ($contractState->requires_no_active_obligations) {
                $this->validateRequiresNoActiveObligations($contract);
            }
        }

        return $this->errors;
    }

    /**
     * Validates presence of installation date.
     *
     * Ensures that the contract has an installation date set when required
     * by the target contract state.
     *
     * @return void
     */
    private function validateRequiresInstallationDate(Contract $contract): void
    {
        if ($contract->installation_date === null) {
            $this->setError(
                'installation_date',
                __('Installation date must be set for this contract state.'),
            );
        }
    }

    /**
     * Validates presence of uninstallation date.
     *
     * Ensures that the contract has an uninstallation date set when required
     * by the target contract state.
     *
     * @return void
     */
    private function validateRequiresUninstallationDate(Contract $contract): void
    {
        if ($contract->uninstallation_date === null) {
            $this->setError(
                'uninstallation_date',
                __('Uninstallation date must be set for this contract state.'),
            );
        }
    }

    /**
     * Validates presence of termination date.
     *
     * Ensures that the contract has a termination date set when required
     * by the target contract state.
     *
     * @return void
     */
    private function validateRequiresTerminationDate(Contract $contract): void
    {
        if ($contract->termination_date === null) {
            $this->setError(
                'termination_date',
                __('Termination date must be set for this contract state.'),
            );
        }
    }

    /**
     * Validates that the billings end with the services.
     *
     * Ensures that no billing reaches past the termination date and that the
     * last one ends on it.
     *
     * @return void
     */
    private function validateTerminationDateMatchingBillings(Contract $contract): void
    {
        if ($contract->termination_date === null) {
            // a missing date is what requires_termination_date reports
            return;
        }

        $billingsTable = $this->fetchTable(BillingsTable::class);

        if ($this->hasOpenEnd($billingsTable, $contract->id, 'billing_until')) {
            $this->setError(
                'termination_date',
                __('A billing has no end date, so it reaches past the termination of services.'),
            );

            return;
        }

        $last = $this->findLastEnd($billingsTable, $contract->id, 'billing_until');

        // a contract without billings is for the flags about their presence to report
        if ($last !== null && !$last->equals($contract->termination_date)) {
            $this->setError(
                'termination_date',
                __(
                    'The last billing runs until {0}, but the services are terminated on {1}.',
                    $last,
                    $contract->termination_date,
                ),
            );
        }
    }

    /**
     * Validates that the contract versions end with the services.
     *
     * Ensures that no contract version reaches past the termination date and
     * that the last one ends on it.
     *
     * @return void
     */
    private function validateTerminationDateMatchingContractVersions(Contract $contract): void
    {
        if ($contract->termination_date === null) {
            // a missing date is what requires_termination_date reports
            return;
        }

        $contractVersionsTable = $this->fetchTable(ContractVersionsTable::class);

        if ($this->hasOpenEnd($contractVersionsTable, $contract->id, 'valid_until')) {
            $this->setError(
                'termination_date',
                __('A contract version has no end date, so it reaches past the termination of services.'),
            );

            return;
        }

        $last = $this->findLastEnd($contractVersionsTable, $contract->id, 'valid_until');

        // a contract without versions is for the flags about their presence to report
        if ($last !== null && !$last->equals($contract->termination_date)) {
            $this->setError(
                'termination_date',
                __(
                    'The last contract version is valid until {0}, but the services are terminated on {1}.',
                    $last,
                    $contract->termination_date,
                ),
            );
        }
    }

    /**
     * Validates that the borrowed equipment comes back with the uninstallation.
     *
     * Ensures that no borrowed equipment is lent past the uninstallation date
     * and that the last one is returned on it.
     *
     * @return void
     */
    private function validateUninstallationDateMatchingBorrowedEquipments(Contract $contract): void
    {
        if ($contract->uninstallation_date === null) {
            // a missing date is what requires_uninstallation_date reports
            return;
        }

        $borrowedEquipmentsTable = $this->fetchTable(BorrowedEquipmentsTable::class);

        if ($this->hasOpenEnd($borrowedEquipmentsTable, $contract->id, 'borrowed_until')) {
            $this->setError(
                'uninstallation_date',
                __('A borrowed equipment has no return date, so it reaches past the uninstallation.'),
            );

            return;
        }

        $last = $this->findLastEnd($borrowedEquipmentsTable, $contract->id, 'borrowed_until');

        // a contract without borrowed equipment has nothing to disagree with
        if ($last !== null && !$last->equals($contract->uninstallation_date)) {
            $this->setError(
                'uninstallation_date',
                __(
                    'The last borrowed equipment is lent until {0}, but the uninstallation is on {1}.',
                    $last,
                    $contract->uninstallation_date,
                ),
            );
        }
    }

    /**
     * Validates presence of required open task type.
     *
     * Ensures that the contract has at least one task
     * of the required type in a non-completed state.
     *
     * @return void
     */
    private function validateRequiresOpenTaskType(
        Contract $contract,
        string $taskTypeId,
    ): void {
        $tasksTable = $this->fetchTable(TasksTable::class);
        $taskTypesTable = $this->fetchTable(TaskTypesTable::class);

        $taskType = $taskTypesTable->get($taskTypeId); // Ensure task type exists

        $exists = $tasksTable->find()
            ->innerJoinWith('TaskStates', function (SelectQuery $q) {
                return $q->where(['TaskStates.completed' => false]);
            })
            ->where([
                'Tasks.contract_id' => $contract->id,
                'Tasks.task_type_id' => $taskType->id,
            ])
            ->limit(1)
            ->count() > 0;

        if (!$exists) {
            $this->setError(
                'contract_state_id',
                __(
                    'An open task of the required type ({0}) must exist before changing to this contract state.',
                    $taskType->name ?? $taskType->id,
                ),
            );
        }
    }

    /**
     * Validates absence of open tasks.
     *
     * Ensures that the contract has no tasks
     * in a non-completed state when required
     * by the target contract state.
     *
     * @return void
     */
    private function validateRequiresNoOpenTasks(Contract $contract): void
    {
        $tasksTable = $this->fetchTable(TasksTable::class);

        $exists = $tasksTable->find()
            ->innerJoinWith('TaskStates', function (SelectQuery $q) {
                return $q->where(['TaskStates.completed' => false]);
            })
            ->where([
                'Tasks.contract_id' => $contract->id,
            ])
            ->limit(1)
            ->count() > 0;

        if ($exists) {
            $this->setError(
                'contract_state_id',
                __('All tasks must be completed before changing to this contract state.'),
            );
        }
    }

    /**
     * Validates absence of active billings.
     *
     * Ensures that the contract has no currently active billing records.
     * A billing is considered active when its billing period includes today.
     *
     * @return void
     */
    private function validateRequiresNoActiveBillings(Contract $contract): void
    {
        $billingsTable = $this->fetchTable(BillingsTable::class);
        $today = Date::now();

        $exists = $billingsTable->find()
            ->where([
                'contract_id' => $contract->id,
                'billing_from <=' => $today,
                'OR' => [
                    'billing_until IS' => null,
                    'billing_until >=' => $today,
                ],
            ])
            ->limit(1)
            ->count() > 0;

        if ($exists) {
            $this->setError(
                'contract_state_id',
                __('Active billings must be finished before changing to this contract state.'),
            );
        }
    }

    /**
     * Validates absence of future billings.
     *
     * Ensures that the contract has no billing records
     * scheduled to start in the future.
     *
     * @return void
     */
    private function validateRequiresNoFutureBillings(Contract $contract): void
    {
        $billingsTable = $this->fetchTable(BillingsTable::class);
        $today = Date::now();

        $exists = $billingsTable->find()
            ->where([
                'contract_id' => $contract->id,
                'billing_from >' => $today,
            ])
            ->limit(1)
            ->count() > 0;

        if ($exists) {
            $this->setError(
                'contract_state_id',
                __('Future billings must be removed before changing to this contract state.'),
            );
        }
    }

    /**
     * Validates absence of assigned IP addresses or networks.
     *
     * Ensures that the contract has no IP addresses or IP networks
     * currently assigned to it.
     *
     * @return void
     */
    private function validateRequiresNoAssignedIpAddressesOrNetworks(Contract $contract): void
    {
        $ipAddressesTable = $this->fetchTable(IpAddressesTable::class);
        $ipNetworksTable = $this->fetchTable(IpNetworksTable::class);

        $hasIpAddresses = $ipAddressesTable->find()
            ->where([
                'contract_id' => $contract->id,
            ])
            ->limit(1)
            ->count() > 0;

        $hasIpNetworks = $ipNetworksTable->find()
            ->where([
                'contract_id' => $contract->id,
            ])
            ->limit(1)
            ->count() > 0;

        if ($hasIpAddresses || $hasIpNetworks) {
            $this->setError(
                'contract_state_id',
                __('All assigned IP addresses and networks must be removed before changing to this contract state.'),
            );
        }
    }

    /**
     * Validates absence of active RADIUS accounts.
     *
     * Ensures that the contract has no active RADIUS accounts
     * associated with it.
     *
     * @return void
     */
    private function validateRequiresNoActiveRadiusAccounts(Contract $contract): void
    {
        $radiusAccountsTable = $this->fetchTable(AccountsTable::class);

        $exists = $radiusAccountsTable->find()
            ->where([
                'contract_id' => $contract->id,
                'active' => true,
            ])
            ->limit(1)
            ->count() > 0;

        if ($exists) {
            $this->setError(
                'contract_state_id',
                __('Active RADIUS accounts must be disabled before changing to this contract state.'),
            );
        }
    }

    /**
     * Validates absence of borrowed equipments.
     *
     * Ensures that the contract has no equipments currently borrowed
     * and not yet returned.
     *
     * @return void
     */
    private function validateRequiresNoBorrowedEquipments(Contract $contract): void
    {
        $borrowedEquipmentsTable = $this->fetchTable(BorrowedEquipmentsTable::class);

        $exists = $borrowedEquipmentsTable->find()
            ->where([
                'contract_id' => $contract->id,
                'borrowed_until IS' => null,
            ])
            ->limit(1)
            ->count() > 0;

        if ($exists) {
            $this->setError(
                'contract_state_id',
                __('All borrowed equipment must be returned before changing to this contract state.'),
            );
        }
    }

    /**
     * Validates presence of at least one contract version.
     *
     * Ensures that the contract has at least one version defined.
     *
     * @return void
     */
    private function validateRequiresContractVersion(Contract $contract): void
    {
        $contractVersionsTable = $this->fetchTable(ContractVersionsTable::class);

        $exists = $contractVersionsTable->find()
            ->where([
                'contract_id' => $contract->id,
            ])
            ->limit(1)
            ->count() > 0;

        if (!$exists) {
            $this->setError(
                'contract_state_id',
                __('At least one contract version must exist for this contract state.'),
            );
        }
    }

    /**
     * Validates presence of an active contract version.
     *
     * Ensures that the contract has a currently active version.
     *
     * @return void
     */
    private function validateRequiresActiveContractVersion(Contract $contract): void
    {
        $contractVersionsTable = $this->fetchTable(ContractVersionsTable::class);
        $today = Date::now();

        $exists = $contractVersionsTable->find()
            ->where([
                'contract_id' => $contract->id,
                'valid_from <=' => $today,
                'OR' => [
                    'valid_until IS' => null,
                    'valid_until >=' => $today,
                ],
            ])
            ->limit(1)
            ->count() > 0;

        if (!$exists) {
            $this->setError(
                'contract_state_id',
                __('An active contract version is required for this contract state.'),
            );
        }
    }

    /**
     * Validates presence of an active or future contract version.
     *
     * Ensures that the contract has at least one active or future version.
     *
     * @return void
     */
    private function validateRequiresActiveOrFutureContractVersion(Contract $contract): void
    {
        $contractVersionsTable = $this->fetchTable(ContractVersionsTable::class);
        $today = Date::now();

        $exists = $contractVersionsTable->find()
            ->where([
                'contract_id' => $contract->id,
                'OR' => [
                    [
                        'valid_from <=' => $today,
                        'OR' => [
                            'valid_until IS' => null,
                            'valid_until >=' => $today,
                        ],
                    ],
                    [
                        'valid_from >' => $today,
                    ],
                ],
            ])
            ->limit(1)
            ->count() > 0;

        if (!$exists) {
            $this->setError(
                'contract_state_id',
                __('An active or future contract version is required for this contract state.'),
            );
        }
    }

    /**
     * Validates absence of active or future contract versions.
     *
     * Ensures that the contract has no active or future versions defined.
     *
     * @return void
     */
    private function validateRequiresNoActiveOrFutureContractVersions(Contract $contract): void
    {
        $contractVersionsTable = $this->fetchTable(ContractVersionsTable::class);
        $today = Date::now();

        $exists = $contractVersionsTable->find()
            ->where([
                'contract_id' => $contract->id,
                'OR' => [
                    [
                        'valid_from <=' => $today,
                        'OR' => [
                            'valid_until IS' => null,
                            'valid_until >=' => $today,
                        ],
                    ],
                    [
                        'valid_from >' => $today,
                    ],
                ],
            ])
            ->limit(1)
            ->count() > 0;

        if ($exists) {
            $this->setError(
                'contract_state_id',
                __('Active or future contract versions must be terminated or removed before changing to this'
                    . ' contract state.'),
            );
        }
    }

    /**
     * Validates absence of active contractual obligations.
     *
     * Ensures that the contract has no active obligations.
     *
     * @return void
     */
    private function validateRequiresNoActiveObligations(Contract $contract): void
    {
        $contractVersionsTable = $this->fetchTable(ContractVersionsTable::class);
        $today = Date::now();

        $exists = $contractVersionsTable->find()
            ->where([
                'contract_id' => $contract->id,
                'obligation_until IS NOT' => null,
                'obligation_until >=' => $today,
                'obligations_settled' => false,
            ])
            ->limit(1)
            ->count() > 0;

        if ($exists) {
            $this->setError(
                'contract_state_id',
                __('Active contractual obligations must be finished before changing to this contract state.'),
            );
        }
    }
}
