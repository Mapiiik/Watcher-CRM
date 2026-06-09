<?php
declare(strict_types=1);

namespace App\Service\CustomerPrint;

use App\Model\Entity\Customer;
use App\Model\Enum\CustomerPrintType;

/**
 * Data Transfer Object for customer PDF printing.
 *
 * This object represents a fully validated and prepared dataset
 * required for generating a customer-related PDF document.
 *
 * It intentionally separates print-specific data from domain entities
 * to avoid mutating Customer with temporary state.
 *
 * Lifecycle:
 *  - Created by Controller
 *  - Filled by CustomerPrintValidator
 *  - Consumed by PDF generator
 */
final class CustomerPrintData
{
    /**
     * Type of document being printed.
     */
    public CustomerPrintType $type;

    /**
     * Customer being printed.
     */
    public Customer $customer;

    /**
     * Constructor.
     */
    public function __construct(
        CustomerPrintType $type,
        Customer $customer,
    ) {
        $this->type = $type;
        $this->customer = $customer;
    }
}
