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
     * Validates print data according to document type.
     *
     * @return array<string, array<string>>
     */
    public function validate(
        CustomerPrintData $data,
    ): array {
        $this->errors = [];

        $this->validateCommon();

        match ($data->type) {
            CustomerPrintType::GdprNew =>
                $this->validateGdprNew(),

            CustomerPrintType::GdprChange =>
                $this->validateGdprChange(),
        };

        return $this->errors;
    }

    /**
     * Common validation shared by all customer document types.
     */
    private function validateCommon(): void
    {
        // Placeholder for future shared validations
        // (e.g. required customer attributes, consent state, etc.)
    }

    /**
     * Validation for new GDPR consent document.
     */
    private function validateGdprNew(): void
    {
        // Currently no additional validation required
    }

    /**
     * Validation for GDPR consent change document.
     */
    private function validateGdprChange(): void
    {
        // Currently no additional validation required
    }
}
