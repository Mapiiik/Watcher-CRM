<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RemoveActiveFromBillings extends AbstractMigration
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
        $table = $this->table('billings');
        $table->changeColumn('billing_from', 'date', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->removeColumn('active');
        $table->update();
    }
}
