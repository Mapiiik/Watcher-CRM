<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ErrorReport;
use App\Test\Traits\ConfigureTestTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Service\ErrorReport Test Case
 *
 * What matters here is the deployment that has nobody configured to be told. Reporting a failure
 * used to throw one of its own on such a deployment, which lost the failure worth reporting.
 * The other thing worth pinning is that a failure and a report are told apart: whoever is on call
 * is not sent the overnight paperwork, and whoever asked for the reports is not woken.
 */
#[UsesClass(ErrorReport::class)]
class ErrorReportTest extends TestCase
{
    use ConfigureTestTrait;
    use EmailTrait;

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        $this->restoreConfigure();

        parent::tearDown();
    }

    /**
     * Everybody configured is told.
     *
     * @return void
     * @link \App\Service\ErrorReport::send()
     */
    public function testSendReachesEverybodyConfigured(): void
    {
        $this->withConfigure(['Report.errorEmails' => ['first@example.com', 'second@example.com']]);

        $this->assertTrue(ErrorReport::send('Something failed', 'This is what is known about it.'));

        $this->assertMailCount(1);
        $this->assertMailSentTo('first@example.com');
        $this->assertMailSentTo('second@example.com');
        $this->assertMailContains('This is what is known about it.');
    }

    /**
     * Nobody configured leaves the report unsent rather than raising a failure of its own.
     *
     * @return void
     * @link \App\Service\ErrorReport::send()
     */
    public function testSendWithNobodyConfiguredIsNotAFailure(): void
    {
        $this->withConfigure(['Report.errorEmails' => []]);

        $this->assertFalse(ErrorReport::send('Something failed', 'This is what is known about it.'));

        $this->assertNoMailSent();
    }

    /**
     * A failure goes to the address of its own, and to nobody else. Whoever asked to be sent the
     * application's reports did not ask to be told that a nightly command fell over.
     *
     * @return void
     * @link \App\Service\ErrorReport::send()
     */
    public function testSendLeavesTheReportAddressesAlone(): void
    {
        $this->withConfigure([
            'Report.errorEmails' => [],
            'Report.emails' => ['reports@example.com'],
        ]);

        $this->assertFalse(ErrorReport::send('Something failed', 'This is what is known about it.'));

        $this->assertNoMailSent();
    }

    /**
     * The recipients are read from the configuration, which is where the environment was made
     * sense of - and nowhere else.
     *
     * @return void
     * @link \App\Service\ErrorReport::recipients()
     */
    public function testRecipientsComeFromTheConfiguration(): void
    {
        $this->withConfigure(['Report.errorEmails' => ['first@example.com']]);

        $this->assertSame(['first@example.com'], ErrorReport::recipients());
    }
}
