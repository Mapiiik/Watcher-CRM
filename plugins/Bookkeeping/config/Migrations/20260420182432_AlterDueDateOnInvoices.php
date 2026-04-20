<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterDueDateOnInvoices extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('invoices');
        $table->changeColumn('due_date', 'date', [
            'default' => null,
            'null' => false,
        ]);
        $table->update();
    }
}
