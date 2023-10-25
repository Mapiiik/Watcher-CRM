<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddPriorityToTaskStates extends AbstractMigration
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
        $table = $this->table('task_states');

        $table->addColumn('priority', 'integer', [
            'default' => '0',
            'limit' => 10,
            'null' => false,
        ]);

        $table->update();
    }
}
