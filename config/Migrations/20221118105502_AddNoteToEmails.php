<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddNoteToEmails extends BaseMigration
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
        $table = $this->table('emails');
        $table->addColumn('note', 'text', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->update();
    }
}
