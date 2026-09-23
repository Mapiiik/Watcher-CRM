<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Contracts\Proposal\ProposalDocumentTypes;
use App\Controller\ContractProposalsController;
use App\Model\Entity\ContractProposal;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\DocumentVariant;
use App\Model\Enum\ProposalPurpose;
use App\Proposals\DrawnPaper;
use App\Proposals\WhatIsOwed;
use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * A contract whose service keeps no versions - one only passed on, where the customer is the
 * provider's client rather than ours.
 *
 * Its proposals go the same way every other does, and change its billings and the contract itself;
 * they only never touch a version and never have a document of ours. What the other side writes may
 * still be filed.
 */
#[UsesClass(ContractProposalsController::class)]
class ProposalsWithoutVersionsTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';
    private const NESTED = '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID;

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
        'app.CustomerProposals',
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.ContractProposals',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
        'plugin.Settings.Settings',
    ];

    /**
     * The fixture's only service stops keeping versions for every test here.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->getTableLocator()->get('ServiceTypes')->updateAll(['have_contract_versions' => false], []);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * The form offers no version to choose and says why, and asks for the day instead.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheFormAsksForNoVersion(): void
    {
        $this->get(self::NESTED . '/contract-proposals/add');

        $this->assertResponseOk();
        $this->assertResponseNotContains('name="contract_version_id"');
        $this->assertResponseContains('does not use contract versions');
        $this->assertResponseContains('name="effective_from"');
    }

    /**
     * Without a version there is no day to take, so a proposal that does not say one is refused.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheDayHasToBeSaid(): void
    {
        $before = $this->idsIn('ContractProposals');

        $this->draw(ProposalPurpose::ServiceChange, $this->round(), ['effective_from' => '']);

        $this->assertNoRedirect();
        $this->assertSame($before, $this->idsIn('ContractProposals'));
    }

    /**
     * A change is photographed without a version, goes out and is signed with the rest, and is
     * applied without one either.
     *
     * @return void
     * @link \App\Contracts\Proposal\ChangeApplication::apply()
     */
    public function testAChangeIsAppliedWithoutAVersion(): void
    {
        $round = $this->round();
        $proposal = $this->draw(ProposalPurpose::ServiceChange, $round, ['effective_from' => '2026-10-01']);

        $this->assertFalse($proposal->keepsVersions());
        $this->assertNull($proposal->contract_version_id);
        $this->assertSame([], $proposal->stateOfThings()->part('version'));

        $versions = $this->idsIn('ContractVersions');
        $this->applyTheRound($round);

        $this->assertTrue($this->reread($proposal)->hasBeenApplied());
        $this->assertSame($versions, $this->idsIn('ContractVersions'));
    }

    /**
     * Even a new contract starts no version where the service keeps none.
     *
     * @return void
     * @link \App\Contracts\Proposal\ChangeApplication::apply()
     */
    public function testANewContractStartsNoVersion(): void
    {
        $round = $this->round();
        $proposal = $this->draw(ProposalPurpose::NewContract, $round, ['effective_from' => '2026-10-01']);

        $versions = $this->idsIn('ContractVersions');
        $this->applyTheRound($round);

        $this->assertTrue($this->reread($proposal)->hasBeenApplied());
        $this->assertSame($versions, $this->idsIn('ContractVersions'), 'A version was started all the same.');
    }

    /**
     * An ending says its day on the contract, there being no version to say it on.
     *
     * @return void
     * @link \App\Contracts\Proposal\ProposalForm::changesFrom()
     */
    public function testAnEndingEndsTheContractAlone(): void
    {
        $proposal = $this->draw(ProposalPurpose::Termination, $this->round(), ['ends_on' => '2026-10-31']);
        $changes = $proposal->proposedChanges();

        $this->assertTrue($changes->version->isEmpty());
        $this->assertSame('2026-10-31', (string)$changes->contract->get('termination_date')?->toDateString());
        $this->assertSame('2026-11-01', $proposal->effective_from?->toDateString());
    }

    /**
     * Nothing of ours is generated or owed, but what the other side writes may be filed.
     *
     * @return void
     * @link \App\Contracts\Proposal\ProposalDocumentTypes::for()
     */
    public function testNothingOfOursIsGeneratedButWhatComesBackMayBeFiled(): void
    {
        $proposal = $this->draw(ProposalPurpose::Termination, $this->round(), ['ends_on' => '2026-10-31']);

        $this->assertSame(
            ['termination-notice', 'death-certificate', 'other'],
            array_keys((new ProposalDocumentTypes())->options($proposal)),
        );
        $this->assertSame([], (new WhatIsOwed())->of($proposal));
        $this->assertNotSame([], (new DrawnPaper())->problemsWith($proposal, 'contract-termination'));
    }

    /**
     * A notice the customer sent to the provider is filed like any paper that came back.
     *
     * @return void
     * @link \App\Controller\DocumentsController::addPages()
     */
    public function testANoticeTheCustomerSentMayBeFiled(): void
    {
        $root = TMP . 'versionless-papers-' . uniqid();
        Configure::write('Files.root', $root);
        $scan = $root . '-notice.pdf';
        file_put_contents($scan, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n");

        $proposal = $this->draw(ProposalPurpose::Termination, $this->round(), ['ends_on' => '2026-10-31']);
        $at = self::NESTED . '/documents/add-pages?agenda=ContractProposals&proposal_id=' . $proposal->id;

        $this->get($at);
        $this->assertResponseOk();
        $this->assertResponseContains($proposal->id . '/termination-notice');

        $this->setUnlockedFields(['papers']);
        $this->replaceRequest(['files' => ['papers' => [
            new UploadedFile($scan, (int)filesize($scan), UPLOAD_ERR_OK, 'notice.pdf', null),
        ]]]);
        $this->post($at, [
            'document_type' => $proposal->id . '/termination-notice',
            'variant' => DocumentVariant::Received->value,
        ]);
        $this->assertRedirect();

        $this->assertSame(1, $this->getTableLocator()->get('Files.FileLinks')->find()
            ->where(['foreign_key' => $proposal->id, 'document_type' => 'termination-notice'])
            ->count());

        Configure::delete('Files.root');
    }

    /**
     * A customer proposal to put the papers in, asking nothing of the customer themselves.
     *
     * @return \App\Model\Entity\CustomerProposal
     */
    private function round(): CustomerProposal
    {
        $before = $this->idsIn('CustomerProposals');

        $this->post('/customers/' . self::CUSTOMER_ID . '/customer-proposals/add', [
            'customer_id' => self::CUSTOMER_ID,
            'purpose' => '',
            'effective_from' => '2026-09-30',
        ]);
        $this->assertRedirectContains('/customer-proposals/view/');

        /** @var \App\Model\Entity\CustomerProposal $round */
        $round = $this->addedRecord('CustomerProposals', $before);

        return $round;
    }

    /**
     * Creates a contract proposal the one way there is.
     *
     * @param \App\Model\Enum\ProposalPurpose $purpose What it is for.
     * @param \App\Model\Entity\CustomerProposal $round The customer proposal it is part of.
     * @param array<string, mixed> $said The rest of what the form sends.
     * @return \App\Model\Entity\ContractProposal
     */
    private function draw(ProposalPurpose $purpose, CustomerProposal $round, array $said): ContractProposal
    {
        $before = $this->idsIn('ContractProposals');

        $this->post(self::NESTED . '/contract-proposals/add', $said + [
            'purpose' => $purpose->value,
            'contract_id' => self::CONTRACT_ID,
            'customer_proposal_id' => $round->id,
        ]);

        if ($this->idsIn('ContractProposals') === $before) {
            return new ContractProposal();
        }

        $this->assertRedirectContains('/contract-proposals/view/');

        /** @var \App\Model\Entity\ContractProposal $proposal */
        $proposal = $this->addedRecord('ContractProposals', $before);

        return $this->reread($proposal);
    }

    /**
     * Sends the customer proposal, records the signature and applies the changes.
     *
     * @param \App\Model\Entity\CustomerProposal $round The customer proposal.
     * @return void
     */
    private function applyTheRound(CustomerProposal $round): void
    {
        $at = '/customers/' . self::CUSTOMER_ID . '/customer-proposals/';

        $this->post($at . 'send/' . $round->id, [
            'sent_date' => '2026-09-30',
            'delivery_type' => DocumentsDeliveryType::Post->value,
        ]);
        $this->post($at . 'conclude/' . $round->id, ['conclusion_date' => '2026-09-30']);
        $this->post($at . 'apply-changes/' . $round->id);
        $this->assertRedirectContains('/customer-proposals/view/' . $round->id);
    }

    /**
     * The proposal as it now stands, with the customer proposal it reads its days from.
     *
     * @param \App\Model\Entity\ContractProposal $proposal The proposal.
     * @return \App\Model\Entity\ContractProposal
     */
    private function reread(ContractProposal $proposal): ContractProposal
    {
        /** @var \App\Model\Entity\ContractProposal $fresh */
        $fresh = $this->getTableLocator()->get('ContractProposals')
            ->get($proposal->id, contain: ['CustomerProposals']);

        return $fresh;
    }
}
