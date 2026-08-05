<?php
declare(strict_types=1);

namespace App\Test\Traits;

use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;

/**
 * Shared setup for the controller integration tests.
 *
 * Every controller sits behind the authentication, so a test that does not log in only ever proves
 * that the login redirect works. The record ids are looked up rather than written down, because not
 * every fixture assigns one.
 */
trait ControllerTestTrait
{
    use IdentityColumnTrait;

    /**
     * Log a user of the given role in.
     *
     * The setting is not loaded under the test environment, so the fallback is what actually
     * applies - and it has to name the table the application uses. `Users` would be resolved to a
     * generic table and cached under that alias, which any association of that name then clashes
     * with.
     *
     * @param string $role Role to act as; the default sees everything.
     * @return void
     */
    protected function login(string $role = 'admin'): void
    {
        /** @var \App\Model\Table\AppUsersTable $usersTable */
        $usersTable = $this->getTableLocator()->get(Configure::read('Users.table', 'AppUsers'));

        $user = $usersTable->newEmptyEntity();
        $user->username = 'tester';
        $user->role = $role;
        $user->active = true;

        $this->session(['Auth' => $user]);
    }

    /**
     * The ids a table holds, to be handed to {@see addedRecord()} afterwards.
     *
     * @param string $table Table alias.
     * @return array<string>
     */
    protected function idsIn(string $table): array
    {
        /** @var array<string> $ids */
        $ids = $this->getTableLocator()->get($table)->find()->all()->extract('id')->toList();

        return $ids;
    }

    /**
     * The record an action has just added, found by not having been there before.
     *
     * Asking for it by a value the request carried would only work where the table has a field
     * distinctive enough to search by, and would say nothing about tables whose every column is
     * shared with the fixtures.
     *
     * @param string $table Table alias.
     * @param array<string> $idsBefore What {@see idsIn()} returned before the action.
     * @return \Cake\Datasource\EntityInterface
     */
    protected function addedRecord(string $table, array $idsBefore): EntityInterface
    {
        $query = $this->getTableLocator()->get($table)->find();
        if ($idsBefore !== []) {
            $query->where(['id NOT IN' => $idsBefore]);
        }

        return $query->firstOrFail();
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
