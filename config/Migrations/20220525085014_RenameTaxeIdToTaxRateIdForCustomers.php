<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RenameTaxeIdToTaxRateIdForCustomers extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('customers');
        $table->renameColumn('taxe_id', 'tax_rate_id');
        $table->update();
    }
}
