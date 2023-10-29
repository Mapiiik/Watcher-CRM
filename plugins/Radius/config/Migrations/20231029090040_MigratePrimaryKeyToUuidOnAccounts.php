<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Util\Literal;

class MigratePrimaryKeyToUuidOnAccounts extends AbstractMigration
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
        // create extension for full UUID support
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');
        $table = $this->table('accounts');

        $table->renameColumn('id', 'nid');
        $table->update();

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);
        $table->update();

        // reset primary key in case of up migration
        if ($this->isMigratingUp()) {
            $table->changePrimaryKey(['id']);
            $table->update();
        }

        // remove old numeric primary key
        $table->removeColumn('nid');
        $table->update();
    }
}
