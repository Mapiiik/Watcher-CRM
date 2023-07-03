<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddNotForNewCustomersToServices extends AbstractMigration
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
        $table = $this->table('services');
        $table->addColumn('not_for_new_customers', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->update();
    }
}
