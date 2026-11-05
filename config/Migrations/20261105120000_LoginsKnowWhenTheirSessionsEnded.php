<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * A token of the customer portal is held by nobody but the portal, so ending one takes a day to
 * compare it with: whatever was issued before this no longer counts.
 */
class LoginsKnowWhenTheirSessionsEnded extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('logins')
            ->addColumn('sessions_valid_from', 'timestamp', [
                'default' => null,
                'null' => true,
                'timezone' => true,
            ])
            ->update();
    }
}
