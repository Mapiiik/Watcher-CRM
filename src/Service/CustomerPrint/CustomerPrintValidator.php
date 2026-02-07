<?php
declare(strict_types=1);

namespace App\Service\CustomerPrint;

use App\Model\Enum\CustomerPrintType;

/**
 * Validator for customer print requests.
 *
 * Validates print-specific requirements depending on document type
 * and fills CustomerPrintData with validated values.
 *
 * This validator:
 *  - does NOT perform redirects
 *  - does NOT use Flash messages
 *  - does NOT validate general customer consistency
 *
 * Validation errors are collected per field.
 */
final class CustomerPrintValidator
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
     * @phpstan-ignore method.unused
     */
    private function setError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    /**
     * Validates print data according to document type.
     *
     * @param \App\Service\CustomerPrint\CustomerPrintData $data
     * @param array $query
     * @return array<string, array<string>>
     */
    public function validate(
        CustomerPrintData $data,
        array $query,
    ): array {
        $this->errors = [];

        $this->validateCommon($data, $query);

        match ($data->type) {
            CustomerPrintType::GdprNew =>
                $this->validateGdprNew($data, $query),

            CustomerPrintType::GdprChange =>
                $this->validateGdprChange($data, $query),
        };

        return $this->getErrors();
    }

    /**
     * Common validation shared by all customer document types.
     */
    private function validateCommon(
        CustomerPrintData $data,
        array $query,
    ): void {
        // Placeholder for future shared validations
        // (e.g. required customer attributes, consent state, etc.)
    }

    /**
     * Validation for new GDPR consent document.
     */
    private function validateGdprNew(
        CustomerPrintData $data,
        array $query,
    ): void {
        // Currently no additional validation required
    }

    /**
     * Validation for GDPR consent change document.
     */
    private function validateGdprChange(
        CustomerPrintData $data,
        array $query,
    ): void {
        // Currently no additional validation required
    }
}
