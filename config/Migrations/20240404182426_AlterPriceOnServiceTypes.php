<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AlterPriceOnServiceTypes extends AbstractMigration
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
        $table = $this->table('service_types');
        $table->changeColumn('activation_fee', 'decimal', [
            'default' => null,
            'null' => true,
            'precision' => 10,
            'scale' => 2,
        ]);
        $table->changeColumn('activation_fee_with_obligation', 'decimal', [
            'default' => null,
            'null' => true,
            'precision' => 10,
            'scale' => 2,
        ]);
        $table->update();
    }
}
