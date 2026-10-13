<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * What signing in with a one-time code needs beyond the secret itself.
 *
 * A code is accepted once: the time step of the last one used is kept, and a code from the same
 * step or an earlier one is refused. Recovery codes let somebody who lost their phone sign in, each
 * one once, kept only as a hash.
 *
 * Secrets that were never confirmed with a code are cleared, so that the next one is created anew
 * rather than taken over from a time when it may have been seen.
 */
class AddOneTimePasswordRecovery extends BaseMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('users')
            ->addColumn('otp_last_timestep', 'biginteger', [
                'default' => null,
                'null' => true,
            ])
            ->update();

        $this->table('recovery_codes', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('code_hash', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('used', 'timestamp', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('created', 'timestamp', [
                'default' => null,
                'null' => false,
            ])
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', [
                'update' => 'CASCADE',
                'delete' => 'CASCADE',
            ])
            ->create();

        $this->execute('UPDATE users SET secret = NULL WHERE secret_verified IS NOT TRUE');
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('recovery_codes')->drop()->save();

        $this->table('users')
            ->removeColumn('otp_last_timestep')
            ->update();
    }
}
