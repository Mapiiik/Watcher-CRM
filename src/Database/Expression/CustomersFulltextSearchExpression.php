<?php
declare(strict_types=1);

namespace App\Database\Expression;

use Cake\Database\ExpressionInterface;
use Cake\Database\ValueBinder;
use Closure;

/**
 * Fulltext search over customers and everything reachable from them — contracts, addresses,
 * e-mails, phone numbers and IP addresses.
 *
 * The search term and the customer series are bound into the value binder the expression is
 * compiled with, instead of into the binder of the query the condition is added to. Binding them
 * on the query itself would not survive eager loading: the `subquery` strategy clones the source
 * query, replaces its value binder with an empty one and embeds the clone as a derived table,
 * which would leave the placeholders in the SQL without any values.
 */
final class CustomersFulltextSearchExpression implements ExpressionInterface
{
    /**
     * Selects the ids of the customers matching the search term.
     *
     * @var string
     */
    private const SQL = <<<SQL
    SELECT
        Customers.id
    FROM
        Customers
        LEFT JOIN (
            SELECT
                Contracts.customer_id,
                STRING_AGG(
                    CONCAT_WS(
                        ' ',
                        Contracts.number,
                        Contracts.subscriber_verification_code
                    ),
                    ' '
                ) AS txt
            FROM
                Contracts
            GROUP BY
                1
        ) Contracts ON (
            Contracts.customer_id = Customers.id
        ) 
        LEFT JOIN (
            SELECT 
                Addresses.customer_id, 
                STRING_AGG(
                    CONCAT_WS(
                        ' ',
                        Addresses.first_name,
                        Addresses.last_name,
                        Addresses.company,
                        Addresses.street, 
                        Addresses.number,
                        Addresses.city,
                        Addresses.zip
                    ), 
                    ' '
                ) AS txt 
            FROM 
                Addresses 
            GROUP BY 
                1
        ) Addresses ON (
            Addresses.customer_id = Customers.id
        ) 
        LEFT JOIN (
            SELECT 
                Emails.customer_id, 
                STRING_AGG(Emails.email, ' ') AS txt 
            FROM 
                Emails 
            GROUP BY 
                1
        ) Emails ON (
            Emails.customer_id = Customers.id
        ) 
        LEFT JOIN (
            SELECT 
                Phones.customer_id, 
                STRING_AGG(Phones.phone, ' ') AS txt_1,
                STRING_AGG(REPLACE(Phones.phone, ' ', ''), ' ') AS txt_2,
                STRING_AGG(REGEXP_REPLACE(REGEXP_REPLACE(Phones.phone, '\+\d+', ''), '\s', '', 'g'), ' ') AS txt_3
            FROM 
                Phones 
            GROUP BY 
                1
        ) Phones ON (
            Phones.customer_id = Customers.id
        ) 
        LEFT JOIN (
            SELECT 
            Ip_Addresses.customer_id, 
                STRING_AGG(Ip_Addresses.ip_address :: character varying, ' ') AS txt 
            FROM 
                Ip_Addresses
            GROUP BY 
                1
        ) Ip_Addresses ON (
            Ip_Addresses.customer_id = Customers.id
        ) 
    WHERE 
        to_tsvector (
            CONCAT_WS(
                ' ',
                Customers.nid + :customer_series,
                Customers.identity_number,
                Customers.vat_number,
                Customers.first_name, 
                Customers.last_name,
                Customers.company, 
                Contracts.txt,
                Addresses.txt,
                Emails.txt,
                Phones.txt_1,
                Phones.txt_2,
                Phones.txt_3,
                Ip_Addresses.txt
            )
        ) @@ websearch_to_tsquery(:search) 
    GROUP BY 
        Customers.id
    SQL;

    /**
     * Constructor
     *
     * @param string $search Term to search for.
     * @param int $customerSeries Offset added to the customer number before it is indexed.
     */
    public function __construct(
        private readonly string $search,
        private readonly int $customerSeries,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function sql(ValueBinder $binder): string
    {
        $customerSeries = $binder->placeholder('c');
        $binder->bind($customerSeries, $this->customerSeries, 'integer');

        $search = $binder->placeholder('c');
        $binder->bind($search, $this->search, 'string');

        return 'Customers.id IN (' . strtr(self::SQL, [
            ':customer_series' => $customerSeries,
            ':search' => $search,
        ]) . ')';
    }

    /**
     * @inheritDoc
     */
    public function traverse(Closure $callback): static
    {
        return $this;
    }
}
