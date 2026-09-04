<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalAcknowledgements;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Proposal\ProposalAcknowledgements Test Case
 */
#[CoversClass(ProposalAcknowledgements::class)]
class ProposalAcknowledgementsTest extends TestCase
{
    /**
     * An unanswered question is not the same as one answered no: the first is still waiting, the
     * second is a confirmation that something is deliberately absent.
     *
     * @return void
     */
    public function testAnUnansweredQuestionIsNotADenial(): void
    {
        $answers = ProposalAcknowledgements::fromArray([
            ProposalAcknowledgements::OWN_EQUIPMENT => false,
        ]);

        $this->assertFalse($answers->confirms(ProposalAcknowledgements::OWN_EQUIPMENT));
        $this->assertSame(
            [ProposalAcknowledgements::OWN_EQUIPMENT, ProposalAcknowledgements::NO_RADIUS],
            $answers->unanswered([
                ProposalAcknowledgements::OWN_EQUIPMENT,
                ProposalAcknowledgements::NO_RADIUS,
            ]),
        );
    }

    /**
     * Only the questions that were asked are held against the proposal.
     *
     * @return void
     */
    public function testOnlyWhatWasAskedIsCounted(): void
    {
        $answers = ProposalAcknowledgements::none()
            ->with(ProposalAcknowledgements::OWN_EQUIPMENT, true);

        $this->assertTrue($answers->confirms(ProposalAcknowledgements::OWN_EQUIPMENT));
        $this->assertSame([], $answers->unanswered([ProposalAcknowledgements::OWN_EQUIPMENT]));
        $this->assertSame(
            [ProposalAcknowledgements::NO_RADIUS],
            $answers->unanswered([ProposalAcknowledgements::NO_RADIUS]),
        );
    }

    /**
     * What goes in comes back out the same.
     *
     * @return void
     */
    public function testWhatIsStoredIsWhatIsRead(): void
    {
        $stored = [
            ProposalAcknowledgements::OWN_EQUIPMENT => true,
            ProposalAcknowledgements::FIXED_TERM => false,
        ];

        $this->assertSame($stored, ProposalAcknowledgements::fromArray($stored)->toArray());
        $this->assertSame([], ProposalAcknowledgements::none()->toArray());
    }

    /**
     * A question nobody asks cannot be answered, so a renamed check cannot leave a stale answer
     * sitting in the column looking like a confirmation.
     *
     * @return void
     */
    public function testAnUnknownQuestionIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ProposalAcknowledgements::fromArray(['has_a_ladder' => true]);
    }
}
