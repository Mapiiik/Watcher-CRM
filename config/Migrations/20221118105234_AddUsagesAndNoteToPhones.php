<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddUsagesAndNoteToPhones extends AbstractMigration
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
        $table = $this->table('phones');
        $table->addColumn('use_for_billing', 'boolean', [
            'default' => true,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('use_for_outages', 'boolean', [
            'default' => true,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('use_for_commercial', 'boolean', [
            'default' => true,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('note', 'text', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->update();
    }
}
