<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Whether an account is one that takes on work.
 *
 * `active` says whether somebody can sign in, which is not the same question as whether a task can
 * be handed to them. The accounts an integration signs in as are the clearest case - they are as
 * active as any, and offering them in the list of who a task belongs to is only ever a way to lose
 * one. Test accounts are the same kind of thing.
 *
 * Everyone starts out holding tasks, so nothing about who holds what changes here. The accounts
 * that name an integration are the one exception, and they say so themselves through their role -
 * nothing has to be guessed at, and nothing about this installation is written into the migration.
 */
class AddHoldsTasksToUsers extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('users')
            ->addColumn('holds_tasks', 'boolean', [
                'default' => true,
                'null' => false,
                'after' => 'active',
            ])
            ->update();

        $this->execute("UPDATE users SET holds_tasks = false WHERE role = 'api'");
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('users')
            ->removeColumn('holds_tasks')
            ->update();
    }
}
