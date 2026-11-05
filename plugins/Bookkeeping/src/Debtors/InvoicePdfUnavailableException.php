<?php
declare(strict_types=1);

namespace Bookkeeping\Debtors;

use Bookkeeping\Model\Entity\Invoice;
use RuntimeException;
use Throwable;

/**
 * The PDF of an invoice could not be had, so a message that goes out with it cannot be written.
 *
 * Says which invoice it was and passes on what the accounting system said in its own words.
 */
final class InvoicePdfUnavailableException extends RuntimeException
{
    /**
     * @param \Bookkeeping\Model\Entity\Invoice $invoice The invoice.
     * @param \Throwable $previous What went wrong in getting it.
     */
    public function __construct(public readonly Invoice $invoice, Throwable $previous)
    {
        parent::__construct($previous->getMessage(), 0, $previous);
    }
}
