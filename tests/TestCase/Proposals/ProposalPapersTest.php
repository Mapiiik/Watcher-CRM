<?php
declare(strict_types=1);

namespace App\Test\TestCase\Proposals;

use App\Proposals\ProposalPapers;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

/**
 * App\Proposals\ProposalPapers Test Case
 *
 * What is asked here is the one thing about an upload that nothing else can notice. A file that
 * arrives broken arrives with a code on it, and that is reported. A file the server threw away
 * before the application started never arrives at all - fifty-five photographs came in as twenty
 * and neither the person who sent them nor anything in the code was told a thing.
 */
#[CoversClass(ProposalPapers::class)]
class ProposalPapersTest extends TestCase
{
    /**
     * @link \App\Proposals\ProposalPapers::cutShort()
     * @return void
     */
    public function testABatchArrivingAtTheLimitIsTakenForACutOne(): void
    {
        $limit = ProposalPapers::atMostAtOnce();

        $this->assertGreaterThan(0, $limit, 'the server has a limit, whatever it is set to');

        $this->assertTrue(ProposalPapers::cutShort($this->files($limit)));
        $this->assertTrue(ProposalPapers::cutShort($this->files($limit + 1)));
        $this->assertFalse(ProposalPapers::cutShort($this->files($limit - 1)));
        $this->assertFalse(ProposalPapers::cutShort([]));
    }

    /**
     * A form can carry several inputs, and the server counts them all together. Counting only the
     * one being filed would miss a batch cut by what was chosen beside it.
     *
     * @link \App\Proposals\ProposalPapers::cutShort()
     * @return void
     */
    public function testTheWholeRequestIsCountedAndNotOneFieldOfIt(): void
    {
        $limit = ProposalPapers::atMostAtOnce();

        $spread = [
            'papers' => $this->files((int)floor($limit / 2)),
            'elsewhere' => ['deeper' => $this->files($limit - (int)floor($limit / 2))],
        ];

        $this->assertTrue(ProposalPapers::cutShort($spread));
    }

    /**
     * @link \App\Proposals\ProposalPapers::shortfall()
     * @return void
     */
    public function testWhatIsSaidNamesTheLimitAndWhatToDoAboutIt(): void
    {
        $said = ProposalPapers::shortfall();

        $this->assertStringContainsString((string)ProposalPapers::atMostAtOnce(), $said);
        $this->assertNotSame('', trim($said));
    }

    /**
     * Stands in for what a request carries. Nothing looks at them, only counts them.
     *
     * @param int $howMany How many.
     * @return list<object>
     */
    private function files(int $howMany): array
    {
        return array_fill(0, max(0, $howMany), new stdClass());
    }
}
