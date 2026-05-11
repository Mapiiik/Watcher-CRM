<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddCriticalityLevelToServices extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('services');
        $table->addColumn('criticality_level', 'smallinteger', [
            'default' => 10,
            'null' => false,
            'comment' => '[enum] normal:10,important:20,critical:30',
        ]);
        $table->update();
    }
}
