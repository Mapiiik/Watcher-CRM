<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * A session is held by the browser that signed in, so ending one takes a moment to compare it
 * with: whatever signed in before this no longer counts.
 */
class UsersKnowWhenTheirSessionsEnded extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('users')
            ->addColumn('sessions_valid_from', 'timestamp', [
                'default' => null,
                'null' => true,
                'timezone' => true,
            ])
            ->update();
    }
}
