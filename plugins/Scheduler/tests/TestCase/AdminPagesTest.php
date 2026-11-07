<?php
declare(strict_types=1);

namespace Scheduler\Test\TestCase;

use App\Test\Traits\ControllerTestTrait;
use Cake\Command\VersionCommand;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use Scheduler\Event\LocalAssetsListener;
use Scheduler\Service\Schedule;

/**
 * The pages of the queue and of the schedule.
 *
 * Whoever may open them may run any command of the application, so who that is comes first. They
 * also come from a library that loads everything from a CDN, and the application must not.
 */
#[UsesClass(LocalAssetsListener::class)]
class AdminPagesTest extends TestCase
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
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * The pages to go through.
     *
     * @return array<string, array{string}>
     */
    public static function pages(): array
    {
        return [
            'schedule overview' => ['/admin/queue-scheduler'],
            'rows of the schedule' => ['/admin/queue-scheduler/scheduler-rows'],
            'new row' => ['/admin/queue-scheduler/scheduler-rows/add'],
            'queue overview' => ['/admin/queue'],
            'runs' => ['/admin/queue/queued-jobs'],
            'statistics' => ['/admin/queue/queued-jobs/stats'],
        ];
    }

    /**
     * An administrator gets the page, with nothing loaded from anywhere else.
     *
     * @param string $url The page.
     * @return void
     * @link \Scheduler\Event\LocalAssetsListener::useLocalAssets()
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
        $this->assertSame(1, preg_match_all('~/scheduler/vendor/bootstrap/bootstrap\.min\.css~', $body));

        preg_match_all('~/scheduler/vendor/([^"]+)"~', $body, $assets);

        foreach ($assets[1] as $asset) {
            $this->assertFileExists(Plugin::path('Scheduler') . 'webroot/vendor/' . $asset);
        }
    }

    /**
     * The pages of a row and of a job, which the empty ones say nothing about.
     *
     * @return void
     */
    public function testAnAdministratorReadsARowAndItsJob(): void
    {
        $this->login();

        $description = ['command' => VersionCommand::class, 'frequency' => '0 1 * * *'];
        (new Schedule())->addMissing(['Example' => $description]);

        /** @var \QueueScheduler\Model\Entity\SchedulerRow $row */
        $row = $this->getTableLocator()->get('QueueScheduler.SchedulerRows')
            ->find()
            ->where(['name' => 'Example'])
            ->firstOrFail();

        /** @var \Queue\Model\Table\QueuedJobsTable $jobs */
        $jobs = $this->getTableLocator()->get('Queue.QueuedJobs');
        $job = $jobs->createJob('QueueScheduler.CommandExecute', ['class' => VersionCommand::class, 'args' => []]);

        foreach (
            [
                '/admin/queue-scheduler/scheduler-rows/view/' . $row->id,
                '/admin/queue-scheduler/scheduler-rows/edit/' . $row->id,
                '/admin/queue/queued-jobs/view/' . $job->id,
            ] as $url
        ) {
            $this->get($url);

            $this->assertResponseOk();
        }
    }

    /**
     * A row is switched on through the form, and waits for its time like from the console.
     *
     * @return void
     */
    public function testAnAdministratorSwitchesARowOn(): void
    {
        $this->login();

        (new Schedule())->addMissing(['Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *']]);

        $rows = $this->getTableLocator()->get('QueueScheduler.SchedulerRows');
        $rows->updateAll(['next_run' => DateTime::now()->subDays(2)], ['name' => 'Example']);

        /** @var \QueueScheduler\Model\Entity\SchedulerRow $row */
        $row = $rows->find()->where(['name' => 'Example'])->firstOrFail();

        // not a fixture, and adding the row above wrote to it as well
        $rows->getConnection()->execute('DELETE FROM audit_logs');

        // the application dresses the table when it starts, and this one was made before that
        $this->getTableLocator()->clear();

        $this->post('/admin/queue-scheduler/scheduler-rows/edit/' . $row->id, [
            'name' => 'Example',
            'type' => $row->type,
            'content' => $row->content,
            'frequency' => $row->frequency,
            'enabled' => '1',
        ]);

        $this->assertRedirectContains('/admin/queue-scheduler/scheduler-rows/view/');

        /** @var \QueueScheduler\Model\Entity\SchedulerRow $saved */
        $saved = $this->getTableLocator()->get('QueueScheduler.SchedulerRows')->get($row->id);

        $this->assertTrue($saved->enabled);
        $this->assertTrue($saved->next_run?->isFuture());

        // who changed the schedule is on record
        /** @var \Cake\Datasource\EntityInterface $entry */
        $entry = $this->getTableLocator()->get('AuditStash.AuditLogs')->find()->firstOrFail();
        $this->assertSame('update', $entry->get('type'));
        $this->assertStringContainsString('"enabled"', (string)json_encode($entry->get('changed')));
    }

    /**
     * The form as the page renders it gets past the form protection, tokens and all. A test that
     * posts by itself is let through whatever the page would have sent.
     *
     * @return void
     */
    public function testTheFormOfARowPassesTheFormProtection(): void
    {
        $this->login();

        (new Schedule())->addMissing(['Example' => ['command' => VersionCommand::class, 'frequency' => '0 1 * * *']]);

        /** @var \QueueScheduler\Model\Entity\SchedulerRow $row */
        $row = $this->getTableLocator()->get('QueueScheduler.SchedulerRows')
            ->find()
            ->where(['name' => 'Example'])
            ->firstOrFail();
        $url = '/admin/queue-scheduler/scheduler-rows/edit/' . $row->id;

        $this->get($url);
        $this->assertResponseOk();

        $data = $this->formOf((string)$this->_response?->getBody());
        $data['frequency'] = '0 2 * * *';

        // the test brings a CSRF token of its own, this one is about the form protection
        unset($data['_csrfToken']);

        $this->_securityToken = false;
        $this->post($url, $data);

        $this->assertRedirectContains('/admin/queue-scheduler/scheduler-rows/view/');
    }

    /**
     * What the browser would send from the form of a page.
     *
     * @param string $html The page.
     * @return array<string, mixed>
     */
    private function formOf(string $html): array
    {
        $document = new DOMDocument();
        $document->loadHTML($html, LIBXML_NOERROR);
        $xpath = new DOMXPath($document);

        $data = [];

        foreach ($xpath->query('//form[.//input[@name="frequency"]]//input') ?: [] as $input) {
            if ($input instanceof DOMElement && $input->getAttribute('name') !== '') {
                $checkable = in_array($input->getAttribute('type'), ['checkbox', 'radio'], true);

                if (!$checkable || $input->hasAttribute('checked')) {
                    parse_str(
                        rawurlencode($input->getAttribute('name')) . '=' . rawurlencode($input->getAttribute('value')),
                        $field,
                    );
                    $data = array_replace_recursive($data, $field);
                }
            }
        }

        foreach ($xpath->query('//form[.//input[@name="frequency"]]//textarea') ?: [] as $area) {
            if ($area instanceof DOMElement) {
                $data[$area->getAttribute('name')] = $area->textContent;
            }
        }

        foreach ($xpath->query('//form[.//input[@name="frequency"]]//select') ?: [] as $select) {
            if ($select instanceof DOMElement) {
                $chosen = $xpath->evaluate('string(.//option[@selected]/@value)', $select);
                $data[$select->getAttribute('name')] = (string)$chosen;
            }
        }

        return $data;
    }

    /**
     * Nobody else gets in, not even a manager.
     *
     * @param string $url The page.
     * @return void
     */
    #[DataProvider('pages')]
    public function testOnlyAnAdministratorGetsIn(string $url): void
    {
        foreach (['manager', 'user'] as $role) {
            $this->login($role);

            $this->get($url);

            $this->assertRedirect('/');
        }
    }

    /**
     * The gate the plugins ask for themselves stands behind the permissions, so no page reaches it
     * with anybody but an administrator.
     *
     * @return void
     */
    public function testTheGatesOfThePluginsLetOnlyAnAdministratorIn(): void
    {
        $this->get('/login');

        foreach (['Queue.adminAccess', 'QueueScheduler.adminAccess'] as $gate) {
            $allows = Configure::read($gate);

            $this->assertTrue($allows((new ServerRequest())->withAttribute('identity', ['role' => 'admin'])));
            $this->assertFalse($allows((new ServerRequest())->withAttribute('identity', ['role' => 'manager'])));
            $this->assertFalse($allows(new ServerRequest()));
        }
    }

    /**
     * Without an account the page is the login.
     *
     * @return void
     */
    public function testTheAnonymousAreSentToSignIn(): void
    {
        $this->get('/admin/queue-scheduler');

        $this->assertRedirectContains('/login');
    }
}
