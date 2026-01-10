<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class UpdateInvoicesNumberAndAddAccountingIdentifierToInvoices extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('invoices');

        $table->changeColumn('number', 'string', [
            'limit' => null,
            'null' => false,
        ]);

        $table->addColumn('accounting_identifier', 'string', [
            'limit' => null,
            'null' => true,
        ]);

        $table->addIndex(
            ['accounting_identifier'],
            ['unique' => true],
        );

        $table->update();
    }
}
