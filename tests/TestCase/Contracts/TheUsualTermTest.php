<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts;

use App\Contracts\TheUsualTerm;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\TheUsualTerm Test Case
 */
#[CoversClass(TheUsualTerm::class)]
class TheUsualTermTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Settings.Settings',
    ];

    /**
     * The term runs to the day before the same date months later, so that two years from the first
     * of October is the last of September - not a day over.
     *
     * @return void
     */
    public function testATermRunsToTheDayBeforeItWouldStartAgain(): void
    {
        $this->assertSame(
            '2028-09-30',
            TheUsualTerm::from(new Date('2026-10-01'))->toDateString(),
        );

        // A term agreed mid-month ends mid-month, and the end of a long month is answered for by
        // the same rule rather than by a special case.
        $this->assertSame(
            '2028-01-14',
            TheUsualTerm::from(new Date('2026-01-15'))->toDateString(),
        );
        $this->assertSame(
            '2028-02-29',
            TheUsualTerm::from(new Date('2026-03-01'))->toDateString(),
        );
    }

    /**
     * How long it runs is a setting, because the usual term is a matter of what is being sold
     * rather than of how the application works.
     *
     * @return void
     */
    public function testHowLongItRunsIsSaid(): void
    {
        $this->assertSame(24, TheUsualTerm::months());
    }
}
