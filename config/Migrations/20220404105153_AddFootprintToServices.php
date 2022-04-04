<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddFootprintToServices extends AbstractMigration
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
        $table = $this->table('services');
        $table->addColumn('created_by', 'integer', [
            'default' => null,
            'limit' => 10,
            'null' => true,
        ]);
        $table->addColumn('modified_by', 'integer', [
            'default' => null,
            'limit' => 10,
            'null' => true,
        ]);
        $table->update();
    }
}
