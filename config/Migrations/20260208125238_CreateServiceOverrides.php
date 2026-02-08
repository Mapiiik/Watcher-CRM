<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

class CreateServiceOverrides extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        // create extension for full UUID support
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        $table = $this->table('service_overrides', [
            'id' => false,
            'primary_key' => ['id'],
        ]);

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);

        $table->addColumn('contract_id', 'uuid', [
            'null' => false,
        ]);

        $table->addColumn('service_id', 'uuid', [
            'null' => false,
        ]);

        $table->addColumn('valid_from', 'date', [
            'null' => false,
        ]);

        $table->addColumn('valid_until', 'date', [
            'null' => false,
        ]);

        $table->addColumn('reason', 'text', [
            'null' => true,
        ]);

        $table->addColumn('created', 'timestamp', [
            'timezone' => true,
            'null' => true,
        ]);

        $table->addColumn('created_by', 'uuid', [
            'null' => true,
        ]);

        $table->addColumn('modified', 'timestamp', [
            'timezone' => true,
            'null' => true,
        ]);

        $table->addColumn('modified_by', 'uuid', [
            'null' => true,
        ]);

        $table->addColumn('revoked', 'timestamp', [
            'timezone' => true,
            'null' => true,
        ]);

        $table->addColumn('revoked_by', 'uuid', [
            'null' => true,
        ]);

        $table->addIndex(['contract_id']);

        $table->addForeignKey('contract_id', 'contracts', 'id');
        $table->addForeignKey('service_id', 'services', 'id');
        $table->addForeignKey('created_by', 'users', 'id');
        $table->addForeignKey('modified_by', 'users', 'id');
        $table->addForeignKey('revoked_by', 'users', 'id');

        $table->create();
    }
}
