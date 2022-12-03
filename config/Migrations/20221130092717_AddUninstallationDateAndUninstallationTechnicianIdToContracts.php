<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddUninstallationDateAndUninstallationTechnicianIdToContracts extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('contracts');
        $table->addColumn('uninstallation_date', 'date', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('uninstallation_technician_id', 'integer', [
            'default' => null,
            'limit' => 10,
            'null' => true,
        ]);
        $table->update();
    }
}
