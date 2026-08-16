<?php
declare(strict_types=1);

namespace App\Dashboard\Card;

use Dashboard\Card\AbstractDashboardCard;

/**
 * Shared ground for the cards that count customers and point at the customer listing.
 */
abstract class AbstractCustomerListingCard extends AbstractDashboardCard
{
    /**
     * The customer listing narrowed to one of this card's rows.
     *
     * The listing keeps its filter in the session and hides these fields behind the
     * advanced search, so the link switches that on and names every field rather than
     * leaving whatever the operator last filtered by in force. The fields being cleared
     * are handed an empty string rather than an empty array, which the URL would drop and
     * the listing would then read as "unchanged".
     *
     * @param array<string, mixed> $filter What this row narrows by.
     * @return array<string, mixed>
     */
    protected function customerListingUrl(array $filter): array
    {
        return [
            'controller' => 'Customers',
            'action' => 'index',
            'customer_id' => false,
            '?' => $filter + [
                'advanced_search' => 1,
                'contract_state_id' => '',
                'service_type_id' => '',
                'label_ids' => '',
                'not_label_ids' => '',
                'search' => '',
            ],
        ];
    }
}
