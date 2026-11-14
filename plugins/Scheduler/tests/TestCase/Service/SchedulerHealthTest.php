<?php
declare(strict_types=1);

namespace Scheduler\Test\TestCase\Service;

use App\Test\Traits\ConfigureTestTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use QueueScheduler\Command\RunCommand;
use Scheduler\Service\SchedulerHealth;

/**
 * Scheduler\Service\SchedulerHealth Test Case
 *
 * Only the heartbeat here: the jobs and the rows are what the check reports, and its test goes
 * through them.
 */
#[CoversClass(SchedulerHealth::class)]
class SchedulerHealthTest extends TestCase
{
    use ConfigureTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // the plugin is not booted in a test that sends no request, so its settings are not loaded
        $this->withConfigure(['Scheduler.silentDispatcherAfter' => 600]);
        Cache::clear();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Cache::clear();
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * An empty cache knows nothing, and nothing known is not a dispatcher gone quiet.
     *
     * @return void
     */
    public function testNoHeartbeatIsNeitherATimeNorSilence(): void
    {
        $health = new SchedulerHealth();

        $this->assertNull($health->lastRun());
        $this->assertFalse($health->isQuiet());
    }

    /**
     * Whatever else might sit under the key is not a time.
     *
     * @return void
     */
    public function testAHeartbeatThatIsNotATimeIsNone(): void
    {
        Cache::write(RunCommand::HEARTBEAT_KEY, 'a minute ago');

        $this->assertNull((new SchedulerHealth())->lastRun());
    }

    /**
     * A cache that cannot be read is no more a reason for alarm than an empty one.
     *
     * @return void
     */
    public function testACacheThatCannotBeReadSaysNothing(): void
    {
        $this->withConfigure(['QueueScheduler.cacheConfig' => 'not_configured']);

        $health = new SchedulerHealth();

        $this->assertNull($health->lastRun());
        $this->assertFalse($health->isQuiet());
    }

    /**
     * A pass a minute ago is a dispatcher at work, one an hour ago is not.
     *
     * @return void
     */
    public function testTheDispatcherIsQuietOnlyAfterTheLimit(): void
    {
        $health = new SchedulerHealth();

        Cache::write(RunCommand::HEARTBEAT_KEY, time() - 60);
        $this->assertFalse($health->isQuiet());

        Cache::write(RunCommand::HEARTBEAT_KEY, time() - 3600);
        $this->assertTrue($health->isQuiet());
        $this->assertSame(
            ['The dispatcher last ran at ' . $health->lastRun() . '.'],
            $health->problems(),
        );
    }

    /**
     * The time of the last pass is told in the time of the installation, not in UTC.
     *
     * @return void
     */
    public function testTheLastRunIsInTheTimeZoneOfTheInstallation(): void
    {
        $was = date_default_timezone_get();
        date_default_timezone_set('Europe/Prague');

        try {
            $beat = time() - 60;
            Cache::write(RunCommand::HEARTBEAT_KEY, $beat);

            $lastRun = (new SchedulerHealth())->lastRun();

            $this->assertNotNull($lastRun);
            $this->assertSame($beat, $lastRun->getTimestamp());
            $this->assertSame('Europe/Prague', $lastRun->getTimezone()->getName());
        } finally {
            date_default_timezone_set($was);
        }
    }
}
