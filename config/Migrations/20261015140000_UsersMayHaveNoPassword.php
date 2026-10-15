<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * An account may have no password, which is how signing in with one is turned off: for clients
 * that only sign in with a key, and for people who sign in only through an identity provider.
 */
class UsersMayHaveNoPassword extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('users')
            ->changeColumn('password', 'string', ['null' => true])
            ->update();
    }

    /**
     * Down Method.
     *
     * An account without a password could not be stored any more, so it gets one nobody knows.
     *
     * @return void
     */
    public function down(): void
    {
        $this->getUpdateBuilder()
            ->update('users')
            ->set('password', '!')
            ->where(['password IS' => null])
            ->execute();
        $this->table('users')
            ->changeColumn('password', 'string', ['null' => false])
            ->update();
    }
}
