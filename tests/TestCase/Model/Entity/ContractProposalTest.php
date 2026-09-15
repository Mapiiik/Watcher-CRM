<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Entity;

use App\Model\Entity\Contract;
use App\Model\Entity\ContractProposal;
use App\Model\Enum\ProposalPurpose;
use Cake\I18n\Date;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Model\Entity\ContractProposal Test Case
 */
#[CoversClass(ContractProposal::class)]
class ContractProposalTest extends TestCase
{
    /**
     * Named beside something else, the papers say which contract they are about and the same
     * thing their own page is headed with. Asked without the contract, the number is left out
     * rather than guessed at.
     *
     * @return void
     * @link \App\Model\Entity\ContractProposal::getName()
     */
    public function testThePapersNameThemselvesInOneLine(): void
    {
        $papers = new ContractProposal([
            'purpose' => ProposalPurpose::NewContract,
            'effective_from' => new Date('2028-12-01'),
            'contract' => new Contract(['number' => '110946-4212']),
        ]);

        // The day is written the way the application writes days, whatever that is set to.
        $day = (string)$papers->effective_from;

        $this->assertSame(
            '110946-4212 - ' . ProposalPurpose::NewContract->label() . ' from ' . $day,
            $papers->getName(),
        );

        $papers->unset('contract');

        $this->assertSame(
            ProposalPurpose::NewContract->label() . ' from ' . $day,
            $papers->getName(),
        );
    }
}
