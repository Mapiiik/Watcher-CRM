<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Proposal;

use App\Contracts\Proposal\ProposalConfirmations;
use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Proposal\ProposalConfirmations Test Case
 */
#[CoversClass(ProposalConfirmations::class)]
class ProposalConfirmationsTest extends TestCase
{
    /**
     * An unanswered question is not the same as one answered no: the first is still waiting, the
     * second is a confirmation that something is deliberately absent.
     *
     * @return void
     */
    public function testAnUnansweredQuestionIsNotADenial(): void
    {
        $answers = ProposalConfirmations::fromArray([
            ProposalConfirmations::OWN_EQUIPMENT => false,
        ]);

        $this->assertFalse($answers->confirms(ProposalConfirmations::OWN_EQUIPMENT));
        $this->assertSame(
            [ProposalConfirmations::OWN_EQUIPMENT, ProposalConfirmations::NO_RADIUS],
            $answers->unanswered([
                ProposalConfirmations::OWN_EQUIPMENT,
                ProposalConfirmations::NO_RADIUS,
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
        $answers = ProposalConfirmations::none()
            ->with(ProposalConfirmations::OWN_EQUIPMENT, true);

        $this->assertTrue($answers->confirms(ProposalConfirmations::OWN_EQUIPMENT));
        $this->assertSame([], $answers->unanswered([ProposalConfirmations::OWN_EQUIPMENT]));
        $this->assertSame(
            [ProposalConfirmations::NO_RADIUS],
            $answers->unanswered([ProposalConfirmations::NO_RADIUS]),
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
            ProposalConfirmations::OWN_EQUIPMENT => true,
            ProposalConfirmations::FIXED_TERM => false,
        ];

        $this->assertSame($stored, ProposalConfirmations::fromArray($stored)->toArray());
        $this->assertSame([], ProposalConfirmations::none()->toArray());
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

        ProposalConfirmations::fromArray(['has_a_ladder' => true]);
    }
}
