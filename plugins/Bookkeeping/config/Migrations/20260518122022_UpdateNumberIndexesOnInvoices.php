<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class UpdateNumberIndexesOnInvoices extends BaseMigration
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
        $table = $this->table('invoices');
        $table->removeIndex(['number']); // remove unique index
        $table->addIndex(['number']);
        $table->addIndex(
            ['number', 'creation_year'],
            ['unique' => true],
        );
        $table->update();
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
        $table = $this->table('invoices');
        $table->removeIndex(['number', 'creation_year']);
        $table->update();

        $table->removeIndex(['number']); // remove non-unique index
        $table->addIndex(
            ['number'],
            ['unique' => true],
        );
        $table->update();
    }
}
