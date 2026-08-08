<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\OperatorReport;
use App\Test\Traits\ConfigureTestTrait;
use Cake\TestSuite\EmailTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Service\OperatorReport Test Case
 *
 * What matters here is the deployment that has nobody configured to be told. Reporting a failure
 * used to throw one of its own on such a deployment, which lost the failure worth reporting.
 */
#[UsesClass(OperatorReport::class)]
class OperatorReportTest extends TestCase
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
     * @link \App\Service\OperatorReport::send()
     */
    public function testSendReachesEverybodyConfigured(): void
    {
        $this->withConfigure(['Report.emails' => ['first@example.com', 'second@example.com']]);

        $this->assertTrue(OperatorReport::send('Something failed', 'This is what is known about it.'));

        $this->assertMailCount(1);
        $this->assertMailSentTo('first@example.com');
        $this->assertMailSentTo('second@example.com');
        $this->assertMailContains('This is what is known about it.');
    }

    /**
     * Nobody configured leaves the report unsent rather than raising a failure of its own.
     *
     * @return void
     * @link \App\Service\OperatorReport::send()
     */
    public function testSendWithNobodyConfiguredIsNotAFailure(): void
    {
        $this->withConfigure(['Report.emails' => []]);

        $this->assertFalse(OperatorReport::send('Something failed', 'This is what is known about it.'));

        $this->assertNoMailSent();
    }

    /**
     * The recipients are read from the configuration, which is where the environment was made
     * sense of - and nowhere else.
     *
     * @return void
     * @link \App\Service\OperatorReport::recipients()
     */
    public function testRecipientsComeFromTheConfiguration(): void
    {
        $this->withConfigure(['Report.emails' => ['first@example.com']]);

        $this->assertSame(['first@example.com'], OperatorReport::recipients());
    }
}
