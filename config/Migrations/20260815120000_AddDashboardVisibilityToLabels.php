<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddDashboardVisibilityToLabels extends BaseMigration
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
        $table = $this->table('labels');

        $table->addColumn('show_on_dashboard', 'boolean', [
            'default' => false,
            'null' => false,
        ]);

        // The roles the label is drawn for; an empty list draws it for all of them.
        $table->addColumn('dashboard_roles', 'jsonb', [
            'default' => null,
            'null' => true,
        ]);

        $table->update();
    }
}
