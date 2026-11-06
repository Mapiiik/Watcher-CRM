<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * The trash: links somebody removed, until they come back or their time is up.
 *
 * A table of its own rather than a flag on `file_links`, because a row there means something to
 * everything that reads it. The unique index on generated documents is left out on purpose: the
 * trash may hold several generations of one document.
 */
class CreateRemovedFileLinks extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('removed_file_links', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('file_id', 'uuid', ['null' => false])
            ->addColumn('model', 'string', ['null' => false])
            ->addColumn('foreign_key', 'uuid', ['null' => false])
            ->addColumn('document_type', 'string', ['null' => false])
            ->addColumn('variant', 'string', ['null' => false])
            ->addColumn('position', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('name', 'string', ['null' => true])
            ->addColumn('meta', 'json', ['default' => '{}', 'null' => false])
            ->addColumn('documentation', 'json', ['null' => true])
            ->addColumn('created', 'timestamptimezone', ['null' => true])
            ->addColumn('created_by', 'uuid', ['null' => true])
            ->addColumn('modified', 'timestamptimezone', ['null' => true])
            ->addColumn('modified_by', 'uuid', ['null' => true])
            ->addColumn('removed', 'timestamptimezone', ['null' => false])
            ->addColumn('removed_by', 'uuid', ['null' => true])
            ->addIndex(['file_id'], ['name' => 'removed_file_links_file_id'])
            ->addIndex(['removed'], ['name' => 'removed_file_links_removed'])
            ->addIndex(['model', 'foreign_key'], ['name' => 'removed_file_links_model_foreign_key'])
            ->addForeignKey('file_id', 'files', 'id', ['constraint' => 'removed_file_links_file_id_fkey'])
            ->addForeignKey('created_by', 'users', 'id', ['constraint' => 'removed_file_links_created_by_fkey'])
            ->addForeignKey('modified_by', 'users', 'id', ['constraint' => 'removed_file_links_modified_by_fkey'])
            ->addForeignKey('removed_by', 'users', 'id', ['constraint' => 'removed_file_links_removed_by_fkey'])
            ->create();
    }
}
