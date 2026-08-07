<?php
declare(strict_types=1);

namespace App\Database;

/**
 * The text document the advanced customer search is answered from: the customer, its contracts,
 * addresses, e-mails, phone numbers and IP addresses, tokenised and stored once per customer.
 *
 * This is the only place it is defined. A document written by one path and searched through
 * another has to agree on every detail, down to the text search configuration, so the writers
 * - `FulltextSearchCustomersTable` and the migrations - and the reader,
 * `FulltextSearchCustomersExpression`, all take it from here.
 */
final class FulltextSearchCustomersDocument
{
    /**
     * The text search configuration to tokenise with, named rather than left to the server's
     * `default_text_search_config` - a stored document and a search that disagree on it would
     * quietly stop being the same language, and nothing would say so.
     *
     * `simple_unaccent` folds the case and the accents and does nothing else, on both sides:
     * `patocka` finds `Patočka` and the other way round. Still a whole word, not a fuzzy one.
     * The server default, `english`, drops `a`, `i`, `to`, `on` and `by` as stop words, which
     * are Czech words somebody may be searching for. Defined in the migration.
     *
     * @var string
     */
    public const CONFIGURATION = 'simple_unaccent';

    /**
     * Builds the document of every customer the filter lets through and stores it.
     *
     * The related records are read through correlated subqueries rather than aggregated joins, so
     * that refreshing one customer reads that customer's records over their foreign key indexes
     * instead of grouping all five tables to throw the rest away.
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
     * The migrations name the configuration instead of taking the current one: a migration has to
     * go on meaning what it meant when it was written. One taking today's would, on a database
     * built from scratch, ask for a configuration a later migration has not created yet.
     *
     * @param string $configuration Text search configuration to tokenise with.
     * @return string
     */
    public static function forEveryCustomer(string $configuration = self::CONFIGURATION): string
    {
        return self::statement('', $configuration);
    }

    /**
     * The statement that stores the documents of the given number of customers, which are to be
     * passed as parameters after the customer series.
     *
     * @param int $customers How many customers the statement is to be given.
     * @param string $configuration Text search configuration to tokenise with.
     * @return string
     */
    public static function forCustomers(int $customers, string $configuration = self::CONFIGURATION): string
    {
        return self::statement(
            'WHERE Customers.id IN (' . implode(', ', array_fill(0, max($customers, 1), '?')) . ')',
            $configuration,
        );
    }

    /**
     * Fills the statement in.
     *
     * @param string $filter The condition narrowing it down to some customers, empty for all.
     * @param string $configuration Text search configuration to tokenise with.
     * @return string
     */
    private static function statement(string $filter, string $configuration): string
    {
        return strtr(self::STATEMENT, [
            '{configuration}' => "'" . $configuration . "'",
            '{filter}' => $filter,
        ]);
    }
}
