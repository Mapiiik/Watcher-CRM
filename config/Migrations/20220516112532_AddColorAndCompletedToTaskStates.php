<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddColorAndCompletedToTaskStates extends AbstractMigration
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
        $table->addColumn('color', 'string', [
            'default' => '#ffffff',
            'limit' => 7,
            'null' => false,
        ]);
        $table->addColumn('completed', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->update();
    }
}
