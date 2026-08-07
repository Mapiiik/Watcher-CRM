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

        // Dropped first because this is not only run against a database that has never seen it:
        // the test runner rebuilds the schema by dropping the tables and applying the migrations
        // again, and a text search configuration is not a table, so it outlives that.
        $this->execute('DROP TEXT SEARCH CONFIGURATION IF EXISTS simple_unaccent');

        // `simple` with the accents folded away as well, on both sides - in what is stored and in
        // what is asked for. Only the mappings that can carry an accent are altered; `asciiword`
        // is ASCII by definition and has nothing to fold.
        $this->execute('CREATE TEXT SEARCH CONFIGURATION simple_unaccent (COPY = simple)');
        $this->execute(
            'ALTER TEXT SEARCH CONFIGURATION simple_unaccent'
            . ' ALTER MAPPING FOR hword, hword_part, word WITH unaccent, simple',
        );

        // Every stored document is in the configuration this one replaces, and would answer to
        // nothing the new one asks - not with an error, just with nothing found. The rebuild
        // belongs in the same step that makes the configuration exist, and names the
        // configuration for the same reason the migration before it does.
        $this->execute(
            FulltextSearchCustomersDocument::forEveryCustomer('simple_unaccent'),
            [(int)env('CUSTOMER_SERIES', '0')],
        );
    }

    /**
     * Down Method.
     *
     * The documents go back with the code, which is what says which configuration to build them
     * in, so the way back is the code first and `bin/cake fulltext_search_customers rebuild`
     * after it - the same order the way here takes.
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute('DROP TEXT SEARCH CONFIGURATION IF EXISTS simple_unaccent');
    }
}
