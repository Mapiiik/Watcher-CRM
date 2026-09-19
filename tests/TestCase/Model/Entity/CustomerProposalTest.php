<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\ContractProposal;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\ProposalPurpose;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;

/**
 * App\Model\Entity\CustomerProposal Test Case
 */
#[CoversClass(CustomerProposal::class)]
class CustomerProposalTest extends TestCase
{
    /**
     * A round holding what is given, with nothing else on it.
     *
     * @param \App\Model\Enum\CustomerProposalPurpose|null $purpose What is asked of the customer.
     * @param int $papers How many contracts' papers it carries.
     * @return \App\Model\Entity\CustomerProposal
     */
    private function roundOf(?CustomerProposalPurpose $purpose, int $papers): CustomerProposal
    {
        return new CustomerProposal([
            'purpose' => $purpose,
            'contract_proposals' => array_fill(0, $papers, new ContractProposal()),
        ]);
    }

    /**
     * A set of papers as it stands at a given step of its own road.
     *
     * @param \Cake\I18n\DateTime|null $applied When it was applied, if it was.
     * @param \Cake\I18n\DateTime|null $revoked When it was given up on, if it was.
     * @return \App\Model\Entity\ContractProposal
     */
    private function papers(?DateTime $applied = null, ?DateTime $revoked = null): ContractProposal
    {
        return new ContractProposal([
            'purpose' => ProposalPurpose::ServiceChange,
            'applied' => $applied,
            'revoked' => $revoked,
        ]);
    }

    /**
     * A round signed, with what it holds in the given states.
     *
     * @param array<\App\Model\Entity\ContractProposal> $papers What it holds.
     * @return \App\Model\Entity\CustomerProposal
     */
    private function signedRoundHolding(array $papers): CustomerProposal
    {
        return new CustomerProposal([
            'purpose' => null,
            'conclusion_date' => new Date('2026-10-05'),
            'contract_proposals' => $papers,
        ]);
    }

    /**
     * The round's own road ends at the signature, but what it holds goes further - so after the
     * signature it says what is left to do rather than calling itself done.
     *
     * @link \App\Model\Entity\CustomerProposal::getState()
     * @return void
     */
    public function testASignedRoundSaysWhatIsLeftToDoInIt(): void
    {
        $waiting = $this->signedRoundHolding([$this->papers(), $this->papers(DateTime::now())]);
        $this->assertSame(__('Waiting for the changes to be applied'), $waiting->getState());
        $this->assertFalse($waiting->hasBeenDealtWith());

        $done = $this->signedRoundHolding([$this->papers(DateTime::now())]);
        $this->assertSame(__('Changes applied'), $done->getState());
        $this->assertTrue($done->hasBeenDealtWith());

        // Papers given up on were never applied, and nothing waits for them either.
        $abandoned = $this->signedRoundHolding([$this->papers(revoked: DateTime::now())]);
        $this->assertSame(__('Signed'), $abandoned->getState());
        $this->assertTrue($abandoned->hasBeenDealtWith());
    }

    /**
     * A round holding nothing of any contract's is done with when it is signed, and one nobody has
     * signed says where it stands rather than looking into what it holds.
     *
     * @link \App\Model\Entity\CustomerProposal::getState()
     * @return void
     */
    public function testARoundOfItsOwnIsDoneWithWhenItIsSigned(): void
    {
        $this->assertSame(__('Signed'), $this->signedRoundHolding([])->getState());
        $this->assertTrue($this->signedRoundHolding([])->hasBeenDealtWith());

        $open = new CustomerProposal([
            'purpose' => null,
            'contract_proposals' => [$this->papers()],
        ]);

        $this->assertSame(__('Being prepared'), $open->getState());
        $this->assertFalse($open->hasBeenDealtWith());
    }

    /**
     * Both halves are said, and either may stand alone.
     *
     * @link \App\Model\Entity\CustomerProposal::whatItIsFor()
     * @return void
     */
    public function testWhatARoundIsForSaysBothHalves(): void
    {
        $asked = CustomerProposalPurpose::GdprConsent;

        $this->assertSame($asked->label(), $this->roundOf($asked, 0)->whatItIsFor());
        $this->assertSame(
            $asked->label() . ' + ' . __('Contract proposals'),
            $this->roundOf($asked, 1)->whatItIsFor(),
        );
        $this->assertSame(__('Contract proposals'), $this->roundOf(null, 2)->whatItIsFor());
    }

    /**
     * A round just opened asks for nothing and holds nothing, and says so.
     *
     * @link \App\Model\Entity\CustomerProposal::whatItIsFor()
     * @return void
     */
    public function testARoundThatCarriesNothingSaysSo(): void
    {
        $this->assertSame(__('Empty'), $this->roundOf(null, 0)->whatItIsFor());
    }

    /**
     * Not knowing what the round holds is a different thing from it holding nothing, so it is not
     * quietly answered as though it were.
     *
     * @link \App\Model\Entity\CustomerProposal::whatItIsFor()
     * @return void
     */
    public function testARoundAskedWithoutItsPapersRefusesToName(): void
    {
        $this->expectException(RuntimeException::class);

        (new CustomerProposal(['purpose' => CustomerProposalPurpose::GdprConsent]))->whatItIsFor();
    }
}
