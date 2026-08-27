<?php
declare(strict_types=1);

namespace App\Customers\Check;

use App\Check\AbstractCheckRegistry;
use App\Model\Table\CustomersTable;

/**
 * Registry of the checks that can be run against what is on record about a customer.
 *
 * This is the single extension point: register a check here, give it a template beside the
 * others, and the dashboard card, the overview and the customer's own card pick it up.
 *
 * @extends \App\Check\AbstractCheckRegistry<\App\Customers\Check\CustomerCheckInterface>
 */
final class CustomerCheckRegistry extends AbstractCheckRegistry
{
    /**
     * Registered in the order they are listed: who the customer is first, then how to reach
     * them, then what they have been asked.
     *
     * @param bool $ignore_inactive Whether the checks keep to the customers with something
     *   running. What is on file about somebody we no longer serve is not worth chasing, and
     *   off, the checks reach back through everybody who was ever on the books.
     * @param string|null $customer_id One customer to ask about, rather than the whole file.
     *   This is what lets a customer's own card show what is missing about them.
     */
    public function __construct(private bool $ignore_inactive = true, private ?string $customer_id = null)
    {
        /** @var \App\Model\Table\CustomersTable $customers */
        $customers = $this->fetchTable(CustomersTable::class);

        $this->factories = [
            'incomplete_identity' =>
                fn(): CustomerCheckInterface => new IncompleteIdentityCheck(
                    $customers,
                    $this->ignore_inactive,
                    $this->customer_id,
                ),
            'missing_email' =>
                fn(): CustomerCheckInterface => new MissingEmailCheck(
                    $customers,
                    $this->ignore_inactive,
                    $this->customer_id,
                ),
            'missing_phone' =>
                fn(): CustomerCheckInterface => new MissingPhoneCheck(
                    $customers,
                    $this->ignore_inactive,
                    $this->customer_id,
                ),
            'missing_gdpr_consent' =>
                fn(): CustomerCheckInterface => new MissingGdprConsentCheck(
                    $customers,
                    $this->ignore_inactive,
                    $this->customer_id,
                ),
        ];
    }
}
