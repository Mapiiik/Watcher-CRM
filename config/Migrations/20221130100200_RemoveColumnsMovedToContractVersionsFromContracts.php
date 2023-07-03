<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RemoveColumnsMovedToContractVersionsFromContracts extends AbstractMigration
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
        $table = $this->table('contracts');
        $table->removeColumn('valid_from');
        $table->removeColumn('valid_until');
        $table->removeColumn('obligation_until');
        $table->removeColumn('conclusion_date');
        $table->removeColumn('number_of_amendments');
        $table->update();
    }
}
