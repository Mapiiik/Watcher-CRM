<?php
declare(strict_types=1);

namespace App\Test\TestCase\CustomerPortal;

use App\CustomerPortal\Throttle;
use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\CustomerPortal\Throttle Test Case
 *
 * Each counter has to stop what it is meant to stop and nothing else, so that a customer is not
 * shut out by somebody else guessing from another place.
 */
#[CoversClass(Throttle::class)]
class ThrottleTest extends TestCase
{
    private const CACHE = 'customer_portal_throttle_test';

    /**
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Cache::setConfig(self::CACHE, ['className' => ArrayEngine::class]);
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::drop(self::CACHE);
        parent::tearDown();
    }

    /**
     * Failures of one login from one address block that pair, and only that pair.
     *
     * @return void
     */
    public function testFailuresBlockTheLoginFromThatAddress(): void
    {
        $throttle = new Throttle(3, 10, 20, 15, self::CACHE);

        for ($i = 0; $i < 3; $i++) {
            $this->assertFalse($throttle->blocked('Customer', '192.0.2.1'));
            $throttle->failed('Customer', '192.0.2.1');
        }

        $this->assertTrue($throttle->blocked(' customer ', '192.0.2.1'));
        $this->assertFalse($throttle->blocked('customer', '192.0.2.2'));
        $this->assertFalse($throttle->blocked('somebody else', '192.0.2.1'));
    }

    /**
     * Failures from one address over many logins block the address.
     *
     * @return void
     */
    public function testFailuresOverManyLoginsBlockTheAddress(): void
    {
        $throttle = new Throttle(3, 4, 20, 15, self::CACHE);

        foreach (['first', 'second', 'third', 'fourth'] as $login) {
            $throttle->failed($login, '192.0.2.1');
        }

        $this->assertTrue($throttle->blocked('fifth', '192.0.2.1'));
        $this->assertFalse($throttle->blocked('fifth', '192.0.2.2'));
    }

    /**
     * Failures of one login from many addresses block the login.
     *
     * @return void
     */
    public function testFailuresFromManyAddressesBlockTheLogin(): void
    {
        $throttle = new Throttle(3, 10, 4, 15, self::CACHE);

        foreach (['192.0.2.1', '192.0.2.2', '192.0.2.3', '192.0.2.4'] as $ip) {
            $throttle->failed('customer', $ip);
        }

        $this->assertTrue($throttle->blocked('customer', '192.0.2.5'));
        $this->assertFalse($throttle->blocked('somebody else', '192.0.2.5'));
    }

    /**
     * Signing in forgets the failures of the pair, but not those counted for the address.
     *
     * @return void
     */
    public function testSigningInForgetsOnlyThePair(): void
    {
        $throttle = new Throttle(2, 3, 20, 15, self::CACHE);

        $throttle->failed('customer', '192.0.2.1');
        $throttle->failed('customer', '192.0.2.1');
        $this->assertTrue($throttle->blocked('customer', '192.0.2.1'));

        $throttle->succeeded('customer', '192.0.2.1');
        $this->assertFalse($throttle->blocked('customer', '192.0.2.1'));

        $throttle->failed('somebody else', '192.0.2.1');
        $this->assertTrue($throttle->blocked('anybody', '192.0.2.1'));
    }

    /**
     * Addresses within one IPv6 /64 count together.
     *
     * @return void
     */
    public function testAnIpv6NetworkCountsAsOneAddress(): void
    {
        $throttle = new Throttle(2, 10, 20, 15, self::CACHE);

        $throttle->failed('customer', '2001:db8:1:2::1');
        $throttle->failed('customer', '2001:db8:1:2:ffff::9');

        $this->assertTrue($throttle->blocked('customer', '2001:db8:1:2::abcd'));
        $this->assertFalse($throttle->blocked('customer', '2001:db8:1:3::1'));
        $this->assertSame('2001:db8:1:2::/64', Throttle::network('2001:db8:1:2:3:4:5:6'));
        $this->assertSame('192.0.2.1', Throttle::network('192.0.2.1'));
    }
}
