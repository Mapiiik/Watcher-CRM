<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\ContractPrint;

use App\Model\Enum\DocumentVariant;
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
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

    /**
     * The document that proposal may be printed as.
     *
     * @var string
     */
    private const DOCUMENT = 'contract-summary';

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
        'app.ContractVersionProposals',
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
     * Asking for our signature on a paper nobody has drawn yet draws the unsigned one, keeps it,
     * and stamps that - so two papers come out of one drawing.
     *
     * @link \App\Service\ContractPrint\ContractDocuments::for()
     * @return void
     */
    public function testTheSignedCopyIsTheUnsignedOneWithASignatureOnIt(): void
    {
        $signed = $this->print(true);

        $this->assertSame(2, $this->stored());
        $this->assertSame(1, $this->filed(DocumentVariant::Generated));
        $this->assertSame(1, $this->filed(DocumentVariant::GeneratedSignedByUs));

        // The unsigned one that was kept on the way is the one anybody else would have been given.
        $this->assertSame($this->print(), $this->paperOf(DocumentVariant::Generated));
        $this->assertNotSame($signed, $this->print());
    }

    /**
     * And where the unsigned one is already on file, nothing is drawn at all - the signature goes
     * onto the paper that is there.
     *
     * @link \App\Service\ContractPrint\ContractDocuments::for()
     * @return void
     */
    public function testTheSignatureGoesOntoThePaperThatIsAlreadyOnFile(): void
    {
        $base = $this->print();

        $this->print(true);

        $this->assertSame(2, $this->stored());
        $this->assertSame($base, $this->paperOf(DocumentVariant::Generated), 'The paper on file moved.');
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
     * Asks for the paper the way the print page does.
     *
     * @param bool $signed Whether to ask for the copy carrying our signature.
     * @return string The paper.
     */
    private function print(bool $signed = false): string
    {
        $this->get(sprintf(
            '/contracts/print/%s.pdf?proposal_id=%s&document_type=%s%s',
            self::CONTRACT_ID,
            self::PROPOSAL_ID,
            self::DOCUMENT,
            $signed ? '&signed=1' : '',
        ));

        $this->assertResponseOk();
        $this->assertNotNull($this->_response);

        return (string)$this->_response->getBody();
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
