<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * The links to dealers that tasks and accounts kept from before tasks were held by users.
 *
 * A task named the dealer that held it, and an account the dealer it belonged to, which is how the
 * tasks found their users when they moved to them. Nothing has read either since.
 */
class DropDealerLinksOfTasksAndAccounts extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('tasks')->removeColumn('dealer_id')->update();
        $this->table('users')->removeColumn('customer_id')->update();
    }

    /**
     * Down Method.
     *
     * The columns come back empty.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('tasks')
            ->addColumn('dealer_id', 'uuid', ['null' => true])
            ->addForeignKey('dealer_id', 'customers', 'id', ['constraint' => 'tasks_dealer_id_fkey'])
            ->update();
        $this->table('users')
            ->addColumn('customer_id', 'uuid', ['null' => true])
            ->addForeignKey('customer_id', 'customers', 'id', ['constraint' => 'users_customer_id_fkey'])
            ->update();
    }
}
