<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Validation;

use App\Model\Validation\NetworkValidation;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * App\Model\Validation\NetworkValidation Test Case
 */
#[CoversClass(NetworkValidation::class)]
class NetworkValidationTest extends TestCase
{
    /**
     * An address may carry a mask, which is how the devices report most of theirs.
     *
     * @param string $value The value.
     * @return void
     */
    #[TestWith(['192.0.2.7'])]
    #[TestWith(['192.0.2.7/24'])]
    #[TestWith(['192.0.2.7/32'])]
    #[TestWith(['0.0.0.0/0'])]
    #[TestWith(['2001:db8::1'])]
    #[TestWith(['2001:db8::1/64'])]
    #[TestWith(['::ffff:192.0.2.7'])]
    #[TestWith(['2001:db8::/128'])]
    public function testAnAddressIsTaken(string $value): void
    {
        $this->assertTrue(NetworkValidation::inet($value));
    }

    /**
     * @param mixed $value The value.
     * @return void
     */
    #[TestWith(['10.0.0.256'])]
    #[TestWith(['10.0.0'])]
    #[TestWith(['192.0.2.7/33'])]
    #[TestWith(['192.0.2.7/'])]
    #[TestWith(['192.0.2.7/-1'])]
    #[TestWith(['192.0.2.7/2x'])]
    #[TestWith(['192.0.2.7/24/24'])]
    #[TestWith(['2001:db8::1/129'])]
    #[TestWith(['2001:db8:::1'])]
    #[TestWith(['something'])]
    #[TestWith([''])]
    #[TestWith([null])]
    #[TestWith([192])]
    #[TestWith([['192.0.2.7']])]
    public function testWhatIsNoAddressIsRefused(mixed $value): void
    {
        $this->assertFalse(NetworkValidation::inet($value));
    }

    /**
     * @param string $value The value.
     * @return void
     */
    #[TestWith(['10.0.0.0/7'])]
    #[TestWith(['2001:db8::/31'])]
    #[TestWith(['2001:DB8::/32'])]
    #[TestWith(['2001:0db8:0000::/48'])]
    #[TestWith(['192.0.2.0/24'])]
    #[TestWith(['192.0.2.0/29'])]
    #[TestWith(['192.0.2.8/29'])]
    #[TestWith(['192.0.2.7/32'])]
    #[TestWith(['192.0.2.7'])]
    #[TestWith(['10.0.0.0/8'])]
    #[TestWith(['0.0.0.0/0'])]
    #[TestWith(['128.0.0.0/1'])]
    #[TestWith(['2001:db8::/32'])]
    #[TestWith(['2001:db8:0:1::/64'])]
    #[TestWith(['2001:db8::1/128'])]
    #[TestWith(['2001:db8::1'])]
    public function testANetworkIsTaken(string $value): void
    {
        $this->assertTrue(NetworkValidation::cidr($value));
    }

    /**
     * Bits set beyond the mask are what PostgreSQL refuses in a `cidr`.
     *
     * @param string $value The value.
     * @return void
     */
    #[TestWith(['88.1.2.3/29'])]
    #[TestWith(['192.0.2.1/24'])]
    #[TestWith(['192.0.2.4/29'])]
    #[TestWith(['11.0.0.0/7'])]
    #[TestWith(['0.0.0.1/0'])]
    #[TestWith(['2001:db8::1/64'])]
    #[TestWith(['2001:db9::/31'])]
    #[TestWith(['10.0.0.256/24'])]
    public function testAnAddressInsideANetworkIsNotOne(string $value): void
    {
        $this->assertFalse(NetworkValidation::cidr($value));
    }

    /**
     * @param string $value The value.
     * @return void
     */
    #[TestWith(['08:00:2b:01:02:03'])]
    #[TestWith(['08-00-2b-01-02-03'])]
    #[TestWith(['08002b:010203'])]
    #[TestWith(['08002b-010203'])]
    #[TestWith(['0800.2b01.0203'])]
    #[TestWith(['0800-2b01-0203'])]
    #[TestWith(['08002b010203'])]
    #[TestWith(['AA:BB:CC:DD:EE:FF'])]
    public function testAHardwareAddressIsTakenInAnyOfItsForms(string $value): void
    {
        $this->assertTrue(NetworkValidation::macaddr($value));
    }

    /**
     * @param mixed $value The value.
     * @return void
     */
    #[TestWith(['08:00:2b:01:02'])]
    #[TestWith(['08:00:2b:01:02:03:04'])]
    #[TestWith(['08:00-2b:01-02:03'])]
    #[TestWith(['0800.2b01-0203'])]
    #[TestWith(['08:00:2b:01:02:0g'])]
    #[TestWith(["08:00:2b:01:02:03\n"])]
    #[TestWith([''])]
    #[TestWith([null])]
    public function testWhatIsNoHardwareAddressIsRefused(mixed $value): void
    {
        $this->assertFalse(NetworkValidation::macaddr($value));
    }
}
