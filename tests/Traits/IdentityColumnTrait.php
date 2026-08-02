<?php
declare(strict_types=1);

namespace App\Test\Traits;

/**
 * Helper for tests that save records into a table with an identity column.
 */
trait IdentityColumnTrait
{
    /**
     * Move the identity of the given column past what the fixtures inserted.
     *
     * Fixtures write identity columns with the values they carry, which leaves the identity itself
     * where it started - the next insert the application makes then collides with a fixture row.
     * A test that saves a record into such a table has to move it along first.
     *
     * @param string $table Table name.
     * @param string $column Identity column.
     * @return void
     */
    protected function advanceIdentity(string $table, string $column): void
    {
        $this->getTableLocator()->get($table)->getConnection()->execute(
            'SELECT setval('
            . "pg_get_serial_sequence('$table', '$column'), "
            . "(SELECT MAX($column) FROM $table)"
            . ')',
        );
    }
}
