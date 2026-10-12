<?php
declare(strict_types=1);

namespace Access\Test\TestCase\Authentication;

use Access\Authentication\LoginThrottle;
use Cake\Cache\Cache;
use Cake\Cache\Engine\ArrayEngine;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * Access\Authentication\LoginThrottle Test Case
 */
#[UsesClass(LoginThrottle::class)]
class LoginThrottleTest extends TestCase
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        Cache::drop('login_throttle_test');
        Cache::setConfig('login_throttle_test', ['className' => ArrayEngine::class]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::drop('login_throttle_test');

        parent::tearDown();
    }

    /**
     * An account is refused from an address it failed from too often, and only from there.
     *
     * @return void
     */
    public function testAnAccountIsRefusedOnlyFromWhereItFailed(): void
    {
        $throttle = new LoginThrottle(3, 100, 15, 'login_throttle_test');

        $throttle->failed('jnovak', '192.0.2.1');
        $throttle->failed('JNovak ', '192.0.2.1');
        $this->assertFalse($throttle->blocked('jnovak', '192.0.2.1'));
        $throttle->failed('jnovak', '192.0.2.1');

        $this->assertTrue($throttle->blocked('jnovak', '192.0.2.1'));
        $this->assertFalse($throttle->blocked('jnovak', '192.0.2.2'));
        $this->assertFalse($throttle->blocked('pdvorak', '192.0.2.1'));
    }

    /**
     * An address trying many accounts is refused for all of them.
     *
     * @return void
     */
    public function testAnAddressTryingManyAccountsIsRefused(): void
    {
        $throttle = new LoginThrottle(100, 3, 15, 'login_throttle_test');

        foreach (['jnovak', 'pdvorak', 'mcerna'] as $account) {
            $throttle->failed($account, '192.0.2.1');
        }

        $this->assertTrue($throttle->blocked('kdalsi', '192.0.2.1'));
    }

    /**
     * Signing in forgets the failures of the account from that address, but not those of the
     * address, so that one good password does not open the door to guessing at others.
     *
     * @return void
     */
    public function testSigningInForgetsOnlyTheFailuresOfTheAccount(): void
    {
        $throttle = new LoginThrottle(2, 3, 15, 'login_throttle_test');
        $throttle->failed('jnovak', '192.0.2.1');
        $throttle->failed('jnovak', '192.0.2.1');
        $throttle->failed('pdvorak', '192.0.2.1');

        $throttle->succeeded('jnovak', '192.0.2.1');

        $this->assertTrue($throttle->blocked('jnovak', '192.0.2.1'));
        $throttle = new LoginThrottle(2, 4, 15, 'login_throttle_test');
        $this->assertFalse($throttle->blocked('jnovak', '192.0.2.1'));
    }

    /**
     * IPv6 addresses count by their /64.
     *
     * @return void
     */
    public function testIpv6AddressesCountByTheirSlash64(): void
    {
        $this->assertSame('2001:db8:1:2::/64', LoginThrottle::network('2001:db8:1:2:aaaa::1'));
        $this->assertSame('192.0.2.1', LoginThrottle::network('192.0.2.1'));
    }
}
