<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\AppUser;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Model\Entity\AppUser Test Case
 */
#[UsesClass(AppUser::class)]
class AppUserTest extends TestCase
{
    /**
     * A user is shown by their name alone. Telling two people of the same name apart is what the
     * lists are for, and a cell in a row that already says which task it is has nothing to tell
     * apart.
     *
     * @return void
     * @link \App\Model\Entity\AppUser::_getName()
     */
    public function testAUserIsShownByTheirNameAlone(): void
    {
        $user = new AppUser([
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'username' => 'jnovak',
        ]);

        $this->assertSame('Jan Novak', $user->name);
    }

    /**
     * A user who has no name filled in is shown by their username rather than by nothing at all.
     *
     * @return void
     * @link \App\Model\Entity\AppUser::_getName()
     */
    public function testAUserWithoutANameIsShownByTheirUsername(): void
    {
        $user = new AppUser([
            'username' => 'jnovak',
        ]);

        $this->assertSame('jnovak', $user->name);
    }

    /**
     * Lists put the surname first, and name the account, so that two people sharing a name can be
     * told apart when one of them has to be picked.
     *
     * @return void
     * @link \App\Model\Entity\AppUser::_getNameForLists()
     */
    public function testListsPutTheSurnameFirstAndNameTheAccount(): void
    {
        $user = new AppUser([
            'first_name' => 'Jan',
            'last_name' => 'Novak',
            'username' => 'jnovak',
        ]);

        $this->assertSame('Novak Jan (jnovak)', $user->name_for_lists);
    }

    /**
     * The roles offered on the form are the ones the permissions are written for. A role that can be
     * picked but that nothing lets through, or one that is used but cannot be picked, would only
     * show up as somebody unable to do their work.
     *
     * @return void
     * @link \App\Model\Entity\AppUser::getRoleOptions()
     */
    public function testTheRolesOfferedAreTheOnesThePermissionsAreWrittenFor(): void
    {
        $roles = array_keys((new AppUser())->getRoleOptions());

        sort($roles);

        $this->assertSame([
            'admin',
            'api',
            'bookkeeper',
            'customer-service-technician',
            'network-manager',
            'network-technician',
            'sales-manager',
            'sales-representative',
            'user',
        ], $roles);
    }

    /**
     * A role is shown under the name it is offered by rather than under the value stored for it.
     *
     * @return void
     * @link \App\Model\Entity\AppUser::getRoleName()
     */
    public function testARoleIsShownUnderTheNameItIsOfferedBy(): void
    {
        $user = new AppUser();
        $user->role = 'network-technician';

        $this->assertSame('Network Technician', $user->getRoleName());
    }

    /**
     * A role nothing offers - one left behind by a rename, or written straight into the database -
     * is shown as it stands rather than as nothing at all.
     *
     * @return void
     * @link \App\Model\Entity\AppUser::getRoleName()
     */
    public function testARoleNothingOffersIsShownAsItStands(): void
    {
        $user = new AppUser();
        $user->role = 'warehouse-keeper';

        $this->assertSame('warehouse-keeper', $user->getRoleName());
    }
}
