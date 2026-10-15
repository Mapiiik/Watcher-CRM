<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * An account notes, as a customer's login does, when and from where it last signed in, and when
 * and from where somebody last failed to.
 */
class UsersNoteDeniedSignIns extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('users')
            ->renameColumn('last_login', 'last_granted')
            ->update();

        $this->table('users')
            ->addColumn('last_granted_ip', 'inet', ['null' => true])
            ->addColumn('last_denied', 'timestamptimezone', ['null' => true])
            ->addColumn('last_denied_ip', 'inet', ['null' => true])
            ->update();
    }
}
