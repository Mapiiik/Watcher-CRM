<?php
declare(strict_types=1);

namespace App\Test\Traits;

/**
 * Helper for tests that save records into a table whose key is handed out by a sequence.
 */
trait IdentityColumnTrait
{
    /**
     * Move the sequence behind the given column past what the fixtures inserted.
     *
     * Fixtures write such columns with the values they carry and leave the sequence where it
     * started, so the next insert the application makes collides with a fixture row. A test that
     * saves a record into such a table has to move it along first.
     *
     * @param string $table Table alias, plugin prefixed where the table belongs to one.
     * @param string $column Column the sequence stands behind.
     * @return void
     */
    protected function advanceIdentity(string $table, string $column): void
    {
        // the alias is not always the table's name, and a plugin's table is not always on the
        // connection everything else is on - both have to come from the table itself
        $tableObject = $this->getTableLocator()->get($table);
        $name = $tableObject->getTable();
        $connection = $tableObject->getConnection();

        $sequence = $connection
            ->execute('SELECT pg_get_serial_sequence(:table, :column)', ['table' => $name, 'column' => $column])
            ->fetchColumn(0);

        if (!is_string($sequence)) {
            // that only answers for a sequence the column owns, which an identity column does and
            // a separately declared one - the radius schema's kind - does not, so for those the
            // name has to be read out of the default
            $default = $connection
                ->execute(
                    'SELECT column_default FROM information_schema.columns'
                    . ' WHERE table_name = :table AND column_name = :column',
                    ['table' => $name, 'column' => $column],
                )
                ->fetchColumn(0);

            if (is_string($default) && preg_match("/nextval\('([^']+)'/", $default, $matches) === 1) {
                $sequence = $matches[1];
            }
        }

        if (!is_string($sequence)) {
            return;
        }

        $connection->execute(
            "SELECT setval('$sequence', coalesce((SELECT MAX($column) FROM $name), 1))",
        );
    }
}
