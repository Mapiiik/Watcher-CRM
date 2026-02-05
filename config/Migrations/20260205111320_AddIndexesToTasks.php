<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIndexesToTasks extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('tasks');

        $table->addIndex(['task_type_id']);
        $table->addIndex(['task_state_id']);
        $table->addIndex(['access_point_id']);

        $table->addIndex(['customer_id']);
        $table->addIndex(['contract_id']);

        $table->update();
    }
}
