<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Whether closing a task of this type is worth telling the report addresses about.
 *
 * Closing a task does not always end the work. An installation that is done still has to be
 * invoiced, a dismantling still has equipment to book back in - and whoever does that is not the
 * one who closed the task, so they never learn it happened. The task simply drops out of the open
 * list and the step that was supposed to follow is forgotten.
 *
 * Only some types are worth reporting, which is why this is asked per type rather than turned on
 * for tasks as a whole. Off by default: a deployment that says nothing is to be reported keeps the
 * behaviour it has today.
 */
class AddReportOnCompletionToTaskTypes extends BaseMigration
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
        $table = $this->table('task_types');
        $table->addColumn('report_on_completion', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->update();
    }
}
