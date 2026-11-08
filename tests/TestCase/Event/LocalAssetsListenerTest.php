<?php
declare(strict_types=1);

namespace App\Test\TestCase\Event;

use App\Event\LocalAssetsListener;
use App\Test\Traits\ControllerTestTrait;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The pages of other packages, whose layouts load everything from a CDN, and the application must not.
 */
#[CoversClass(LocalAssetsListener::class)]
class LocalAssetsListenerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'plugin.Queue.QueuedJobs',
        'plugin.Queue.QueueProcesses',
        'plugin.QueueScheduler.SchedulerRows',
    ];

    /**
     * A page of each layout that names a CDN, and more of those that load extra libraries.
     *
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'queue overview' => ['/admin/queue'],
            'queue statistics' => ['/admin/queue/queued-jobs/stats'],
            'schedule overview' => ['/admin/queue-scheduler'],
            'audit log overview' => ['/admin/audit-stash'],
            'audit log' => ['/admin/audit-stash/audit-logs'],
        ];
    }

    /**
     * The page loads nothing from anywhere else, and everything it loads is here.
     *
     * @param string $url The page.
     * @return void
     */
    #[DataProvider('pages')]
    public function testAPageLoadsEverythingFromHere(string $url): void
    {
        $this->login();

        $this->get($url);

        $this->assertResponseOk();
        $body = (string)$this->_response?->getBody();

        // what the browser fetches by itself, a link somebody clicks is another matter
        $this->assertSame(0, preg_match('~<(?:link|script)[^>]+(?:href|src)="https?://~', $body), $body);
        $this->assertSame(1, preg_match_all('~"/vendor/bootstrap/bootstrap\.min\.css"~', $body));

        preg_match_all('~"/vendor/([^"]+)"~', $body, $assets);

        foreach ($assets[1] as $asset) {
            $this->assertFileExists(WWW_ROOT . 'vendor' . DS . $asset);
        }
    }
}
