<?php
declare(strict_types=1);

namespace App\Test\TestCase\CustomerPortal;

use App\CustomerPortal\Lookups;
use Cake\Cache\Cache;
use Cake\Chronos\Chronos;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\CustomerPortal\Lookups Test Case
 */
#[CoversClass(Lookups::class)]
class LookupsTest extends TestCase
{
    /**
     * What the clock was stopped at before this class moved it.
     */
    private ?Chronos $clock = null;

    /**
     * Counters start empty, in a minute that stands still.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear('default');
        $this->clock = Chronos::getTestNow();
        Chronos::setTestNow('2026-10-12 10:00:15');
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Chronos::setTestNow($this->clock);
        parent::tearDown();
    }

    /**
     * A caller gets as many addresses as the limit lets in a minute, and its own count.
     *
     * @return void
     */
    public function testACallerIsRefusedOverTheLimitOfAMinute(): void
    {
        $lookups = new Lookups(2);

        $this->assertTrue($lookups->allow('tv-gateway', '192.0.2.1'));
        $this->assertTrue($lookups->allow('tv-gateway', '192.0.2.2'));
        $this->assertFalse($lookups->allow('tv-gateway', '192.0.2.3'));
        $this->assertTrue($lookups->allow('another-service', '192.0.2.3'));
    }

    /**
     * Asking about one address again and again is no way through a range, so it uses up nothing.
     * A visitor reloading a page would otherwise lock out everybody behind the same caller.
     *
     * @return void
     */
    public function testAnAddressAskedAboutAgainIsNotCounted(): void
    {
        $lookups = new Lookups(2);

        foreach (range(1, 40) as $ignored) {
            $this->assertTrue($lookups->allow('tv-gateway', '192.0.2.1'));
        }
        $this->assertTrue($lookups->allow('tv-gateway', '192.0.2.2'));
        $this->assertFalse($lookups->allow('tv-gateway', '192.0.2.3'));
    }

    /**
     * Whoever got an answer keeps getting it for the rest of the minute, over the limit too.
     *
     * @return void
     */
    public function testAnAddressAlreadyLetThroughStaysLetThrough(): void
    {
        $lookups = new Lookups(1);

        $this->assertTrue($lookups->allow('tv-gateway', '192.0.2.1'));
        $this->assertFalse($lookups->allow('tv-gateway', '192.0.2.2'));
        $this->assertTrue($lookups->allow('tv-gateway', '192.0.2.1'));
        $this->assertFalse($lookups->allow('tv-gateway', '192.0.2.2'));
    }

    /**
     * The next minute starts counting again.
     *
     * @return void
     */
    public function testTheNextMinuteCountsAfresh(): void
    {
        $lookups = new Lookups(1);
        $this->assertTrue($lookups->allow('tv-gateway', '192.0.2.1'));
        $this->assertFalse($lookups->allow('tv-gateway', '192.0.2.2'));

        Chronos::setTestNow('2026-10-12 10:01:00');

        $this->assertTrue($lookups->allow('tv-gateway', '192.0.2.2'));
    }
}
