<?php
declare(strict_types=1);

namespace App\Test\TestCase\Proposals;

use App\Model\Enum\ProposalStep;
use App\Proposals\Settlement;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Proposals\Settlement Test Case
 *
 * Nothing here reads a record, which is the point of the class: every combination of how far the
 * papers go and what has happened to them can be put to it directly, including the ones no purpose
 * asks for today.
 */
#[CoversClass(Settlement::class)]
class SettlementTest extends TestCase
{
    /**
     * A day for anything that wants one.
     */
    private function day(): Date
    {
        return new Date('2026-09-14');
    }

    /**
     * And a moment.
     */
    private function moment(): DateTime
    {
        return new DateTime('2026-09-14 10:00:00');
    }

    /**
     * Papers that are only handed over are done the moment they are, so nobody is left waiting for
     * a signature that is never coming.
     *
     * @return void
     */
    public function testPapersThatAreOnlyIssuedAreSettledByBeingIssued(): void
    {
        $issued = new Settlement(ProposalStep::Issued, null, $this->day());

        $this->assertTrue($issued->isSettled());
        $this->assertSame('Issued', $issued->state());
        $this->assertFalse($issued->expects(ProposalStep::Delivered));
        $this->assertFalse($issued->expects(ProposalStep::Signed));
    }

    /**
     * Papers that only have to arrive are settled by arriving.
     *
     * @return void
     */
    public function testPapersThatAreDeliveredAreSettledByBeingDelivered(): void
    {
        $waiting = new Settlement(ProposalStep::Delivered, null, null);
        $this->assertFalse($waiting->isSettled());
        $this->assertSame('Being prepared', $waiting->state());
        $this->assertTrue($waiting->isDueFor(ProposalStep::Delivered));

        $done = new Settlement(ProposalStep::Delivered, $this->day(), $this->day());
        $this->assertTrue($done->isSettled());
        $this->assertSame('Delivered', $done->state());
        $this->assertFalse($done->isDueFor(ProposalStep::Delivered));
    }

    /**
     * A consent is settled by coming back signed, and nothing stands behind it.
     *
     * @return void
     */
    public function testPapersThatAreSignedAreSettledByTheSignature(): void
    {
        $sent = new Settlement(ProposalStep::Signed, $this->day(), null);
        $this->assertFalse($sent->isSettled());
        $this->assertSame('Sent', $sent->state());
        $this->assertTrue($sent->isDueFor(ProposalStep::Signed));

        $signed = new Settlement(ProposalStep::Signed, $this->day(), $this->day());
        $this->assertTrue($signed->isSettled());
        $this->assertSame('Signed', $signed->state());
    }

    /**
     * A proposal of a contract is only halfway once it is signed: what it asked for still has to
     * reach the records.
     *
     * @return void
     */
    public function testPapersThatAreCarriedOverAreNotSettledByTheSignature(): void
    {
        $signed = new Settlement(ProposalStep::CarriedOver, $this->day(), $this->day());
        $this->assertFalse($signed->isSettled());
        $this->assertSame('Waiting to be carried over', $signed->state());
        $this->assertTrue($signed->isDueFor(ProposalStep::CarriedOver));

        $carried = new Settlement(
            ProposalStep::CarriedOver,
            $this->day(),
            $this->day(),
            $this->moment(),
        );
        $this->assertTrue($carried->isSettled());
        $this->assertSame('Carried over', $carried->state());
    }

    /**
     * Being given up on settles papers however far they were going.
     *
     * @return void
     */
    public function testBeingRevokedSettlesAnything(): void
    {
        foreach (ProposalStep::cases() as $step) {
            $revoked = new Settlement($step, null, null, null, $this->moment());

            $this->assertTrue($revoked->isSettled(), $step->name);
            $this->assertSame('Revoked', $revoked->state(), $step->name);
            $this->assertFalse($revoked->isDueFor(ProposalStep::Delivered), $step->name);
        }
    }

    /**
     * What has already happened comes before what only moved the papers along, so papers that were
     * carried over do not still read as sent.
     *
     * @return void
     */
    public function testWhatSettledThePapersIsReadBeforeWhatMovedThemAlong(): void
    {
        $both = new Settlement(
            ProposalStep::CarriedOver,
            $this->day(),
            $this->day(),
            $this->moment(),
            $this->moment(),
        );

        $this->assertSame('Carried over', $both->state());
    }

    /**
     * The steps are not held to their order. Papers come back signed whether or not anybody wrote
     * down that they went out, and the office records what happened rather than what should have.
     *
     * @return void
     */
    public function testAStepDoesNotWaitForTheOnesBeforeIt(): void
    {
        $fresh = new Settlement(ProposalStep::CarriedOver, null, null);

        $this->assertTrue($fresh->isDueFor(ProposalStep::Delivered));
        $this->assertTrue($fresh->isDueFor(ProposalStep::Signed));
        $this->assertTrue($fresh->isDueFor(ProposalStep::CarriedOver));
    }

    /**
     * And a step the papers never go through is never offered, however far along they are.
     *
     * @return void
     */
    public function testAStepThePapersNeverTakeIsNeverDue(): void
    {
        $issued = new Settlement(ProposalStep::Issued, null, null);

        $this->assertFalse($issued->isDueFor(ProposalStep::Delivered));
        $this->assertFalse($issued->isDueFor(ProposalStep::Signed));
        $this->assertFalse($issued->isDueFor(ProposalStep::CarriedOver));
    }
}
