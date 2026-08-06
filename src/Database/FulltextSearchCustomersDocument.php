<?php
declare(strict_types=1);

namespace App\Database;

/**
 * The text document the advanced customer search is answered from.
 *
 * It is the same text the search used to build on the fly: the customer, its contracts, addresses,
 * e-mails, phone numbers and IP addresses concatenated and tokenised. Building it costs about
 * 180 ms over 7500 customers and no index can help with that, because what is indexed is an
 * expression that lives in no table. Stored once per customer and indexed with GIN, the same
 * search is answered in 0.4 ms.
 *
 * This is the only place the document is defined. A document written by one path and searched
 * through another has to agree on every detail, down to the text search configuration, so both
 * the writers - `FulltextSearchCustomersTable` and the migration that first fills the table - and the
 * reader, `FulltextSearchCustomersExpression`, take it from here.
 */
final class FulltextSearchCustomersDocument
{
    /**
     * The text search configuration to tokenise with, named rather than left to the server's
     * `default_text_search_config`.
     *
     * Naming it stops being optional once the document is stored: were that setting ever to
     * change, what sits in the table and what a search asks for would quietly stop being the
     * same language, and nothing would say so.
     *
     * `simple` is not what the server default does today - that is `english`, which stems Czech
     * words by English rules to no purpose and, worse, drops `a`, `i`, `to`, `on` and `by` as
     * stop words. Those are Czech words somebody may well be searching for. `simple` folds case
     * and does nothing else, which is what a search over names, addresses, contract numbers and
     * phone numbers wants.
     *
     * @var string
     */
    public const CONFIGURATION = 'simple';

    /**
     * Builds the document of every customer the filter lets through and stores it.
     *
     * The related records are read through correlated subqueries rather than aggregated joins so
     * that the statement costs what it is asked for: refreshing one customer reads that customer's
     * records over their foreign key indexes instead of grouping all five tables to throw the rest
     * away.
     *
     * The first parameter is the customer series; any further ones are the customer ids.
     *
     * @var string
     */
    private const STATEMENT = <<<SQL
    INSERT INTO fulltext_search_customers (customer_id, document, modified)
    SELECT
        Customers.id,
        to_tsvector(
            {configuration},
            CONCAT_WS(
                ' ',
                Customers.nid + ?,
                Customers.identity_number,
                Customers.vat_number,
                Customers.first_name,
                Customers.last_name,
                Customers.company,
                (
                    SELECT
                        STRING_AGG(
                            CONCAT_WS(
                                ' ',
                                Contracts.number,
                                Contracts.subscriber_verification_code
                            ),
                            ' '
                        )
                    FROM
                        Contracts
                    WHERE
                        Contracts.customer_id = Customers.id
                ),
                (
                    SELECT
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
                        )
                    FROM
                        Addresses
                    WHERE
                        Addresses.customer_id = Customers.id
                ),
                (
                    SELECT
                        STRING_AGG(Emails.email, ' ')
                    FROM
                        Emails
                    WHERE
                        Emails.customer_id = Customers.id
                ),
                (
                    SELECT
                        STRING_AGG(
                            CONCAT_WS(
                                ' ',
                                Phones.phone,
                                REPLACE(Phones.phone, ' ', ''),
                                REGEXP_REPLACE(REGEXP_REPLACE(Phones.phone, '\+\d+', ''), '\s', '', 'g')
                            ),
                            ' '
                        )
                    FROM
                        Phones
                    WHERE
                        Phones.customer_id = Customers.id
                ),
                (
                    SELECT
                        STRING_AGG(Ip_Addresses.ip_address :: character varying, ' ')
                    FROM
                        Ip_Addresses
                    WHERE
                        Ip_Addresses.customer_id = Customers.id
                )
            )
        ),
        CURRENT_TIMESTAMP
    FROM
        Customers
    {filter}
    ON CONFLICT (customer_id) DO UPDATE SET
        document = EXCLUDED.document,
        modified = EXCLUDED.modified
    SQL;

    /**
     * The statement that stores the document of every customer there is.
     *
     * @return string
     */
    public static function forEveryCustomer(): string
    {
        return self::statement('');
    }

    /**
     * The statement that stores the documents of the given number of customers, which are to be
     * passed as parameters after the customer series.
     *
     * @param int $customers How many customers the statement is to be given.
     * @return string
     */
    public static function forCustomers(int $customers): string
    {
        return self::statement(
            'WHERE Customers.id IN (' . implode(', ', array_fill(0, max($customers, 1), '?')) . ')',
        );
    }

    /**
     * Fills the statement in.
     *
     * @param string $filter The condition narrowing it down to some customers, empty for all.
     * @return string
     */
    private static function statement(string $filter): string
    {
        return strtr(self::STATEMENT, [
            '{configuration}' => "'" . self::CONFIGURATION . "'",
            '{filter}' => $filter,
        ]);
    }
}
