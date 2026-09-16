<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\ContractPrint;

use App\Model\Enum\DocumentVariant;
use App\Pdf\AppPDF;
use App\Service\ContractPrint\ContractDocuments;
use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Service\ContractPrint\ContractDocuments Test Case
 *
 * A paper is drawn once and handed over ever after. What is asked of that here is the whole of
 * what it promises: that asking twice gives back the same bytes rather than a fresh drawing, and
 * that the copy with our signature on it is the unsigned one with a signature rather than a
 * second setting of the same document.
 */
#[UsesClass(ContractDocuments::class)]
class ContractDocumentsTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * The contract the papers are drawn for, and a proposal on it.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';
    private const VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';

    /**
     * The document that proposal may be printed as. The summary, which binds nobody and so has
     * no signature block - which is what a couple of these tests turn on.
     *
     * @var string
     */
    private const DOCUMENT = 'contract-summary';
    private const SIGNABLE = 'contract-amendment';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.AppUsers',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.ContractVersions',
        'app.CustomerProposals',
        'app.ContractProposals',
        'app.IpAddresses',
        'app.IpNetworks',
        'app.SoldEquipments',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
    ];

    /**
     * Where the papers go while this runs.
     *
     * @var string
     */
    private string $root;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'contract-documents-' . uniqid();
        Configure::write('Files.root', $this->root);

        $this->login();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    protected function tearDown(): void
    {
        Configure::delete('Files.root');
        $this->removeDirectory($this->root);

        parent::tearDown();
    }

    /**
     * @link \App\Service\ContractPrint\ContractDocuments::for()
     * @return void
     */
    public function testAPaperIsDrawnOnceAndKept(): void
    {
        $paper = $this->print();

        $this->assertStringStartsWith('%PDF', $paper);
        $this->assertSame(1, $this->stored());
        $this->assertSame(1, $this->filed(DocumentVariant::Generated));
    }

    /**
     * The promise the whole thing rests on: what somebody signed is what comes back, whatever
     * the records have done since.
     *
     * @link \App\Service\ContractPrint\ContractDocuments::for()
     * @return void
     */
    public function testAskingAgainGivesBackTheSamePaperRatherThanANewOne(): void
    {
        $first = $this->print();
        $second = $this->print();

        $this->assertSame($first, $second);
        $this->assertSame(1, $this->stored(), 'The paper was drawn a second time.');
    }

    /**
     * The signature goes onto the paper that is already on file, and that paper does not move.
     *
     * The base is put on the shelf by hand here, carrying the marks that say where it is signed.
     * Which is a stand-in rather than a lie: the rule the code follows is "does this paper have a
     * mark for our signature", and this one has, so it takes the path a contract would.
     *
     * @link \App\Service\ContractPrint\ContractDocuments::for()
     */
    public function testTheSignatureGoesOntoThePaperThatIsAlreadyOnFile(): void
    {
        $base = $this->fileAPaperThatCanBeSigned();

        $signed = $this->print(true);

        $this->assertSame(2, $this->stored(), 'The signed copy was not filed beside the base.');
        $this->assertSame(1, $this->filed(DocumentVariant::Generated));
        $this->assertSame(1, $this->filed(DocumentVariant::GeneratedSignedByUs));

        $this->assertSame($base, $this->paperOf(DocumentVariant::Generated), 'The paper on file moved.');
        $this->assertNotSame($base, $signed, 'Nothing was drawn onto it.');
    }

    /**
     * And asking a second time hands back what was made rather than making it again.
     *
     * @link \App\Service\ContractPrint\ContractDocuments::for()
     * @return void
     */
    public function testTheSignedCopyIsFrozenTheSameWayTheBaseIs(): void
    {
        $this->fileAPaperThatCanBeSigned();

        $first = $this->print(true);
        $second = $this->print(true);

        $this->assertSame($first, $second);
        $this->assertSame(2, $this->stored(), 'The signed copy was made twice.');
    }

    /**
     * A paper with nowhere to sign is handed over as it stands.
     *
     * The summary says what is on offer before anybody is bound by it, so it carries no signature
     * block. Asking for it signed used to hand back the same pages wrapped in a second document
     * and file that as a copy we had signed - bigger, indistinguishable, and a lie on the shelf.
     *
     * @link \App\Service\ContractPrint\ContractDocuments::for()
     * @return void
     */
    public function testAPaperWithNowhereToSignIsNotSigned(): void
    {
        $unsigned = $this->print();
        $asked = $this->print(true);

        $this->assertSame($unsigned, $asked, 'The summary came back as something else.');
        $this->assertSame(1, $this->stored(), 'A second copy of the summary was filed.');
        $this->assertSame(0, $this->filed(DocumentVariant::GeneratedSignedByUs));
    }

    /**
     * And the operator is not offered a switch that would do nothing.
     *
     * @link \App\Model\Enum\ContractDocumentType::mayCarryOurSignature()
     * @return void
     */
    public function testTheSwitchIsOfferedOnlyWhereItWouldDoSomething(): void
    {
        // Our signature is stamped onto a paper that is already there, so the offer stands beside
        // one - and only where there is somewhere on that paper to sign.
        // Asked of the paper's own offer rather than of the page: other papers the round owes are
        // listed here too, and some of them may be signed.
        $this->fileGenerated(self::DOCUMENT);
        $this->form(self::DOCUMENT);
        $this->assertResponseNotContains($this->offerOfASignedCopy(self::DOCUMENT));

        $this->fileGenerated(self::SIGNABLE);
        $this->form(self::SIGNABLE);
        $this->assertResponseContains($this->offerOfASignedCopy(self::SIGNABLE));
    }

    /**
     * How the offer of a signed copy of one paper reads in the page.
     *
     * @param string $document Which paper.
     * @return string
     */
    private function offerOfASignedCopy(string $document): string
    {
        return 'document_type=' . $document . '&amp;signed=1';
    }

    /**
     * Puts a paper of the given kind on the shelf, without drawing it.
     *
     * @param string $document Which paper.
     * @return void
     */
    private function fileGenerated(string $document): void
    {
        $storage = new FileStorage();
        $storage->link(
            $storage->store('%PDF-1.7 ' . $document, 'application/pdf'),
            ContractDocuments::MODEL,
            self::PROPOSAL_ID,
            $document,
            DocumentVariant::Generated->value,
            ['name' => $document . '.pdf'],
        );
    }

    /**
     * The name a paper was filed under is the name it comes back under, however long afterwards.
     *
     * @link \App\Service\ContractPrint\ContractDocuments::for()
     * @return void
     */
    public function testAPaperComesBackUnderTheNameItWasFiledWith(): void
    {
        $this->print();
        $this->print();

        $filed = $this->fetchTable('Files.FileLinks')->find()->firstOrFail();

        $this->assertNotNull($this->_response);
        $this->assertStringContainsString(
            (string)$filed->get('name'),
            $this->_response->getHeaderLine('Content-Disposition'),
        );
    }

    /**
     * Papers for a new contract may be drawn up before the version they are about exists, and they
     * have to come out the same either way - the drawing reads the snapshot and what is proposed,
     * never the version record. Byte for byte, because anything less would let a difference hide.
     *
     * @return void
     */
    public function testAPaperReadsTheSameWhetherOrNotTheVersionExistsYet(): void
    {
        $withOne = $this->print();

        // The same papers, no longer naming a version. What they were photographed against has not
        // moved, so nothing about them has changed except where the version is kept.
        $this->fetchTable('Files.FileLinks')->deleteAll([]);

        $proposals = $this->fetchTable('ContractProposals');
        $proposals->saveOrFail(
            $proposals->patchEntity(
                $proposals->get(self::PROPOSAL_ID),
                ['contract_version_id' => null],
            ),
            ['checkRules' => false],
        );

        $this->assertSame($this->apartFromItsOwnMark($withOne), $this->apartFromItsOwnMark($this->print()));
    }

    /**
     * The paper without what it stamps on itself: its identifiers and the moment it was drawn.
     *
     * Two drawings of the same thing differ in those and nowhere else, and none of them says
     * anything about what is on the page. Left in, the test would also fail whenever the two
     * drawings happened to fall either side of a second.
     *
     * @param string $paper The paper.
     * @return string
     */
    private function apartFromItsOwnMark(string $paper): string
    {
        return (string)preg_replace(
            [
                '/\/ID \[[^\]]*\]/',
                '/uuid:[0-9a-f-]+/',
                '/\d{4}-\d{2}-\d{2}T[\d:]+[+-][\d:]+/',
                '/D:\d{14}[^)]*/',
            ],
            ['/ID []', 'uuid:', 'when', 'D:when'],
            $paper,
        );
    }

    /**
     * Asks for the paper the way the print page does.
     *
     * @param bool $signed Whether to ask for the copy carrying our signature.
     * @param string|null $document Which document, where it is not the usual one.
     * @return string The paper.
     */
    private function print(bool $signed = false, ?string $document = null): string
    {
        $this->get(sprintf(
            '/customers/%s/contracts/%s/documents/generate.pdf'
            . '?agenda=ContractProposals&proposal_id=%s&document_type=%s%s',
            self::CUSTOMER_ID,
            self::CONTRACT_ID,
            self::PROPOSAL_ID,
            $document ?? self::DOCUMENT,
            $signed ? '&signed=1' : '',
        ));

        $this->assertResponseOk();
        $this->assertNotNull($this->_response);

        return (string)$this->_response->getBody();
    }

    /**
     * Opens the print page for one document, without asking for the document itself.
     *
     * @param string $document Which document.
     * @return void
     */
    private function form(string $document): void
    {
        $this->get(sprintf(
            '/customers/%s/contracts/%s/contract-versions/%s/documents/manage'
            . '?agenda=ContractProposals&proposal_id=%s&document_type=%s',
            self::CUSTOMER_ID,
            self::CONTRACT_ID,
            self::VERSION_ID,
            self::PROPOSAL_ID,
            $document,
        ));

        $this->assertResponseOk();
    }

    /**
     * Puts a paper on the shelf that carries the marks saying where it is signed.
     *
     * @return string The paper.
     */
    private function fileAPaperThatCanBeSigned(): string
    {
        $pdf = new class extends AppPDF {
            /**
             * @return void
             */
            public function drawTheBlockThatIsSigned(): void
            {
                $this->AddPage();
                $this->SetFont(static::FONT_FAMILY, '', static::BODY_FONT_SIZE);
                $this->printSignatureSection('double');
            }
        };
        $pdf->drawTheBlockThatIsSigned();
        $paper = $pdf->Output('base.pdf', 'S');

        $storage = new FileStorage();
        $storage->link(
            $storage->store($paper, 'application/pdf'),
            ContractDocuments::MODEL,
            self::PROPOSAL_ID,
            self::DOCUMENT,
            DocumentVariant::Generated->value,
            ['name' => 'base.pdf'],
        );

        return $paper;
    }

    /**
     * @return int How many papers are on the shelf.
     */
    private function stored(): int
    {
        return $this->fetchTable('Files.Files')->find()->count();
    }

    /**
     * @param \App\Model\Enum\DocumentVariant $variant Which variant of the document.
     * @return int How many papers the proposal has in that hand.
     */
    private function filed(DocumentVariant $variant): int
    {
        return $this->fetchTable('Files.FileLinks')->find()
            ->where(['variant' => $variant->value])
            ->count();
    }

    /**
     * @param \App\Model\Enum\DocumentVariant $variant Which variant of the document.
     * @return string The paper on file in that hand.
     */
    private function paperOf(DocumentVariant $variant): string
    {
        $link = $this->fetchTable('Files.FileLinks')->find()
            ->contain(['Files'])
            ->where(['variant' => $variant->value])
            ->firstOrFail();

        return (new FileStorage())->read($link->get('file'));
    }

    /**
     * Removes a directory and everything under it.
     *
     * @param string $directory The directory.
     * @return void
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (array_diff((array)scandir($directory), ['.', '..']) as $entry) {
            $path = $directory . DS . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
