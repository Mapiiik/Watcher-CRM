<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AlterColorOnLabels extends AbstractMigration
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
        $table = $this->table('labels');
        $table->changeColumn('color', 'string', [
            'default' => '#ffffff',
            'limit' => 7,
            'null' => false,
        ]);
        $table->update();
    }
}
