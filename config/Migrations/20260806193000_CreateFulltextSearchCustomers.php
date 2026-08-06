<?php
declare(strict_types=1);

use App\Database\FulltextSearchCustomersDocument;
use Migrations\BaseMigration;

class CreateFulltextSearchCustomers extends BaseMigration
{
    /**
     * Up Method.
     *
     * Written in SQL rather than through the table builder: neither `tsvector` nor a GIN index
     * over it is something the builder knows, and both are the whole point of the table.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(<<<SQL
        CREATE TABLE fulltext_search_customers (
            customer_id uuid PRIMARY KEY REFERENCES customers (id) ON DELETE CASCADE,
            document tsvector NOT NULL,
            modified timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
        SQL);

        $this->execute(
            'CREATE INDEX fulltext_search_customers_document'
            . ' ON fulltext_search_customers USING gin (document)',
        );

        // Fill it here rather than leave it to the command that keeps it fresh: from the moment
        // the search reads this table, a customer without a document is a customer the search
        // cannot find, and nothing about that would look like an error.
        $this->execute(
            FulltextSearchCustomersDocument::forEveryCustomer(),
            [(int)env('CUSTOMER_SERIES', '0')],
        );
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute('DROP TABLE fulltext_search_customers');
    }
}
