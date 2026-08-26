<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\OperatorReport;
use App\Test\Traits\ConfigureTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Service\OperatorReport Test Case
 *
 * Only the addresses live here; each report builds its own mailer around them. What is worth
 * pinning is that they are the reports' own addresses and not the ones a failure goes to.
 */
#[UsesClass(OperatorReport::class)]
class OperatorReportTest extends TestCase
{
    use ConfigureTestTrait;

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

    /**
     * A report is sent to whoever asked for the reports, never to the address kept for failures.
     *
     * @return void
     * @link \App\Service\OperatorReport::recipients()
     */
    public function testRecipientsAreNotTheOnesToldAboutFailures(): void
    {
        $this->withConfigure([
            'Report.errorEmails' => ['on-call@example.com'],
            'Report.emails' => [],
        ]);

        $this->assertSame([], OperatorReport::recipients());
    }
}
