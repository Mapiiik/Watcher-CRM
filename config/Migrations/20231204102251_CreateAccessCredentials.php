<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Util\Literal;

class CreateAccessCredentials extends AbstractMigration
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

        $table = $this->table('access_credentials', ['id' => false, 'primary_key' => ['id']]);

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);
        $table->addColumn('customer_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('contract_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('name', 'string', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('username', 'string', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('password', 'string', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('ip', 'inet', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('port', 'integer', [
            'default' => null,
            'limit' => 10,
            'null' => true,
        ]);
        $table->addColumn('note', 'text', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('created', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('created_by', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified_by', 'uuid', [
            'default' => null,
            'null' => true,
        ]);

        $table->addForeignKey('customer_id', 'customers', 'id');
        $table->addForeignKey('contract_id', 'contracts', 'id');
        $table->addForeignKey('created_by', 'users', 'id');
        $table->addForeignKey('modified_by', 'users', 'id');

        $table->create();
    }
}
