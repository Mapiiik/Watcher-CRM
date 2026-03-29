<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RenameUsernameToUserDisplayOnAuditLogs extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('audit_logs');
        $table->renameColumn('username', 'user_display');
        $table->save();
    }
}
