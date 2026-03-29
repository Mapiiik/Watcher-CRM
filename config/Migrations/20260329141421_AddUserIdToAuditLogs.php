<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddUserIdToAuditLogs extends BaseMigration
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
        $table->addColumn('user_id', 'uuid', [
            'default' => null,
            'null' => true,
        ]);
        $table->addIndex(['user_id']);
        $table->update();
    }
}
