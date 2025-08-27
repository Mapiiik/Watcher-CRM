<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddNormallyWithBorrowedEquipmentToServiceTypes extends BaseMigration
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
        $table = $this->table('service_types');
        $table->addColumn('normally_with_borrowed_equipment', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->update();
    }
}
