<?php
declare(strict_types=1);

namespace App\Service\CustomerPrint;

use App\Model\Entity\Customer;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerDocumentType;

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
    public CustomerDocumentType $type;

    /**
     * Customer being printed.
     */
    public Customer $customer;

    /**
     * The round the paper belongs to, where it belongs to one.
     *
     * What is drawn from a round is kept and handed back ever after, so this is what says where
     * to keep it. A paper drawn from nothing is handed over and forgotten.
     */
    public ?CustomerProposal $proposal;

    /**
     * Constructor.
     */
    public function __construct(
        CustomerDocumentType $type,
        Customer $customer,
        ?CustomerProposal $proposal = null,
    ) {
        $this->type = $type;
        $this->customer = $customer;
        $this->proposal = $proposal;
    }
}
