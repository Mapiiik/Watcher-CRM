<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * The accounts are `Users` now, not `AppUsers`.
 *
 * The audit log names a record by the alias of its table, so what was logged under the old name
 * is renamed to stay in the history of the account. A session holds whoever signed in as an object
 * of the old class, which is gone, so the sessions go and everybody signs in again.
 */
class UsersAreNoLongerAppUsers extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->rename('AppUsers', 'Users');
        $this->getDeleteBuilder()->delete('sessions')->execute();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->rename('Users', 'AppUsers');
    }

    /**
     * Renames a source in the audit log, as a record's own and as its parent's.
     *
     * @param string $from The name it has.
     * @param string $to The name it gets.
     * @return void
     */
    private function rename(string $from, string $to): void
    {
        foreach (['source', 'parent_source'] as $column) {
            $this->getUpdateBuilder()
                ->update('audit_logs')
                ->set($column, $to)
                ->where([$column => $from])
                ->execute();
        }
    }
}
