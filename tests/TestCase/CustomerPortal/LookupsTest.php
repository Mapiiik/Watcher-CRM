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
     * Counters start empty, in a minute that stands still.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        Cache::clear('default');
        Chronos::setTestNow('2026-10-12 10:00:15');
    }

    /**
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Chronos::setTestNow(null);
        parent::tearDown();
    }

    /**
     * A caller gets as many lookups as the limit lets in a minute, and its own count.
     *
     * @return void
     */
    public function testACallerIsRefusedOverTheLimitOfAMinute(): void
    {
        $lookups = new Lookups(2);

        $this->assertTrue($lookups->allow('tv-gateway'));
        $this->assertTrue($lookups->allow('tv-gateway'));
        $this->assertFalse($lookups->allow('tv-gateway'));
        $this->assertTrue($lookups->allow('another-service'));
    }

    /**
     * The next minute starts counting again.
     *
     * @return void
     */
    public function testTheNextMinuteCountsAfresh(): void
    {
        $lookups = new Lookups(1);
        $this->assertTrue($lookups->allow('tv-gateway'));
        $this->assertFalse($lookups->allow('tv-gateway'));

        Chronos::setTestNow('2026-10-12 10:01:00');

        $this->assertTrue($lookups->allow('tv-gateway'));
    }
}
