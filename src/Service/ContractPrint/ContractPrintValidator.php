<?php
declare(strict_types=1);

namespace App\Service\ContractPrint;

use App\Model\Enum\ContractPrintType;
use Cake\I18n\Date;

/**
 * Validator for contract print requests.
 *
 * Validates print-specific requirements depending on document type
 * and fills ContractPrintData with validated values.
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
     * Returns all collected validation errors.
     *
     * @return array<string, array<string>>
     */
    private function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Adds a validation error message for the given field.
     *
     * Multiple messages may be added for the same field.
     *
     * @param string $field
     * @param string $message
     * @return void
     */
    private function setError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * Validates print data according to document type.
     *
     * @param \App\Service\ContractPrint\ContractPrintData $data
     * @param array $query
     * @return array<string, array<string>>
     */
    public function validate(
        ContractPrintData $data,
        array $query,
    ): array {
        $this->errors = [];

        $this->validateCommon($data, $query);

        match ($data->type) {
            ContractPrintType::ContractNew =>
                $this->validateContractNew($data, $query),

            ContractPrintType::ContractNewX =>
                $this->validateContractNewX($data, $query),

            ContractPrintType::ContractAmendment =>
                $this->validateContractAmendment($data, $query),

            ContractPrintType::ContractTermination =>
                $this->validateContractTermination($data, $query),

            ContractPrintType::HandoverInstallation =>
                $this->validateHandoverInstallation($data, $query),

            ContractPrintType::HandoverUninstallation =>
                $this->validateHandoverUninstallation($data, $query),
        };

        return $this->getErrors();
    }

    /**
     * Common validation used by many document types.
     * Checks that a contract version is selected.
     *
     * @return bool Whether the contract version requirement is fulfilled.
     */
    private function requireContractVersion(ContractPrintData $data): bool
    {
        if ($data->contractVersion === null) {
            $this->setError(
                'contract_version_id',
                __('Please select the contract version.'),
            );

            return false;
        }

        return true;
    }

    /**
     * Common validation shared by all document types.
     */
    private function validateCommon(
        ContractPrintData $data,
        array $query,
    ): void {
        if (
            empty($query['own_equipment'])
            && $data->contract->service_type->normally_with_borrowed_equipment
            && empty($data->contract->borrowed_equipments)
        ) {
            $this->setError(
                'own_equipment',
                __(
                    'A borrowed equipment is not assigned, although it should normally be for this type of service.'
                    . ' Please confirm that the customer has their own equipment or add it.',
                ),
            );
        }

        $data->signed = !empty($query['signed']);
    }

    /**
     * Validation for new contract document.
     */
    private function validateContractNew(
        ContractPrintData $data,
        array $query,
    ): void {
        if (!$this->requireContractVersion($data)) {
            return;
        }
    }

    /**
     * Validation for new contract with replacement.
     */
    private function validateContractNewX(
        ContractPrintData $data,
        array $query,
    ): void {
        if (!$this->requireContractVersion($data)) {
            return;
        }

        if ($data->contractVersionToBeReplaced === null) {
            $this->setError(
                'contract_version_to_be_replaced_id',
                __('Please select the contract version to be replaced.'),
            );

            return;
        }

        if ($data->contractVersionToBeReplaced->id === $data->contractVersion->id) {
            $this->setError(
                'contract_version_to_be_replaced_id',
                __('The contract version to be replaced must not be the same as the new contract version.'),
            );

            return;
        }

        if (!$data->contractVersionToBeReplaced->__isset('conclusion_date')) {
            $this->setError(
                'Flash',
                __('Please set the date of conclusion of the original contract version.'),
            );
        }

        if (empty($query['number_of_the_contract_to_be_terminated'])) {
            $this->setError(
                'number_of_the_contract_to_be_terminated',
                __('Please enter the number of the contract to be terminated.'),
            );
        } else {
            $data->numberOfContractToBeTerminated =
                (string)$query['number_of_the_contract_to_be_terminated'];
        }
    }

    /**
     * Validation for contract amendment document.
     */
    private function validateContractAmendment(
        ContractPrintData $data,
        array $query,
    ): void {
        if (!$this->requireContractVersion($data)) {
            return;
        }

        if (empty($query['effective_date_of_the_amendment'])) {
            $this->setError(
                'effective_date_of_the_amendment',
                __('Please enter the effective date of the amendment.'),
            );
        } else {
            $data->effectiveDateOfAmendment =
                new Date($query['effective_date_of_the_amendment']);
        }

        if (!$data->contractVersion->__isset('conclusion_date')) {
            $this->setError(
                'conclusion_date',
                __('Please set the date of conclusion of the contract version.'),
            );
        }
    }

    /**
     * Validation for contract termination document.
     */
    private function validateContractTermination(
        ContractPrintData $data,
        array $query,
    ): void {
        if (!$this->requireContractVersion($data)) {
            return;
        }

        if (!$data->contractVersion->__isset('valid_until')) {
            $this->setError(
                'Flash',
                __('Please set the date until which the contract version is valid.'),
            );
        }

        if (!$data->contractVersion->__isset('conclusion_date')) {
            $this->setError(
                'Flash',
                __('Please set the date of conclusion of the contract version.'),
            );
        }

        if (empty($query['number_of_the_contract_to_be_terminated'])) {
            $this->setError(
                'number_of_the_contract_to_be_terminated',
                __('Please enter the number of the contract to be terminated.'),
            );
        } else {
            $data->numberOfContractToBeTerminated =
                (string)$query['number_of_the_contract_to_be_terminated'];
        }
    }

    /**
     * Validation for handover protocol – installation.
     */
    private function validateHandoverInstallation(
        ContractPrintData $data,
        array $query,
    ): void {
        if (!$this->requireContractVersion($data)) {
            return;
        }
    }

    /**
     * Validation for handover protocol – uninstallation.
     */
    private function validateHandoverUninstallation(
        ContractPrintData $data,
        array $query,
    ): void {
        if (!$this->requireContractVersion($data)) {
            return;
        }

        if (!$data->contractVersion->__isset('valid_until')) {
            $this->setError(
                'Flash',
                __('Please set the date until which the contract version is valid.'),
            );
        }

        if (empty($query['number_of_the_contract_to_be_terminated'])) {
            $this->setError(
                'number_of_the_contract_to_be_terminated',
                __('Please enter the number of the contract to be terminated.'),
            );
        } else {
            $data->numberOfContractToBeTerminated =
                (string)$query['number_of_the_contract_to_be_terminated'];
        }
    }
}
