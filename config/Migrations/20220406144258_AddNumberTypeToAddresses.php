<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddNumberTypeToAddresses extends AbstractMigration
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
        $table = $this->table('addresses');
        $table->addColumn('number_type', 'integer', [
            'default' => 0,
            'limit' => 10,
            'null' => false,
        ]);
        $table->update();
    }
}
