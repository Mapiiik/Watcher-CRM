<?php
declare(strict_types=1);

namespace App\Customers\Check;

use App\Check\AbstractCheckRegistry;
use App\Check\CheckScope;
use App\Model\Table\CustomerProposalsTable;
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
     * them, then what they have been asked, and last the papers put to them that are not
     * finished.
     *
     * @param bool $ignore_inactive Whether the checks keep to the customers with something
     *   running. What is on file about somebody we no longer serve is not worth chasing, and
     *   off, the checks reach back through everybody who was ever on the books.
     * @param string|null $customer_id One customer to ask about, rather than the whole file.
     *   This is what lets a customer's own card show what is missing about them.
     */
    public function __construct(bool $ignore_inactive = true, ?string $customer_id = null)
    {
        $this->scope = new CheckScope($ignore_inactive, null, $customer_id);

        /** @var \App\Model\Table\CustomersTable $customers */
        $customers = $this->fetchTable(CustomersTable::class);

        /** @var \App\Model\Table\CustomerProposalsTable $proposals */
        $proposals = $this->fetchTable(CustomerProposalsTable::class);

        $this->factories = [
            'incomplete_identity' => fn() => new IncompleteIdentityCheck($customers, $this->scope),
            'missing_email' => fn() => new MissingEmailCheck($customers, $this->scope),
            'missing_phone' => fn() => new MissingPhoneCheck($customers, $this->scope),
            'missing_gdpr_consent' => fn() => new MissingGdprConsentCheck($customers, $this->scope),
            'unsent_customer_proposal' => fn() => new UnsentCustomerProposalCheck($proposals, $this->scope),
            'unsigned_customer_proposal' => fn() => new UnsignedCustomerProposalCheck($proposals, $this->scope),
            'unfiled_customer_proposal' => fn() => new UnfiledCustomerProposalCheck($proposals, $this->scope),
        ];
    }
}
