<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\HistoricalConnections;

use App\Service\HistoricalConnections\UpdateSummary;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Service\HistoricalConnections\UpdateSummary Test Case
 */
#[UsesClass(UpdateSummary::class)]
class UpdateSummaryTest extends TestCase
{
    /**
     * A run that wrote nothing says so, which is what keeps a nightly job from reporting on every
     * run it makes.
     *
     * @return void
     * @link \App\Service\HistoricalConnections\UpdateSummary::hasChanges()
     */
    public function testARunThatWroteNothingSaysSo(): void
    {
        $summary = new UpdateSummary();
        $summary->accounts = 120;
        $summary->skipped = 120;

        $this->assertFalse($summary->hasChanges());
    }

    /**
     * Each of the three ways of writing counts as a change on its own. Walking accounts and passing
     * over what was already recorded does not.
     *
     * @return void
     * @link \App\Service\HistoricalConnections\UpdateSummary::hasChanges()
     */
    public function testEachWayOfWritingCountsAsAChangeOnItsOwn(): void
    {
        foreach (['opened', 'extended', 'enriched'] as $written) {
            $summary = new UpdateSummary();
            $summary->{$written} = 1;

            $this->assertTrue($summary->hasChanges(), 'a run that ' . $written . ' an interval wrote something');
        }
    }
}
