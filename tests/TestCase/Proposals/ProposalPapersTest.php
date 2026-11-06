<?php
declare(strict_types=1);

namespace App\Test\TestCase\Proposals;

use App\Model\Enum\DocumentVariant;
use App\Proposals\ProposalPapers;
use App\Service\ContractPrint\ContractDocuments;
use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Cake\Utility\Filesystem;
use Files\Model\Table\FileLinksTable;
use Files\Service\FileStorage;
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
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
        'plugin.Files.RemovedFileLinks',
    ];

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
     * The plugin cannot read the enum, so it keeps the list the index holds to one per document.
     *
     * @link \Files\Model\Table\FileLinksTable::ONE_PER_DOCUMENT
     * @return void
     */
    public function testThePluginHoldsToOneWhatWeDraw(): void
    {
        $drawn = array_filter(
            DocumentVariant::cases(),
            fn(DocumentVariant $variant): bool => $variant->isGeneratedByUs(),
        );

        $this->assertSame(
            FileLinksTable::ONE_PER_DOCUMENT,
            array_values(array_map(fn(DocumentVariant $variant): string => $variant->value, $drawn)),
        );
    }

    /**
     * The trash brings back what left in one act, so both forms of a paper have to leave in one.
     * Asked with the clock running, as under a stopped one any two moments are the same.
     *
     * @link \App\Proposals\ProposalPapers::drop()
     * @return void
     */
    public function testBothFormsOfAPaperLeaveInOneAct(): void
    {
        $root = TMP . 'proposal-papers-' . uniqid();
        Configure::write('Files.root', $root);
        $clock = Chronos::getTestNow();
        Chronos::setTestNow(null);

        try {
            $storage = new FileStorage();
            $record = '11111111-2222-4333-8444-555555555555';

            $plain = $storage->link(
                $storage->store('a paper', 'application/pdf'),
                ContractDocuments::MODEL,
                $record,
                'contract-new',
                DocumentVariant::Generated->value,
            );
            $storage->link(
                $storage->store('a paper, stamped', 'application/pdf'),
                ContractDocuments::MODEL,
                $record,
                'contract-new',
                DocumentVariant::GeneratedSignedByUs->value,
            );

            $this->assertSame(2, (new ProposalPapers($storage))->drop($plain));

            $moments = $this->fetchTable('Files.RemovedFileLinks')->find()->all()->extract('removed')->toList();

            $this->assertCount(2, $moments);
            $this->assertEquals($moments[0], $moments[1]);
        } finally {
            Chronos::setTestNow($clock);
            Configure::delete('Files.root');
            (new Filesystem())->deleteDir($root);
        }
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
