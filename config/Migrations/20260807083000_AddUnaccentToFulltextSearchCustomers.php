<?php
declare(strict_types=1);

use App\Database\FulltextSearchCustomersDocument;
use Migrations\BaseMigration;

class AddUnaccentToFulltextSearchCustomers extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS unaccent');

        // Dropped first because the test runner rebuilds the schema by dropping the tables and
        // applying the migrations again, and a configuration is not a table, so it outlives that.
        $this->execute('DROP TEXT SEARCH CONFIGURATION IF EXISTS simple_unaccent');

        // Only the mappings that can carry an accent are altered; `asciiword` is ASCII by
        // definition and has nothing to fold.
        $this->execute('CREATE TEXT SEARCH CONFIGURATION simple_unaccent (COPY = simple)');
        $this->execute(
            'ALTER TEXT SEARCH CONFIGURATION simple_unaccent'
            . ' ALTER MAPPING FOR hword, hword_part, word WITH unaccent, simple',
        );

        // A document in the old configuration answers to nothing the new one asks - not with an
        // error, just with nothing found - so the rebuild belongs in the step that creates it.
        $this->execute(
            FulltextSearchCustomersDocument::forEveryCustomer('simple_unaccent'),
            [(int)env('CUSTOMER_SERIES', '0')],
        );
    }

    /**
     * Down Method.
     *
     * The documents go back with the code, which says which configuration to build them in: roll
     * the code back first, then `bin/cake fulltext_search_customers rebuild`.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute('DROP TEXT SEARCH CONFIGURATION IF EXISTS simple_unaccent');
    }
}
