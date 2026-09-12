<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContractProposalsController;
use App\Model\Enum\DocumentVariant;
use App\Service\ContractPrint\ContractDocuments;
use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Files\Model\Entity\FileLink;
use Laminas\Diactoros\UploadedFile;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The papers on a proposal: what arrives, what order it goes in, and what letting go of one means.
 *
 * Kept apart from the proposal's own controller test, which is about drawing proposals up. What is
 * asked here is only ever about the shelf.
 */
#[UsesClass(ContractProposalsController::class)]
class ContractProposalsDocumentsTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * The proposal the papers hang on, and the contract it belongs to.
     *
     * @var string
     */
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * The document the scans are of.
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
        'app.ContractProposals',
        'app.IpAddresses',
        'app.IpNetworks',
        'app.SoldEquipments',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
    ];

    /**
     * Where the papers go while this runs, and the scans they are filed from.
     *
     * @var string
     */
    private string $root;

    private string $scans;

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'proposal-papers-' . uniqid();
        $this->scans = TMP . 'proposal-scans-' . uniqid();
        mkdir($this->scans, 0777, true);
        Configure::write('Files.root', $this->root);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        // PHP merges what was uploaded into the request body, and the real forms secure the field
        // that carries it. Here the scans are handed over on their own, so the token knows nothing
        // about them - which is a fact about the test harness rather than about the pages.
        $this->setUnlockedFields(['papers']);
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
        $this->removeDirectory($this->scans);

        parent::tearDown();
    }

    /**
     * The page renders with nothing on file, which is how every proposal starts.
     *
     * @link \App\Controller\ContractProposalsController::documents()
     * @return void
     */
    public function testThePapersRenderBeforeAnythingHasBeenFiled(): void
    {
        $this->get('/contract-proposals/documents/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
    }

    /**
     * The overviews the two cards link to render, and what several pages have in common is said
     * once down the side rather than repeated on every row.
     *
     * @link \App\Controller\ContractsController::documents()
     * @link \App\Controller\CustomersController::documents()
     * @return void
     */
    public function testTheOverviewsRender(): void
    {
        $this->addPages(['one.pdf', 'two.pdf', 'three.pdf']);

        $this->get('/contracts/documents/' . self::CONTRACT_ID);
        $this->assertResponseOk();
        $this->assertResponseContains('two.pdf');
        $this->assertResponseContains('rowspan="3"');

        $this->get('/customers/documents/' . self::CUSTOMER_ID);
        $this->assertResponseOk();
        $this->assertResponseContains('two.pdf');
        $this->assertResponseContains('rowspan="3"');
    }

    /**
     * The overviews are reachable under the customer and the contract they belong to, the way
     * printing is, and the pages link to each other in that shape.
     *
     * @link \App\Controller\ContractsController::documents()
     * @link \App\Controller\CustomersController::documents()
     * @return void
     */
    public function testTheOverviewsSitUnderWhatTheyBelongTo(): void
    {
        $nested = sprintf('/customers/%s/contracts/%s/documents', self::CUSTOMER_ID, self::CONTRACT_ID);

        $this->get($nested);
        $this->assertResponseOk();

        $this->get(sprintf('/customers/%s/documents', self::CUSTOMER_ID));
        $this->assertResponseOk();

        // And the two pages point at each other, in the same shape and under the same name they
        // are known by everywhere else.
        $printing = sprintf('/customers/%s/contracts/%s/print', self::CUSTOMER_ID, self::CONTRACT_ID);

        $this->get($printing);
        $this->assertResponseOk();
        $this->assertResponseContains($nested);

        $this->get($nested);
        $this->assertResponseContains($printing);
        $this->assertResponseContains('Print to PDF');
    }

    /**
     * The order the browser sends them in is the order they go on the shelf, because for a set of
     * scans that is usually their own numbering.
     *
     * @link \App\Controller\ContractProposalsController::addPages()
     * @return void
     */
    public function testPagesAreFiledInTheOrderTheyWerePicked(): void
    {
        $this->addPages(['one.pdf', 'two.png', 'three.pdf']);

        $this->assertSame(['one.pdf', 'two.png', 'three.pdf'], $this->namesOnFile());
    }

    /**
     * More pages go after the ones already there rather than among them.
     *
     * @link \App\Controller\ContractProposalsController::addPages()
     * @return void
     */
    public function testMorePagesGoOnTheEnd(): void
    {
        $this->addPages(['one.pdf']);
        $this->addPages(['two.pdf']);

        $this->assertSame(['one.pdf', 'two.pdf'], $this->namesOnFile());
        $this->assertSame([0, 1], $this->positionsOnFile());
    }

    /**
     * A page moves past the one beside it, and stays where it is at either end.
     *
     * @link \App\Controller\ContractProposalsController::movePage()
     * @return void
     */
    public function testAPageMovesPastTheOneBesideIt(): void
    {
        $this->addPages(['one.pdf', 'two.pdf', 'three.pdf']);

        $this->move($this->linkOf('three.pdf'), 'up');
        $this->assertSame(['one.pdf', 'three.pdf', 'two.pdf'], $this->namesOnFile());

        $this->move($this->linkOf('one.pdf'), 'up');
        $this->assertSame(['one.pdf', 'three.pdf', 'two.pdf'], $this->namesOnFile(), 'The first page moved up.');

        $this->move($this->linkOf('two.pdf'), 'down');
        $this->assertSame(['one.pdf', 'three.pdf', 'two.pdf'], $this->namesOnFile(), 'The last page moved down.');
    }

    /**
     * Letting go of a page closes the gap it leaves, so the position still means the page.
     *
     * @link \App\Controller\ContractProposalsController::dropPage()
     * @return void
     */
    public function testDroppingAPageClosesTheGap(): void
    {
        $this->addPages(['one.pdf', 'two.pdf', 'three.pdf']);

        $this->drop($this->linkOf('two.pdf'));

        $this->assertSame(['one.pdf', 'three.pdf'], $this->namesOnFile());
        $this->assertSame([0, 1], $this->positionsOnFile());
    }

    /**
     * A page reached through the wrong proposal is not a page at all.
     *
     * @link \App\Controller\ContractProposalsController::dropPage()
     * @return void
     */
    public function testAPageIsOnlyReachableThroughItsOwnProposal(): void
    {
        $this->addPages(['one.pdf']);

        $this->post(sprintf(
            '/contract-proposals/drop-page/%s/%s',
            'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f61',
            $this->linkOf('one.pdf'),
        ));

        $this->assertResponseError();
        $this->assertSame(['one.pdf'], $this->namesOnFile());
    }

    /**
     * Letting go of a paper we drew up is unfreezing it: the next request for the document draws
     * it afresh rather than handing back what is no longer there.
     *
     * @link \App\Controller\ContractProposalsController::dropPage()
     * @return void
     */
    public function testLettingGoOfWhatWeDrewUpUnfreezesIt(): void
    {
        $this->print();
        $drawn = $this->fetchTable('Files.FileLinks')->find()->firstOrFail();

        $this->drop((string)$drawn->get('id'));
        $this->assertSame(0, $this->fetchTable('Files.FileLinks')->find()->count());

        $this->print();
        $this->assertSame(1, $this->fetchTable('Files.FileLinks')->find()->count());
    }

    /**
     * Unfreezing is the administrator's to do. Everyone who files scans may correct their own,
     * but letting a drawn-up paper go changes what the customer would be handed next time.
     *
     * @link \App\Controller\ContractProposalsController::dropPage()
     * @return void
     */
    public function testOnlyTheAdministratorMayUnfreezeADrawnUpPaper(): void
    {
        $this->addPages(['scan.pdf']);
        $this->print();

        $drawn = $this->fetchTable('Files.FileLinks')->find()
            ->where(['variant' => DocumentVariant::Generated->value])
            ->firstOrFail();

        $this->login('sales-representative');

        $this->post('/contract-proposals/drop-page/' . self::PROPOSAL_ID . '/' . $drawn->get('id'));
        $this->assertSame(
            1,
            $this->fetchTable('Files.FileLinks')->find()
                ->where(['variant' => DocumentVariant::Generated->value])
                ->count(),
            'The paper we drew up was unfrozen by somebody who may not.',
        );

        $this->post('/contract-proposals/drop-page/' . self::PROPOSAL_ID . '/' . $this->linkOf('scan.pdf'));
        $this->assertSame([], $this->namesOnFile());
    }

    /**
     * What is not a paper does not go on the shelf, and the operator is told why rather than
     * being left to wonder.
     *
     * @link \App\Proposals\ProposalPapers::take()
     * @return void
     */
    public function testSomethingThatIsNotAPaperIsRefused(): void
    {
        $this->addPages(['notes.txt'], false);

        $this->assertSame(0, $this->fetchTable('Files.FileLinks')->find()->count());
        // The form comes back with the reason on it, so the flash has already been drawn into the
        // page by the time the session is looked at.
        $this->assertResponseContains('text/plain');
    }

    /**
     * A page the server would not take is named, rather than coming out as nothing having been
     * chosen. That is what the operator saw when the limit was two megabytes.
     *
     * @link \App\Proposals\ProposalPapers::take()
     * @return void
     */
    public function testAPageTheServerTurnedAwayIsNamed(): void
    {
        $path = $this->file('scan.png');

        $this->replaceRequest(['files' => ['papers' => [
            new UploadedFile($path, (int)filesize($path), UPLOAD_ERR_INI_SIZE, 'too-big.png', null),
        ]]]);
        $this->post('/contract-proposals/add-pages/' . self::PROPOSAL_ID, [
            'document_type' => self::DOCUMENT,
            'variant' => DocumentVariant::ReceivedSignedByCustomer->value,
        ]);
        $this->replaceRequest([]);

        $this->assertResponseOk();
        // The name is what makes it actionable, and it is what the old answer never said.
        $this->assertResponseContains('too-big.png');
        $this->assertSame(0, $this->fetchTable('Files.FileLinks')->find()->count());
    }

    /**
     * An empty slot is not a refusal. A form offering every document at once has one for each,
     * and most of them are left alone.
     *
     * @link \App\Proposals\ProposalPapers::take()
     * @return void
     */
    public function testASlotNobodyFilledInIsPassedOver(): void
    {
        $path = $this->file('scan.png');

        $this->replaceRequest(['files' => ['papers' => [
            new UploadedFile($path, 0, UPLOAD_ERR_NO_FILE, '', null),
            $this->upload($this->file('scan.png')),
        ]]]);
        $this->post('/contract-proposals/add-pages/' . self::PROPOSAL_ID, [
            'document_type' => self::DOCUMENT,
            'variant' => DocumentVariant::ReceivedSignedByCustomer->value,
        ]);
        $this->replaceRequest([]);

        $this->assertRedirect();
        $this->assertSame(1, $this->fetchTable('Files.FileLinks')->find()->count());
    }

    /**
     * The pages of one document are offered as one group to look through.
     *
     * The variant cell already spans exactly those pages, so the mark sits in it and carries the
     * whole group with it - the viewer never has to ask a second time.
     *
     * @link \Files\View\Helper\PreviewHelper::flipThrough()
     * @return void
     */
    public function testThePagesOfOneDocumentAreOfferedAsOneGroup(): void
    {
        $this->addPages(['scan.png', 'scan.png']);

        $this->get('/contract-proposals/documents/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('data-files-gallery');
        // The viewer comes from the layout, because a cell cannot reach the layout's blocks.
        $this->assertResponseContains('glightbox.min.js', 'the viewer itself is fetched');

        /** @var iterable<\Files\Model\Entity\FileLink> $links */
        $links = $this->fetchTable('Files.FileLinks')->find()->all();
        $counted = 0;
        foreach ($links as $link) {
            $this->assertResponseContains((string)$link->id, 'every page travels with the mark');
            $counted++;
        }

        $this->assertSame(2, $counted, 'both pages were filed');
    }

    /**
     * A paper is shown in a frame, and the viewer has its own word for that.
     *
     * `external` is what it calls a framed page. `iframe` is not a kind it knows, and a kind it
     * does not know is drawn as a picture - which for a PDF is a slide that stays blank, with no
     * error anywhere to say so.
     *
     * @link \Files\Service\Viewable::typeOf()
     * @return void
     */
    public function testAPaperIsOfferedAsSomethingToFrame(): void
    {
        $this->addPages(['scan.pdf']);

        $this->get('/contract-proposals/documents/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('&quot;type&quot;:&quot;external&quot;');
        $this->assertResponseNotContains('&quot;type&quot;:&quot;iframe&quot;');
    }

    /**
     * A page being looked at says what it is and which of how many it is.
     *
     * What the group is called comes from here rather than from the plugin, which knows a record
     * as a model and a key and nothing else. Which page it is, and which file, the plugin can say
     * for itself.
     *
     * Read out of the group the mark carries rather than looked for in the body: every one of
     * these words is in the table as well, so a search over the page would pass without the
     * viewer being told anything at all.
     *
     * @link \Files\View\Helper\PreviewHelper::flipThrough()
     * @return void
     */
    public function testEachPageSaysWhatItIsAndWhereItSits(): void
    {
        $this->addPages(['first.png', 'second.png']);

        $this->get('/contract-proposals/documents/' . self::PROPOSAL_ID);

        $this->assertResponseOk();

        $pages = $this->pagesOfTheMark();
        $this->assertCount(2, $pages);

        // The variant is the application's word for it, and it reaches the viewer unchanged.
        $variant = DocumentVariant::ReceivedSignedByCustomer->label();
        $this->assertStringContainsString($variant, $pages[0]['title']);

        // The file itself is the line underneath, because it is the only part that changes, and
        // the count is held at the far end of that line rather than trailing the filename.
        $this->assertStringContainsString('first.png', $pages[0]['description']);
        $this->assertStringContainsString('second.png', $pages[1]['description']);

        // Counted only where there is more than one, and counted over what can be shown.
        $this->assertStringEndsWith('<span class="files-page">1/2</span>', $pages[0]['description']);
        $this->assertStringEndsWith('<span class="files-page">2/2</span>', $pages[1]['description']);
        $this->assertStringNotContainsString('1/2', $pages[0]['title']);
    }

    /**
     * Where the papers are worked on, each page shows what it looks like. Everywhere else the
     * table stays words.
     *
     * A separate question from whether the pages may be reordered: a listing can want the
     * pictures without being able to let go of anything, and the other way round.
     *
     * @link \App\View\Cell\DocumentsCell::display()
     * @return void
     */
    public function testThePicturesAreOnlyWhereThePapersAreWorkedOn(): void
    {
        $this->addPages(['first.png', 'second.png']);

        $this->get('/contract-proposals/documents/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('files-thumb');
        $this->assertResponseContains('/files/documents/thumbnail/');

        // The wide listings would lose more in readability than the pictures give back.
        $this->get('/contracts/documents/' . self::CONTRACT_ID);

        $this->assertResponseOk();
        $this->assertResponseNotContains('files-thumb');
    }

    /**
     * A picture in the listing opens the group at its own page, and does it without carrying the
     * group a second time.
     *
     * @link \Files\View\Helper\PreviewHelper::pageMark()
     * @return void
     */
    public function testAPictureOpensTheGroupAtItsOwnPage(): void
    {
        $this->addPages(['first.png', 'second.png']);

        $this->get('/contract-proposals/documents/' . self::PROPOSAL_ID);

        $this->assertResponseOk();

        $body = (string)$this->_getBodyAsString();

        // One mark carries the pages. The rest only name the group they belong to.
        $this->assertSame(1, substr_count($body, 'data-files-pages'));
        $this->assertGreaterThan(1, substr_count($body, 'data-files-gallery'));

        // And each says which page of it to open at.
        $this->assertMatchesRegularExpression('/data-files-start="0"/', $body);
        $this->assertMatchesRegularExpression('/data-files-start="1"/', $body);
    }

    /**
     * The name of a page opens the group at that page, the same as its picture does.
     *
     * It is what somebody reads to find the page they want, and on a listing showing no pictures
     * it is the only thing there is to reach for.
     *
     * @link \Files\View\Helper\PreviewHelper::pageName()
     * @return void
     */
    public function testTheNameOfAPageOpensTheGroupThere(): void
    {
        $this->addPages(['first.png', 'second.png']);

        $this->get('/contract-proposals/documents/' . self::PROPOSAL_ID);

        $this->assertResponseOk();

        $body = (string)$this->_getBodyAsString();

        // The name is a link to the file, and it says which page of the group it stands for.
        $this->assertMatchesRegularExpression(
            '~<a[^>]+data-files-start="1"[^>]*>second\.png</a>~',
            $body,
        );

        // Still only the one carrier of the group, however many marks point at it.
        $this->assertSame(1, substr_count($body, 'data-files-pages'));
    }

    /**
     * The strip under the page being read needs a picture of each of the others, so every page
     * carries where its own is to be had.
     *
     * Given whether or not there is anything at the end of it yet: a picture is made the first
     * time it is asked for, and a strip that waited for the making would show nothing at all.
     *
     * @link \Files\View\Helper\PreviewHelper::flipThrough()
     * @return void
     */
    public function testEachPageSaysWhereItsOwnPictureIs(): void
    {
        $this->addPages(['first.png', 'second.png']);

        $this->get('/contract-proposals/documents/' . self::PROPOSAL_ID);

        $this->assertResponseOk();

        foreach ($this->pagesOfTheMark() as $page) {
            $this->assertArrayHasKey('thumb', $page);
            $this->assertStringContainsString('/files/documents/thumbnail/', $page['thumb']);
            // The picture of a page and the page itself are two different things to ask for.
            $this->assertNotSame($page['href'], $page['thumb']);
        }
    }

    /**
     * Where the table spans several rounds, the viewer says which round a page came from.
     *
     * And says it as a sentence. The column wants the day first so that a table reads down its
     * left edge, but the viewer reads across one line, and a line of four things separated by
     * dashes is not a line anybody reads.
     *
     * @link \App\View\Cell\DocumentsCell::rows()
     * @return void
     */
    public function testOnAContractThePageSaysWhichRoundItCameFrom(): void
    {
        $this->addPages(['first.png', 'second.png']);

        $this->get('/contracts/documents/' . self::CONTRACT_ID);

        $this->assertResponseOk();

        /** @var \App\Model\Entity\ContractProposal $proposal */
        $proposal = $this->fetchTable('ContractProposals')->get(self::PROPOSAL_ID);
        $title = $this->pagesOfTheMark()[0]['title'];

        $this->assertStringContainsString(
            __('{0} from {1}', $proposal->purpose->label(), $proposal->effective_from),
            $title,
        );
        $this->assertStringNotContainsString(
            $proposal->effective_from . ' - ' . $proposal->purpose->label(),
            $title,
            'the column reads down, the viewer reads across',
        );
    }

    /**
     * One page of a document carries no count, since `1/1` says nothing.
     *
     * @link \Files\View\Helper\PreviewHelper::titleOf()
     * @return void
     */
    public function testOnePageIsNotCounted(): void
    {
        $this->addPages(['only.png']);

        $this->get('/contract-proposals/documents/' . self::PROPOSAL_ID);

        $pages = $this->pagesOfTheMark();
        $this->assertCount(1, $pages);
        $this->assertStringNotContainsString('files-page', $pages[0]['description']);
    }

    /**
     * The group as the mark hands it to the viewer.
     *
     * @return list<array<string, string>>
     */
    private function pagesOfTheMark(): array
    {
        preg_match('/data-files-pages="([^"]*)"/', (string)$this->_getBodyAsString(), $found);
        $carried = $found[1] ?? '';
        $this->assertNotSame('', $carried, 'the page carries a mark to look through');

        /** @var list<array<string, string>> $pages */
        $pages = json_decode(html_entity_decode($carried, ENT_QUOTES), true);

        return $pages;
    }

    /**
     * The form asks before it files, like every other way of adding something.
     *
     * @link \App\Controller\ContractProposalsController::addPages()
     * @return void
     */
    public function testTheFormForANewDocumentRenders(): void
    {
        $this->get('/contract-proposals/add-pages/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('papers[]');
    }

    /**
     * The signature form offers the documents this proposal was printed as, because nothing else
     * can have come back - and files what comes with it in one go.
     *
     * @link \App\Controller\ContractProposalsController::conclude()
     * @return void
     */
    public function testTheScansComeInWithTheSignature(): void
    {
        $this->print();

        $this->get('/contract-proposals/conclude/' . self::PROPOSAL_ID);
        $this->assertResponseOk();
        $this->assertResponseContains('papers[' . self::DOCUMENT . '][]');

        $this->replaceRequest(['files' => [
            'papers' => [self::DOCUMENT => [$this->upload($this->file('signed.pdf'))]],
        ]]);
        $this->post('/contract-proposals/conclude/' . self::PROPOSAL_ID, [
            'conclusion_date' => '2026-10-05',
            'variants' => [self::DOCUMENT => DocumentVariant::ReceivedSignedByBoth->value],
        ]);

        $this->assertRedirect();
        $this->assertSame(1, $this->fetchTable('Files.FileLinks')->find()
            ->where(['variant' => DocumentVariant::ReceivedSignedByBoth->value])
            ->count());
    }

    /**
     * Asks for the document the way the print page does, which is what puts a drawn-up paper on
     * the shelf.
     *
     * @return void
     */
    private function print(): void
    {
        $this->get(sprintf(
            '/contracts/print/%s.pdf?proposal_id=%s&document_type=%s',
            self::CONTRACT_ID,
            self::PROPOSAL_ID,
            self::DOCUMENT,
        ));

        $this->assertResponseOk();
    }

    /**
     * Files the named scans against the proposal.
     *
     * @param array<string> $names What to send, named as they would arrive.
     * @param bool $expectFiling Whether the form is expected to be done with rather than redrawn.
     * @return void
     */
    private function addPages(array $names, bool $expectFiling = true): void
    {
        $files = [];
        foreach ($names as $name) {
            $files[] = $this->upload($this->file($name));
        }

        // replaceRequest rather than configRequest: the latter piles the scans of one request
        // onto the next, and what is left over would then reach a page that asked for nothing.
        $this->replaceRequest(['files' => ['papers' => $files]]);
        $this->post('/contract-proposals/add-pages/' . self::PROPOSAL_ID, [
            'document_type' => self::DOCUMENT,
            'variant' => DocumentVariant::ReceivedSignedByCustomer->value,
        ]);

        $expectFiling ? $this->assertRedirect() : $this->assertResponseOk();
        $this->replaceRequest([]);
    }

    /**
     * @param string $link Which page.
     * @param string $direction Which way.
     * @return void
     */
    private function move(string $link, string $direction): void
    {
        $this->post(sprintf(
            '/contract-proposals/move-page/%s/%s/%s',
            self::PROPOSAL_ID,
            $link,
            $direction,
        ));

        $this->assertRedirect();
    }

    /**
     * @param string $link Which page.
     * @return void
     */
    private function drop(string $link): void
    {
        $this->post('/contract-proposals/drop-page/' . self::PROPOSAL_ID . '/' . $link);

        $this->assertRedirect();
    }

    /**
     * Writes a scan out for PHP to hand over, with content its own extension answers for.
     *
     * @param string $name What it arrived called.
     * @return string Where it is.
     */
    private function file(string $name): string
    {
        $path = $this->scans . DS . uniqid() . '-' . $name;

        file_put_contents($path, match (pathinfo($name, PATHINFO_EXTENSION)) {
            'png' => (string)base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            ),
            'pdf' => "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
            default => "a note to self\n",
        });

        return $path;
    }

    /**
     * The scan as PHP would hand it over.
     *
     * @param string $path Where it is.
     * @return \Laminas\Diactoros\UploadedFile
     */
    private function upload(string $path): UploadedFile
    {
        return new UploadedFile(
            $path,
            (int)filesize($path),
            UPLOAD_ERR_OK,
            substr(basename($path), strpos(basename($path), '-') + 1),
            null,
        );
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

        foreach ((array)scandir($directory) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . DS . $entry;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }

    /**
     * @param string $name Which page.
     * @return string Its link id.
     */
    private function linkOf(string $name): string
    {
        return (string)$this->fetchTable('Files.FileLinks')->find()
            ->where(['name' => $name])
            ->firstOrFail()
            ->get('id');
    }

    /**
     * @return array<string> The pages as they read.
     */
    private function namesOnFile(): array
    {
        return array_map(fn(FileLink $link): string => (string)$link->name, $this->group());
    }

    /**
     * @return array<int> The numbers they carry.
     */
    private function positionsOnFile(): array
    {
        return array_map(fn(FileLink $link): int => $link->position, $this->group());
    }

    /**
     * @return list<\Files\Model\Entity\FileLink> The scans, in order.
     */
    private function group(): array
    {
        /** @var list<\Files\Model\Entity\FileLink> $group */
        $group = $this->fetchTable('Files.FileLinks')->find(
            'group',
            model: ContractDocuments::MODEL,
            foreign_key: self::PROPOSAL_ID,
            document_type: self::DOCUMENT,
            variant: DocumentVariant::ReceivedSignedByCustomer->value,
        )->all()->toList();

        return $group;
    }
}
