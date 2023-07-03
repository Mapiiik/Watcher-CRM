<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddManualCoordinateSettingToAddresses extends AbstractMigration
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
        $table = $this->table('addresses');
        $table->addColumn('manual_coordinate_setting', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->renameColumn('gpsx', 'gps_x');
        $table->renameColumn('gpsy', 'gps_y');
        $table->update();
    }
}
