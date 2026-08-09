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
