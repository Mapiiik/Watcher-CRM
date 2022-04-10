<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddAccessPointIdToContracts extends AbstractMigration
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
        $table = $this->table('contracts');
        $table->addColumn('access_point_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->update();
    }
}
