<?php
declare(strict_types=1);

namespace Bookkeeping\Service;

/**
 * VerificationResult
 *
 * Immutable value object representing the result of CSV vs CRM verification.
 */
final readonly class VerificationResult
{
    /**
     * Constructor
     */
    public function __construct(
        private array $differences,
        private array $errors = [],
    ) {
    }

    /**
     * Whether verification passed without differences and errors.
     */
    public function isOk(): bool
    {
        return $this->differences === [] && $this->errors === [];
    }

    /**
     * Get verification differences.
     *
     * @return array<string, array{
     *   csv?: array{total: \PhpCollective\DecimalObject\Decimal, items: array},
     *   crm?: array{total: \PhpCollective\DecimalObject\Decimal, items: array},
     *   customer?: \App\Model\Entity\Customer
     * }>
     */
    public function getDifferences(): array
    {
        return $this->differences;
    }

    /**
     * Get verification errors.
     *
     * @return array<int, array{line:int, message:string, value:string}>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Whether verification has errors.
     */
    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
