<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

/**
 * The people working on a task beside the one who holds it.
 *
 * A task has always named one person, and that stays: somebody has to answer for it. But work
 * is not always done alone - two go out to one installation, a job is carried by a handful of
 * people - and until now the second name had nowhere to go but the text.
 *
 * The link is deleted with its task and refused with its user: a task that is gone has nobody
 * working on it any more, while an account somebody is still counting on is not one to remove.
 */
class CreateTaskCollaborators extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        // create extension for full UUID support
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        $this->table('task_collaborators', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => Literal::from('uuid_generate_v4()'),
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('task_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'timestamp', [
                'timezone' => true,
                'default' => null,
                'null' => true,
            ])
            ->addColumn('created_by', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'timestamp', [
                'timezone' => true,
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified_by', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(['task_id', 'user_id'], ['unique' => true])
            ->addIndex(['user_id'])
            ->addForeignKey('task_id', 'tasks', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            // Left to raise rather than to cascade, so that removing an account somebody is
            // still counted on for is answered by the rule that asks about it.
            ->addForeignKey('user_id', 'users', 'id')
            ->addForeignKey('created_by', 'users', 'id')
            ->addForeignKey('modified_by', 'users', 'id')
            ->create();
    }
}
