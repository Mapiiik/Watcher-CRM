<?php
declare(strict_types=1);

namespace App\Test\Traits;

use Cake\Core\Configure;

/**
 * Shared setup for the controller integration tests.
 *
 * Every controller sits behind the authentication, so a test that does not log in only ever proves
 * that the login redirect works. The record ids are looked up rather than written down, because not
 * every fixture assigns one.
 */
trait ControllerTestTrait
{
    /**
     * Log a user of the given role in.
     *
     * @param string $role Role to act as; the default sees everything.
     * @return void
     */
    protected function login(string $role = 'admin'): void
    {
        /** @var \App\Model\Table\AppUsersTable $usersTable */
        $usersTable = $this->getTableLocator()->get(Configure::read('Users.table', 'Users'));

        $user = $usersTable->newEmptyEntity();
        $user->username = 'tester';
        $user->role = $role;
        $user->active = true;

        $this->session(['Auth' => $user]);
    }

    /**
     * Move the identity of the given column past what the fixtures inserted.
     *
     * Fixtures write identity columns with the values they carry, which leaves the identity itself
     * where it started - the next insert the application makes then collides with a fixture row.
     * A test that saves a record with such a column has to move it along first.
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

    /**
     * Primary key of a record the fixtures put in the given table.
     *
     * @param string $table Table alias, plugin prefixed where the table belongs to one.
     * @return string
     */
    protected function firstId(string $table): string
    {
        $tableObject = $this->getTableLocator()->get($table);
        // the radius tables are named after what the radius server expects rather than after our
        // own conventions, so the key is not always called `id`
        $primaryKey = $tableObject->getPrimaryKey();
        assert(is_string($primaryKey));

        $id = $tableObject
            ->find()
            ->orderByAsc($primaryKey)
            ->firstOrFail()
            ->get($primaryKey);

        return (string)$id;
    }
}
