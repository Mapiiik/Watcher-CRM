<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCreationYearToInvoices extends BaseMigration
{
    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-up-method
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute('
            ALTER TABLE invoices
            ADD COLUMN creation_year SMALLINT
            GENERATED ALWAYS AS (EXTRACT(YEAR FROM creation_date)::SMALLINT) STORED
        ');
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-down-method
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute('ALTER TABLE invoices DROP COLUMN creation_year');
    }
}
