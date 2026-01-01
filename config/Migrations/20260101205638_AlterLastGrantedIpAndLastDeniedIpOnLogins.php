<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlterLastGrantedIpAndLastDeniedIpOnLogins extends BaseMigration
{
    /**
     * Up Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-up-method
     *
     * @return void
     */
    public function up(): void
    {
        $this->execute(
            'ALTER TABLE logins
                ALTER COLUMN last_granted_ip DROP DEFAULT,
                ALTER COLUMN last_denied_ip DROP DEFAULT,
                ALTER COLUMN last_granted_ip TYPE inet
                    USING last_granted_ip::inet,
                ALTER COLUMN last_denied_ip TYPE inet
                    USING last_denied_ip::inet',
        );
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-down-method
     *
     * @return void
     */
    public function down(): void
    {
        $this->execute(
            'ALTER TABLE logins
                ALTER COLUMN last_granted_ip TYPE varchar(39),
                ALTER COLUMN last_denied_ip TYPE varchar(39)',
        );
    }
}
