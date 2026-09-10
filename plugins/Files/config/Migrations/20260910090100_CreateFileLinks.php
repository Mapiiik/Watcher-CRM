<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Literal;

/**
 * Who has a file and why.
 *
 * Four coordinates say it: which record holds it (`model` and `foreign_key`), which document it
 * is (`document_type`), which variant of that document this is (`variant`), and where a document
 * runs to several pages, which page this one is (`position`).
 *
 * A variant rather than a state: the blank copy, the one we signed and the one that came back are
 * four different files standing side by side, not four stages of one, and nothing ever moves from
 * being one to being another.
 *
 * The values in `document_type` and `variant` belong to the application - here they are strings, so
 * that a plugin copied into another application does not have to be taught that one's vocabulary.
 *
 * Two tables rather than one because the same content can hang on several records, and letting go
 * of one of them must not take the bytes away while another still wants them.
 */
class CreateFileLinks extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // create extension for full UUID support
        $this->execute('CREATE EXTENSION IF NOT EXISTS "uuid-ossp";');

        $table = $this->table('file_links', ['id' => false, 'primary_key' => ['id']]);

        $table->addColumn('id', 'uuid', [
            'default' => Literal::from('uuid_generate_v4()'),
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('file_id', 'uuid', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('model', 'string', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('foreign_key', 'uuid', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('document_type', 'string', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('variant', 'string', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('position', 'integer', [
            'default' => 0,
            'limit' => null,
            'null' => false,
        ]);
        // What it was called where it came from. Kept as it arrived: a scan is filed under the
        // name the customer's own machine gave it, and renaming it loses the only clue to what
        // order the pages were in.
        $table->addColumn('name', 'string', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('meta', 'jsonb', [
            'default' => '{}',
            'null' => false,
        ]);
        $table->addColumn('created', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('created_by', 'uuid', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->addColumn('modified', 'timestamp', [
            'timezone' => true,
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified_by', 'uuid', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);

        // Everything a record has, and the order it reads in.
        $table->addIndex(['model', 'foreign_key']);
        $table->addIndex(['model', 'foreign_key', 'document_type', 'variant', 'position']);
        // Asked whenever the last use of some content is let go of.
        $table->addIndex(['file_id']);

        // Raised rather than cascaded: content is let go of by dropping the last thing that
        // wanted it, never by deleting the content out from under something that still does.
        $table->addForeignKey('file_id', 'files', 'id');
        $table->addForeignKey('created_by', 'users', 'id');
        $table->addForeignKey('modified_by', 'users', 'id');

        $table->create();

        // A document we made is in each group exactly once - which is what freezing it means:
        // once it is on file it is handed back rather than drawn again. Written as a partial
        // index rather than as a unique over the position as well, so that the pages of a scan
        // stay free to be reordered.
        $this->execute(
            'CREATE UNIQUE INDEX file_links_generated ON file_links (model, foreign_key, document_type, variant)'
            . " WHERE variant IN ('generated', 'generated-signed-by-us')",
        );
    }

    /**
     * Down Method.
     *
     * Written out rather than left to `change()`, which cannot take back an index it was handed
     * as plain SQL - it replays it forwards instead, and the rollback fails on a column that has
     * already gone. Dropping the table takes its indexes with it.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('file_links')->drop()->save();
    }
}
