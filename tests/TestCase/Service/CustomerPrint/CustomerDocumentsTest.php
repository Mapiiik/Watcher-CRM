<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\CustomerPrint;

use App\Model\Enum\ContractPrintType;
use App\Model\Enum\CustomerPrintType;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentVariant;
use App\Service\CustomerPrint\CustomerDocuments;
use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Files\Model\Entity\FileLink;
use Files\Service\FileStorage;
use Laminas\Diactoros\UploadedFile;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Service\CustomerPrint\CustomerDocuments Test Case
 *
 * A consent lists what we hold about somebody, so what is asked of it is the promise that makes
 * that safe: the paper is drawn once and handed back ever after, whatever the records have done
 * since.
 */
#[UsesClass(CustomerDocuments::class)]
class CustomerDocumentsTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * The customer the rounds hang on.
     *
     * @var string
     */
    /**
     * Where these pages belong: a proposal is reached under the record it is for, and asked
     * for anywhere else it is sent here.
     *
     * @var string
     */
    private const NESTED = '/customers/403bab0e-52cd-4a8e-83f8-43c2457d0481';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * A round of one of that customer's contracts, from the fixture.
     *
     * @var string
     */
    private const CONTRACT_PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

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
        'app.Emails',
        'app.Phones',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.CustomerProposals',
        'app.ContractProposals',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
        'plugin.Settings.Settings',
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

        $this->root = TMP . 'customer-documents-' . uniqid();
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
     * @link \App\Service\CustomerPrint\CustomerDocuments::for()
     * @return void
     */
    public function testAPaperIsDrawnOnceAndKept(): void
    {
        $paper = $this->print($this->round());

        $this->assertStringStartsWith('%PDF', $paper);
        $this->assertSame(1, $this->stored());
        $this->assertSame(1, $this->filed(DocumentVariant::Generated));
    }

    /**
     * The promise the whole thing rests on: what somebody signed is what comes back, whatever the
     * records have done since.
     *
     * @link \App\Service\CustomerPrint\CustomerDocuments::for()
     * @return void
     */
    public function testAskingAgainGivesBackTheSamePaperRatherThanANewOne(): void
    {
        $round = $this->round();

        $first = $this->print($round);

        // The customer moves house. The consent that went out still lists where they lived when
        // they agreed to it, and that is the point of keeping it.
        $customers = $this->getTableLocator()->get('Customers');
        $customers->saveOrFail(
            $customers->patchEntity($customers->get(self::CUSTOMER_ID), ['company' => 'Somebody Else']),
            ['checkRules' => false],
        );

        $second = $this->print($round);

        $this->assertSame($first, $second);
        $this->assertSame(1, $this->stored(), 'The paper was drawn a second time.');
    }

    /**
     * Two rounds are two papers, which is what makes a signed scan tellable from the last one.
     *
     * @link \App\Service\CustomerPrint\CustomerDocuments::for()
     * @return void
     */
    public function testEachRoundHasItsOwnPaper(): void
    {
        $this->print($this->round('2026-01-01'));
        $this->print($this->round('2026-09-30'));

        $this->assertSame(2, $this->stored());
        $this->assertSame(2, $this->filed(DocumentVariant::Generated));
    }

    /**
     * The customer's own page of papers shows both agendas at once, and a consent belongs to
     * nobody's contract, so that column is simply empty on its rows.
     *
     * @link \App\View\Cell\DocumentsCell::display()
     * @return void
     */
    public function testTheCustomersPapersShowBothAgendasTogether(): void
    {
        $this->print($this->round());

        $this->get(sprintf('/customers/%s/documents/manage', self::CUSTOMER_ID));

        $this->assertResponseOk();
        $this->assertResponseContains('Consent to the processing of personal data');
        // The heading is drawn, so the column is there - the round simply has nothing to put in it.
        $this->assertResponseContains('<th>' . __('Contract') . '</th>');
    }

    /**
     * What was put to the customer themselves reads first, because it is about all of them, while
     * a contract's paper is about a corner of them.
     *
     * @link \App\View\Cell\DocumentsCell::display()
     * @return void
     */
    public function testTheCustomersOwnRoundsReadBeforeTheContractsPapers(): void
    {
        $this->fileAgainst(
            CustomerDocuments::MODEL,
            $this->round(),
            CustomerPrintType::GdprNew->value,
        );
        $this->fileAgainst(
            'ContractProposals',
            self::CONTRACT_PROPOSAL_ID,
            ContractPrintType::ContractAmendment->value,
        );

        $this->get(sprintf('/customers/%s/documents/manage', self::CUSTOMER_ID));

        $this->assertResponseOk();
        $body = (string)$this->_response?->getBody();
        $consent = strpos($body, CustomerPrintType::GdprNew->label());
        $amendment = strpos($body, ContractPrintType::ContractAmendment->label());

        $this->assertIsInt($consent);
        $this->assertIsInt($amendment);
        $this->assertLessThan($amendment, $consent);
    }

    /**
     * The print page says what it has already drawn, so that fetching a paper back is the obvious
     * thing to do rather than printing it a second time.
     *
     * @link \App\View\Cell\DocumentsCell::display()
     * @return void
     */
    public function testThePrintPageListsWhatHasAlreadyBeenDrawn(): void
    {
        $this->print($this->round());
        $theContracts = $this->fileAgainst(
            'ContractProposals',
            self::CONTRACT_PROPOSAL_ID,
            ContractPrintType::ContractAmendment->value,
            DocumentVariant::Generated,
        );

        $this->get(sprintf('/customers/%s/documents/manage', self::CUSTOMER_ID));

        $this->assertResponseOk();
        $this->assertResponseContains(__('Generated Documents'));
        // The label alone would be the round's own name as well, so the row is what is looked for.
        $this->assertResponseContains(sprintf('/files/file-links/download/%s', $this->ourRound()->id));
        // Both sides at once is the point of standing on the customer: the papers of their
        // contracts used to be somewhere else entirely.
        $this->assertResponseContains(
            sprintf('/files/file-links/download/%s', $theContracts->id),
            'The papers of the contracts are missing from the customer they belong to.',
        );
    }

    /**
     * The signature is a date, and the scan of it arrives when the post does. Recording the one
     * therefore closes no door on the other.
     *
     * @link \App\Controller\DocumentsController::addPages()
     * @return void
     */
    public function testAScanIsStillFiledAfterTheSignatureHasBeenRecorded(): void
    {
        $round = $this->round();

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // The scans are handed over on their own here, so the token knows nothing about them.
        $this->setUnlockedFields(['papers']);

        $this->post('/customer-proposals/conclude/' . $round, ['conclusion_date' => '2026-10-05']);
        $this->assertRedirect();

        $scan = $this->scan();

        $this->replaceRequest(['files' => ['papers' => [
            new UploadedFile($scan, (int)filesize($scan), UPLOAD_ERR_OK, 'scan.pdf', null),
        ]]]);
        $this->post(
            '/documents/add-pages?proposal_id=' . $round . '&agenda=CustomerProposals',
            [
                'document_type' => $round . '/' . CustomerPrintType::GdprNew->value,
                'variant' => DocumentVariant::ReceivedSignedByCustomer->value,
            ],
        );
        $this->assertRedirect();
        $this->replaceRequest([]);
        unlink($scan);

        $this->assertSame(1, $this->filed(DocumentVariant::ReceivedSignedByCustomer));
    }

    /**
     * The round's own card shows what is filed against it, so that somebody reading what was
     * agreed to does not have to go looking for the papers.
     *
     * @link \App\Controller\CustomerProposalsController::view()
     * @return void
     */
    public function testTheRoundsCardShowsThePapersFiledAgainstIt(): void
    {
        $round = $this->round();
        $this->print($round);

        // The proposal's own page says how many there are and leads to them; the papers
        // themselves are read where they are worked on.
        $this->get(self::NESTED . '/customer-proposals/view/' . $round);

        $this->assertResponseOk();
        $this->assertResponseContains(__('Papers on File'));
        $this->assertResponseContains('proposal_id=' . $round);

        $this->get(sprintf('/customers/%s/documents/manage?proposal_id=%s', self::CUSTOMER_ID, $round));

        $this->assertResponseOk();
        $this->assertResponseContains(sprintf('/files/file-links/download/%s', $this->ourRound()->id));
    }

    /**
     * Asks for the paper the way the print page does.
     *
     * @param string $round Which round.
     * @return string The paper.
     */
    private function print(string $round): string
    {
        $this->get(sprintf(
            '/customers/%s/documents/generate.pdf?agenda=CustomerProposals&proposal_id=%s&document_type=%s',
            self::CUSTOMER_ID,
            $round,
            CustomerPrintType::GdprNew->value,
        ));

        $this->assertResponseOk();
        $this->assertNotNull($this->_response);

        return (string)$this->_response->getBody();
    }

    /**
     * A round to draw from.
     *
     * @param string $from The day it speaks about.
     * @return string Its id.
     */
    private function round(string $from = '2026-09-30'): string
    {
        $proposals = $this->getTableLocator()->get('CustomerProposals');

        return (string)$proposals->saveOrFail($proposals->newEntity([
            'customer_id' => self::CUSTOMER_ID,
            'purpose' => CustomerProposalPurpose::GdprConsent->value,
            'effective_from' => $from,
        ]))->get('id');
    }

    /**
     * Files a page against a round, without going the long way round through an upload.
     *
     * @param string $model Whose round it is.
     * @param string $foreign_key Which round.
     * @param string $document_type Which document.
     * @param \App\Model\Enum\DocumentVariant $variant Which side of the paper it is.
     * @return \Files\Model\Entity\FileLink
     */
    private function fileAgainst(
        string $model,
        string $foreign_key,
        string $document_type,
        DocumentVariant $variant = DocumentVariant::ReceivedSignedByCustomer,
    ): FileLink {
        $storage = new FileStorage();

        return $storage->link(
            $storage->store('%PDF-1.7 ' . $foreign_key . $document_type, 'application/pdf'),
            $model,
            $foreign_key,
            $document_type,
            $variant->value,
            ['name' => 'scan.pdf'],
        );
    }

    /**
     * The page filed against a round put to the customer themselves.
     *
     * @return \Files\Model\Entity\FileLink
     */
    private function ourRound(): FileLink
    {
        /** @var \Files\Model\Entity\FileLink $link */
        $link = $this->fetchTable('Files.FileLinks')->find()
            ->where(['model' => CustomerDocuments::MODEL])
            ->firstOrFail();

        return $link;
    }

    /**
     * A scan to hand over, as a file of its own on disk.
     *
     * @return string Where it is.
     */
    private function scan(): string
    {
        $path = TMP . uniqid('customer-scan-') . '.pdf';
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n");

        return $path;
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
     * @return int How many papers the rounds have in that hand.
     */
    private function filed(DocumentVariant $variant): int
    {
        return $this->fetchTable('Files.FileLinks')->find()
            ->where(['variant' => $variant->value])
            ->count();
    }

    /**
     * Takes a directory and everything under it away again.
     *
     * @param string $directory Which one.
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
