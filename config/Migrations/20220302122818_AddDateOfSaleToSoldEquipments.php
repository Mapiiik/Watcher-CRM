<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddDateOfSaleToSoldEquipments extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('sold_equipments');
        $table->addColumn('date_of_sale', 'date', [
            'default' => null,
            'null' => true,
        ]);
        $table->update();
    }
}
