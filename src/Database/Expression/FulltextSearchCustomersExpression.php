<?php
declare(strict_types=1);

namespace App\Database\Expression;

use App\Database\FulltextSearchCustomersDocument;
use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;

/**
 * Fulltext search over customers and everything reachable from them — contracts, addresses,
 * e-mails, phone numbers and IP addresses.
 *
 * What is searched is the document stored for each customer in `fulltext_search_customers`, kept
 * up to date by `FulltextSearchCustomersBehavior`; `FulltextSearchCustomersDocument` says what
 * goes into it.
 *
 * The search term is bound into the value binder the expression is compiled with, instead of into
 * the binder of the query the condition is added to. Binding it on the query itself would not
 * survive eager loading: the `subquery` strategy clones the source query, replaces its value
 * binder with an empty one and embeds the clone as a derived table, which would leave the
 * placeholder in the SQL without a value.
 */
final class FulltextSearchCustomersExpression implements ExpressionInterface
{
    /**
     * Constructor
     *
     * @param string $search Term to search for.
     */
    public function __construct(
        private readonly string $search,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $search = $binder->placeholder('c');
        $binder->bind($search, $this->search, 'string');

        return sprintf(
            'Customers.id IN (SELECT customer_id FROM fulltext_search_customers WHERE document @@ '
            . "websearch_to_tsquery('%s', %s))",
            FulltextSearchCustomersDocument::CONFIGURATION,
            $search,
        );
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback): static
    {
        return $this;
    }
}
