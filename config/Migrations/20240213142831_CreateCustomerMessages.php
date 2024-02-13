<?php
declare(strict_types=1);

use Migrations\AbstractMigration;
use Phinx\Util\Literal;

class CreateCustomerMessages extends AbstractMigration
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

        $table = $this->table('customer_messages', ['id' => false, 'primary_key' => ['id']]);

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'null' => false,
        ]);
        $table->addColumn('customer_id', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('type', 'smallinteger', [
            'default' => null,
            'null' => false,
            'comment' => '[enum] email:10,sms:20',
        ]);
        $table->addColumn('direction', 'smallinteger', [
            'default' => null,
            'null' => false,
            'comment' => '[enum] outgoing:10,incoming:20',
        ]);
        $table->addColumn('recipients', 'jsonb', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('subject', 'string', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('body', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('body_format', 'smallinteger', [
            'default' => null,
            'null' => false,
            'comment' => '[enum] plaintext:0,html:10',
        ]);
        $table->addColumn('delivery_status', 'smallinteger', [
            'default' => null,
            'null' => false,
            'comment' => '[enum] pending:0,sent:10,delivered:20,failed:30',
        ]);
        $table->addColumn('processed', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('identifier', 'string', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('created', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('created_by', 'uuid', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('modified', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('modified_by', 'uuid', [
            'default' => null,
            'null' => false,
        ]);

        $table->addForeignKey('customer_id', 'customers', 'id');
        $table->addForeignKey('created_by', 'users', 'id');
        $table->addForeignKey('modified_by', 'users', 'id');

        $table->create();
    }
}
