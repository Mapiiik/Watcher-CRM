<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * What the accounts kept for CakeDC/Users and nothing reads any more.
 *
 * The failed sign-ins it counted are counted in the cache now, nobody registers or accepts terms,
 * and nobody signs in by a link sent by e-mail. Of an identity at another provider only what tells
 * it apart is kept, not the provider's tokens nor the profile it sent. Links for a new password
 * issued before were kept readable, and only their hashes are compared now, so they are cleared.
 */
class DropAccountColumnsNothingUses extends BaseMigration
{
    /**
     * Columns of the accounts nothing reads.
     *
     * @var list<string>
     */
    private const USER_COLUMNS = [
        'activation_date',
        'tos_date',
        'additional_data',
        'lockout_time',
        'login_token',
        'login_token_date',
        'token_send_requested',
    ];

    /**
     * Columns of the linked identities nothing reads.
     *
     * @var list<string>
     */
    private const SOCIAL_ACCOUNT_COLUMNS = [
        'avatar',
        'description',
        'link',
        'token',
        'token_secret',
        'token_expires',
        'data',
    ];

    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->hasTable('failed_password_attempts')) {
            $this->table('failed_password_attempts')->drop()->save();
        }

        $users = $this->table('users');
        foreach (self::USER_COLUMNS as $column) {
            $users->removeColumn($column);
        }
        $users->update();
        $this->execute('UPDATE users SET token = NULL, token_expires = NULL');

        $socialAccounts = $this->table('social_accounts');
        foreach (self::SOCIAL_ACCOUNT_COLUMNS as $column) {
            $socialAccounts->removeColumn($column);
        }
        $socialAccounts->update();
    }

    /**
     * Down Method.
     *
     * The columns come back empty, and the table of failed sign-ins without what it counted.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('failed_password_attempts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('created', 'timestamptimezone', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addForeignKey('user_id', 'users', 'id', ['constraint' => 'failed_password_attempts_user_id_fkey'])
            ->create();

        $this->table('users')
            ->addColumn('activation_date', 'timestamptimezone', ['null' => true])
            ->addColumn('tos_date', 'timestamptimezone', ['null' => true])
            ->addColumn('additional_data', 'text', ['null' => true])
            ->addColumn('lockout_time', 'timestamptimezone', ['null' => true])
            ->addColumn('login_token', 'string', ['limit' => 32, 'null' => true])
            ->addColumn('login_token_date', 'timestamptimezone', ['null' => true])
            ->addColumn('token_send_requested', 'boolean', ['default' => false, 'null' => false])
            ->addIndex(['login_token'], ['name' => 'users_login_token'])
            ->update();

        $this->table('social_accounts')
            ->addColumn('avatar', 'text', ['null' => true])
            ->addColumn('description', 'text', ['null' => true])
            ->addColumn('link', 'string', ['null' => true])
            ->addColumn('token', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('token_secret', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('token_expires', 'timestamptimezone', ['null' => true])
            ->addColumn('data', 'text', ['null' => true])
            ->update();
    }
}
