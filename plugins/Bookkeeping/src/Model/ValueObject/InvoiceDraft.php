<?php
declare(strict_types=1);

namespace Bookkeeping\Model\ValueObject;

use App\Model\Entity\Customer;
use Cake\I18n\Date;
use PhpCollective\DecimalObject\Decimal;

/**
 * InvoiceDraft
 *
 * Lightweight, mutable value object representing an invoice
 * parsed from an external source (XML, DBF, CSV, ...).
 *
 * This object is intentionally decoupled from ORM and persistence.
 * It serves as an intermediate data structure that can be:
 *  - validated
 *  - displayed (preview / dry-run)
 *  - compared with existing data
 *  - mapped into a persistent Invoice entity
 *
 * Fields may include additional metadata that is not intended
 * to be stored in the database.
 */
final class InvoiceDraft
{
    /**
     * Invoice number from the source system
     */
    public ?string $number = null;

    /**
     * Variable symbol (VS)
     */
    public ?string $variableSymbol = null;

    /**
     * Invoice creation date
     */
    public ?Date $creationDate = null;

    /**
     * Invoice due date
     */
    public ?Date $dueDate = null;

    /**
     * Invoice text / description
     */
    public ?string $text = null;

    /**
     * Invoice note
     */
    public ?string $note = null;

    /**
     * Invoice internal note
     */
    public ?string $internalNote = null;

    /**
     * Total invoice amount
     */
    public ?Decimal $total = null;

    /**
     * Remaining debt amount
     */
    public ?Decimal $debt = null;

    /**
     * Payment (liquidation) date, if available
     */
    public ?Date $paymentDate = null;

    /**
     * Accounting Identifier
     */
    public ?string $accountingIdentifier = null;

    /**
     * Customer number (CRM)
     */
    public ?string $customerNumber = null;

    /**
     * Customer Entity
     */
    public ?Customer $customer = null;

    /**
     * Invoice items
     *
     * @var list<\App\Model\Entity\Billing>
     */
    public array $items = [];

    /**
     * Optional metadata collected during parsing or validation.
     * Not intended for persistence.
     *
     * @var array<string, mixed>
     */
    public array $metadata = [];

    /**
     * Optional warnings or validation messages related to this draft.
     *
     * @var list<string>
     */
    public array $warnings = [];

    /**
     * Add warning message
     */
    public function addWarning(string $message): void
    {
        $this->warnings[] = $message;
    }

    /**
     * Check if method has warnings
     */
    public function hasWarnings(): bool
    {
        return $this->warnings !== [];
    }

    /**
     * Check invoice structural validity.
     *
     * Ensures all required fields are present and syntactically valid.
     * Business rules (e.g. VS range, customer existence) are handled elsewhere.
     */
    public function isValid(): bool
    {
        $valid = true;

        if ($this->number === null || $this->number === '') {
            $this->addWarning('Missing invoice number');
            $valid = false;
        }

        if ($this->variableSymbol === null) {
            $this->addWarning('Missing variable symbol');
            $valid = false;
        }

        if ($this->customerNumber === null) {
            $this->addWarning('Missing customer number');
            $valid = false;
        }

        if ($this->creationDate === null) {
            $this->addWarning('Missing creation date');
            $valid = false;
        }

        if ($this->dueDate === null) {
            $this->addWarning('Missing due date');
            $valid = false;
        }

        if ($this->text === null || trim($this->text) === '') {
            $this->addWarning('Missing invoice text');
            $valid = false;
        }

        if ($this->total === null) {
            $this->addWarning('Missing total amount');
            $valid = false;
        } elseif ($this->total->isNegative()) {
            $this->addWarning('Total amount cannot be negative');
            $valid = false;
        }

        if ($this->debt === null) {
            $this->addWarning('Missing remaining debt');
            $valid = false;
        } elseif ($this->debt->isNegative()) {
            $this->addWarning('Remaining debt cannot be negative');
            $valid = false;
        }

        return $valid;
    }
}
