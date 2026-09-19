<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\CustomerPrint;

use App\Customers\Check\UnfiledCustomerSignatureCheck;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerDocumentType;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentsDeliveryType;
use App\Pdf\CustomerPDF;
use App\Proposals\DrawnPaper;
use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The list of the user's contracts and the services provided is drawn from a customer proposal,
 * kept once drawn, and only ever handed over - nobody signs it.
 */
#[UsesClass(CustomerPDF::class)]
#[UsesClass(DrawnPaper::class)]
class ServicesOverviewTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * A customer of the fixture without a single contract.
     */
    private const CUSTOMER_WITHOUT_CONTRACTS = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    /**
     * The fixture's customer proposal, and the contract proposal in it.
     */
    private const ROUND_ID = 'a7c1d5e2-3f48-4b90-9c61-2d0e7a5b8f34';
    private const CONTRACT_PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

    /**
     * The state both fixture contracts are in.
     */
    private const CONTRACT_STATE_ID = '3fc51c92-5dbb-4bd4-9a47-237169c2755c';

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
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'services-overview-' . uniqid();
        Configure::write('Files.root', $this->root);

        $this->login();
    }

    /**
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
     * Drawn once and kept: asked for again, the list says what it said on the day it was drawn.
     *
     * @return void
     */
    public function testTheListIsDrawnOnceAndKept(): void
    {
        $round = $this->round();

        $first = $this->print($round);
        $second = $this->print($round);

        $this->assertStringStartsWith('%PDF', $first);
        $this->assertSame($first, $second);
        $this->assertSame(1, $this->fetchTable('Files.FileLinks')->find()->where([
            'model' => 'CustomerProposals',
            'foreign_key' => $round,
            'document_type' => CustomerDocumentType::ServicesOverview->value,
        ])->count());
    }

    /**
     * The customer proposal offers the one paper its purpose is for.
     *
     * @return void
     */
    public function testTheWorkbenchOffersTheList(): void
    {
        $round = $this->round();

        $this->get(sprintf('/customers/%s/documents/manage?proposal_id=%s', self::CUSTOMER_ID, $round));

        $this->assertResponseOk();
        $this->assertResponseContains(h(CustomerDocumentType::ServicesOverview->label()));
        // Escaped once, by whatever draws it - the apostrophe in the name reads as one.
        $this->assertResponseNotContains('&amp;#039;');
        $this->assertResponseContains('document_type=' . CustomerDocumentType::ServicesOverview->value);
    }

    /**
     * A list naming nothing is not worth handing over, and the workbench says why.
     *
     * @return void
     */
    public function testACustomerWithoutContractsHasNothingToList(): void
    {
        $problems = (new DrawnPaper())->problemsWith(
            $this->roundEntity(self::CUSTOMER_WITHOUT_CONTRACTS),
            CustomerDocumentType::ServicesOverview->value,
        );

        $this->assertContains(__('The customer has no contracts whose services are provided.'), $problems);
    }

    /**
     * A contract whose services are no longer provided is not on the list, even while it is still
     * billed for the last time.
     *
     * @return void
     */
    public function testAnEndedContractIsNotListed(): void
    {
        $this->fetchTable('ContractStates')->updateAll(
            ['active_services' => false, 'billed' => true],
            ['id' => self::CONTRACT_STATE_ID],
        );

        $problems = (new DrawnPaper())->problemsWith(
            $this->roundEntity(self::CUSTOMER_ID),
            CustomerDocumentType::ServicesOverview->value,
        );

        $this->assertContains(__('The customer has no contracts whose services are provided.'), $problems);
    }

    /**
     * Only handed over: once it has gone out there is nothing left to do about it, and no
     * signature is waited for.
     *
     * @return void
     */
    public function testTheListIsDoneWithOnceSent(): void
    {
        $rounds = $this->fetchTable('CustomerProposals');
        $id = $this->round();

        $this->assertFalse($rounds->get($id, contain: ['ContractProposals'])->hasBeenDealtWith());

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customer-proposals/send/' . $id, [
            'sent_date' => '2026-09-01',
            'delivery_type' => DocumentsDeliveryType::cases()[0]->value,
        ]);
        $this->assertRedirect();

        $round = $rounds->get($id, contain: ['ContractProposals']);
        $this->assertTrue($round->hasBeenDealtWith());
        $this->assertEquals($round->sent_date, $round->conclusion_date);
        $this->assertSame(__('Delivered'), $round->getState());

        // Nobody signs it, so no signed copy of it is missing.
        $this->assertNotContains(
            $id,
            (new UnfiledCustomerSignatureCheck($rounds, false))->find()->all()->extract('id')->toList(),
        );
    }

    /**
     * A contract proposal comes back signed, so it does not go out in a proposal that is only
     * handed over.
     *
     * @return void
     */
    public function testAContractProposalDoesNotJoinAListThatIsOnlyHandedOver(): void
    {
        $proposals = $this->fetchTable('ContractProposals');
        $proposal = $proposals->patchEntity(
            $proposals->get(self::CONTRACT_PROPOSAL_ID),
            ['customer_proposal_id' => $this->round()],
        );

        $this->assertFalse($proposals->save($proposal));
        $this->assertArrayHasKey('papersJoinARoundThatIsSigned', $proposal->getError('customer_proposal_id'));
    }

    /**
     * Nor does a proposal holding contract proposals turn into one that is only handed over.
     *
     * @return void
     */
    public function testAProposalHoldingContractProposalsDoesNotBecomeAList(): void
    {
        $rounds = $this->fetchTable('CustomerProposals');
        $round = $rounds->patchEntity(
            $rounds->get(self::ROUND_ID),
            ['purpose' => CustomerProposalPurpose::ServicesOverview->value],
        );

        $this->assertFalse($rounds->save($round));
        $this->assertArrayHasKey('purposeFitsItsContractProposals', $round->getError('purpose'));
    }

    /**
     * The contract proposal form offers only the purposes a contract proposal may go out with.
     *
     * @return void
     */
    public function testContractProposalsAreOfferedOnlyPurposesThatAreSigned(): void
    {
        $offered = CustomerProposalPurpose::forContractProposals();

        $this->assertArrayHasKey(CustomerProposalPurpose::GdprConsent->value, $offered);
        $this->assertArrayNotHasKey(CustomerProposalPurpose::ServicesOverview->value, $offered);
    }

    /**
     * Asks for the list the way the workbench does.
     *
     * @param string $round Which customer proposal.
     * @return string The paper.
     */
    private function print(string $round): string
    {
        $this->get(sprintf(
            '/customers/%s/documents/generate.pdf?agenda=CustomerProposals&proposal_id=%s&document_type=%s',
            self::CUSTOMER_ID,
            $round,
            CustomerDocumentType::ServicesOverview->value,
        ));

        $this->assertResponseOk();

        return (string)$this->_response?->getBody();
    }

    /**
     * A customer proposal for the list.
     *
     * @return string Its id.
     */
    private function round(): string
    {
        $rounds = $this->fetchTable('CustomerProposals');

        return (string)$rounds->saveOrFail($this->roundEntity(self::CUSTOMER_ID))->get('id');
    }

    /**
     * The same, not yet saved.
     *
     * @param string $customer_id Whose.
     * @return \App\Model\Entity\CustomerProposal
     */
    private function roundEntity(string $customer_id): CustomerProposal
    {
        /** @var \App\Model\Entity\CustomerProposal $round */
        $round = $this->fetchTable('CustomerProposals')->newEntity([
            'customer_id' => $customer_id,
            'purpose' => CustomerProposalPurpose::ServicesOverview->value,
            'effective_from' => '2026-09-19',
        ]);

        return $round;
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
