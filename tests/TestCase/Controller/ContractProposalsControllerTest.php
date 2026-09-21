<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Contracts\Proposal\ProposedBilling;
use App\Contracts\TheUsualTerm;
use App\Controller\ContractProposalsController;
use App\Model\Enum\ContractDocumentType;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\DocumentVariant;
use App\Model\Enum\ProposalPurpose;
use App\Service\ContractPrint\ContractDocuments;
use App\Test\Traits\ControllerTestTrait;
use Cake\Core\Configure;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Files\Service\FileStorage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\ContractProposalsController Test Case
 *
 * Mostly smoke tests: every action is requested once and has to answer. Two of them go further,
 * because they are the ones that would let a paper and the record behind it part company - sending
 * settles the proposal, and taking the snapshot again has to survive a billing having gone.
 */
#[UsesClass(ContractProposalsController::class)]
class ContractProposalsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Contract the nested routes hang off.
     *
     * @var string
     */
    /**
     * Where these pages belong: a proposal is reached under the record it is for, and asked
     * for anywhere else it is sent here.
     *
     * @var string
     */
    private const NESTED = '/customers/403bab0e-52cd-4a8e-83f8-43c2457d0481'
        . '/contracts/7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * The proposal the fixture carries: open, unsent, changing nothing.
     *
     * @var string
     */
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

    /**
     * The proposal those papers are a part of. Sending, signing and applying the changes happen
     * there and reach everything in it.
     *
     * @var string
     */
    private const ROUND_ID = 'a7c1d5e2-3f48-4b90-9c61-2d0e7a5b8f34';

    /**
     * The version the fixture carries: concluded, so papers over it take effect on a day of their
     * own and the form asks for one.
     *
     * @var string
     */
    private const CONTRACT_VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';

    /**
     * A billing the fixture proposal's snapshot knows about.
     *
     * @var string
     */
    private const KNOWN_BILLING_ID = 'b2000000-0000-4000-8000-000000000002';

    /**
     * And one the fixtures stopped long before any of these papers.
     *
     * @var string
     */
    private const CLOSED_BILLING_ID = 'b1000000-0000-4000-8000-000000000001';

    /**
     * A service still sold, and one the fixtures mark as no longer offered.
     */
    private const OPEN_SERVICE_ID = '5f6a2f47-0a4d-4c05-9bcb-2f0dc0a3f0d2';

    private const RETIRED_SERVICE_ID = 'eaacfeb3-1430-43ce-842e-497c5c95d953';

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
        'app.Queues',
        'app.Services',
        'app.Contracts',
        'app.ContractVersions',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.SoldEquipments',
        'app.IpAddresses',
        'app.IpNetworks',
        'app.CustomerProposals',
        'app.ContractProposals',
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
    ];

    /**
     * The detail renders.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get(self::NESTED . '/contract-proposals/view/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
    }

    /**
     * The form for a new proposal renders.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/customers/403bab0e-52cd-4a8e-83f8-43c2457d0481/contracts/'
            . self::CONTRACT_ID . '/contract-proposals/add');

        $this->assertResponseOk();
    }

    /**
     * A link from the contract's own pages settles which contract the papers are for, so the form
     * has the versions of that contract to choose from rather than an empty list.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheFormFollowsTheContractTheLinkNamed(): void
    {
        $this->login();
        $this->get('/contract-proposals/add?contract_id=' . self::CONTRACT_ID);

        $this->assertResponseOk();
        $this->assertNotEmpty($this->viewVariable('versions')->toArray());
    }

    /**
     * The day the papers apply from may be left empty, and then follows the version. It is left
     * empty rather than filled in ahead of time on purpose: a day put there for the operator would
     * stay behind when they chose another version, and read as theirs.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheDayThePapersApplyFromFollowsTheVersionWhenLeftEmpty(): void
    {
        $this->login();
        $this->get('/contract-proposals/add?purpose=' . ProposalPurpose::ServiceChange->value
            . '&contract_version_id=' . self::CONTRACT_VERSION_ID);

        $this->assertResponseOk();
        $this->assertResponseNotContains('value="2022-11-30"');
        // The hint names that day, written the way the application writes days.
        $day = $this->getTableLocator()->get('ContractVersions')
            ->get(self::CONTRACT_VERSION_ID)
            ->get('valid_from');
        $this->assertResponseContains('the start of the version is used (' . $day . ')');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::ServiceChange->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => self::CONTRACT_VERSION_ID,
            'effective_from' => '',
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertRedirect();

        /** @var \App\Model\Entity\ContractProposal $drawn */
        $drawn = $this->getTableLocator()->get('ContractProposals')
            ->find()
            ->orderByDesc('created')
            ->firstOrFail();

        $this->assertSame('2022-11-30', $drawn->effective_from->toDateString());
    }

    /**
     * Saying nothing about which version the papers are for is said on that field, not on the
     * columns behind the snapshot - those are not on the form, and an error nobody can see reads
     * as three required fields with nothing marked.
     *
     * Asked of a change, because that is one of the purposes that has to name a version: it amends
     * one that was already agreed to, so there is nothing for it to start.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testAProposalWithoutAVersionSaysSoWhereItCanBeSeen(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::ServiceChange->value,
            'contract_id' => self::CONTRACT_ID,
            'effective_from' => '2026-11-01',
        ]);

        $this->assertResponseOk();

        $errors = $this->viewVariable('contractProposal')->getErrors();
        $this->assertArrayHasKey('contract_version_id', $errors);
        $this->assertArrayNotHasKey('snapshot', $errors);
        $this->assertArrayNotHasKey('snapshot_taken', $errors);
    }

    /**
     * Papers for a new contract may be drawn up before the version they are about exists. The
     * snapshot is taken of the version as it will be, so there is one to print from, and the
     * version itself waits for the papers to be applied.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testANewContractMayBeProposedBeforeItsVersionExists(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::NewContract->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => '',
            'effective_from' => '2026-11-01',
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertRedirect();

        /** @var \App\Model\Entity\ContractProposal $drawn */
        $drawn = $this->getTableLocator()->get('ContractProposals')
            ->find()
            ->orderByDesc('created')
            ->firstOrFail();

        $this->assertNull($drawn->contract_version_id);
        $this->assertSame('2026-11-01', $drawn->effective_from->toDateString());

        // The papers have a version to print from all the same, and it starts the day they do.
        $taken = $drawn->stateOfThings()->part('version');
        $this->assertNull($taken['id']);
        $this->assertSame('2026-11-01', $taken['valid_from']);
    }

    /**
     * There is one proposal and it is put to the customer. Papers drawn up from the contract
     * rather than from a proposal are given one of their own, so nothing stands outside one.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testPapersDrawnUpOnTheirOwnGetAProposalToBePartOf(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->getTableLocator()->get('CustomerProposals')->find()->count();

        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::NewContract->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => '',
            'effective_from' => '2026-11-01',
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);
        $this->assertRedirect();

        /** @var \App\Model\Entity\ContractProposal $drawn */
        $drawn = $this->getTableLocator()->get('ContractProposals')
            ->find()
            ->orderByDesc('created')
            ->firstOrFail();

        $this->assertNotNull($drawn->customer_proposal_id);
        $this->assertSame(
            $before + 1,
            $this->getTableLocator()->get('CustomerProposals')->find()->count(),
        );

        // It asks nothing of the customer themselves: it is there to hold these papers.
        $round = $this->getTableLocator()->get('CustomerProposals')->get($drawn->customer_proposal_id);
        $this->assertNull($round->purpose);
        $this->assertSame('2026-11-01', $round->effective_from->toDateString());
    }

    /**
     * The preview of papers that bring their version into being has a version to say nothing
     * about: there is none yet, so nothing can have moved on one.
     *
     * @return void
     * @link \App\Contracts\Proposal\ChangePreview::of()
     */
    public function testThePreviewOfPapersWithoutAVersionRenders(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $proposals->saveOrFail(
            $proposals->patchEntity($proposals->get(self::PROPOSAL_ID), [
                'contract_version_id' => null,
                'conclusion_date' => '2026-09-15',
            ]),
            ['checkRules' => false],
        );

        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/customer-proposals/apply-changes/' . self::ROUND_ID);

        $this->assertResponseOk();
    }

    /**
     * Without a day there is nothing for the version to start on, so it is asked for rather than
     * guessed at - there is no version to take it from.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testANewContractWithoutAVersionHasToSayWhichDayItStarts(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::NewContract->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => '',
            'effective_from' => '',
        ]);

        $this->assertResponseOk();

        $errors = $this->viewVariable('contractProposal')->getErrors();
        $this->assertArrayHasKey('effective_from', $errors);
    }

    /**
     * Changing the contract redraws the form with that contract's versions rather than trying to
     * save what is only half filled in.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testChangingTheContractOnlyRedrawsTheForm(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $before = $proposals->find()->count();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'refresh' => 'refresh',
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => '74824fba-20b2-46fc-806c-df795aa9e429',
        ]);

        $this->assertResponseOk();
        $this->assertSame([], $this->viewVariable('contractProposal')->getErrors());
        $this->assertNotEmpty($this->viewVariable('versions')->toArray());
        $this->assertSame($before, $proposals->find()->count());
    }

    /**
     * A contract whose service type wants equipment, addresses and an account asks three questions,
     * and an unanswered one has to land on the box that answers it. They used to be set on the
     * column the answers are kept in, which the form does not render as a field - so three
     * complaints appeared above the form with nothing marked in it.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testUnansweredChecksLandOnTheirOwnBoxes(): void
    {
        $types = $this->getTableLocator()->get('ServiceTypes');
        $type = $types->get($this->getTableLocator()->get('Contracts')->get(self::CONTRACT_ID)->service_type_id);
        $types->saveOrFail($types->patchEntity($type, [
            'have_equipments' => true,
            'normally_with_borrowed_equipment' => true,
            'have_ip_addresses' => true,
            'have_radius_accounts' => true,
        ]), ['checkRules' => false]);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customers/403bab0e-52cd-4a8e-83f8-43c2457d0481/contracts/'
            . self::CONTRACT_ID . '/contract-proposals/add', [
                'contract_id' => self::CONTRACT_ID,
                'contract_version_id' => '74824fba-20b2-46fc-806c-df795aa9e429',
                'effective_from' => '2026-11-01',
                'confirmations' => ['fixed_term' => '1'],
            ]);

        $this->assertResponseOk();

        $errors = $this->viewVariable('contractProposal')->getErrors();
        $this->assertArrayHasKey('confirmations', $errors);
        // Nested under the field the boxes are named after, so the form marks them.
        $this->assertArrayHasKey('own_equipment', $errors['confirmations']);
        $this->assertArrayNotHasKey('snapshot', $errors);
        $this->assertArrayNotHasKey('changes', $errors);
    }

    /**
     * Answering them lets the proposal through.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testAnsweringTheChecksLetsItThrough(): void
    {
        $types = $this->getTableLocator()->get('ServiceTypes');
        $type = $types->get($this->getTableLocator()->get('Contracts')->get(self::CONTRACT_ID)->service_type_id);
        $types->saveOrFail($types->patchEntity($type, [
            'have_equipments' => true,
            'normally_with_borrowed_equipment' => true,
            'have_ip_addresses' => true,
        ]), ['checkRules' => false]);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customers/403bab0e-52cd-4a8e-83f8-43c2457d0481/contracts/'
            . self::CONTRACT_ID . '/contract-proposals/add', [
                'contract_id' => self::CONTRACT_ID,
                'contract_version_id' => '74824fba-20b2-46fc-806c-df795aa9e429',
                'effective_from' => '2026-11-01',
                'confirmations' => [
                    'fixed_term' => '1',
                    'own_equipment' => '1',
                    'does_not_use_ip_addresses' => '1',
                    'does_not_use_radius' => '1',
                ],
            ]);

        $this->assertRedirect();
    }

    /**
     * The form talks about the version and the contract in fields of its own. They are named apart
     * from the records they speak of, because two of those names are associations on the proposal:
     * handed `contract`, the marshaller once built a whole new contract out of one date and then
     * complained that it had no customer, no service type and no state - three complaints on fields
     * no form ever drew.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheFormsOwnFieldsAreNotReadAsAssociations(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customers/403bab0e-52cd-4a8e-83f8-43c2457d0481/contracts/'
            . self::CONTRACT_ID . '/contract-proposals/add', [
                'contract_id' => self::CONTRACT_ID,
                'contract_version_id' => '74824fba-20b2-46fc-806c-df795aa9e429',
                'effective_from' => '2026-11-01',
                'confirmations' => [
                    'fixed_term' => '1',
                    'own_equipment' => '1',
                    'does_not_use_ip_addresses' => '1',
                    'does_not_use_radius' => '1',
                ],
                // exactly what the form sends when nothing about them is ticked
                'version_change_named' => ['valid_until' => '0', 'obligation_until' => '0'],
                'version_change' => ['valid_until' => '', 'obligation_until' => ''],
                'contract_change_named' => ['termination_date' => '0'],
                'contract_change' => ['termination_date' => ''],
            ]);

        $this->assertRedirect();

        $proposals = $this->getTableLocator()->get('ContractProposals');
        /** @var \App\Model\Entity\ContractProposal $saved */
        $saved = $proposals->find()->orderByDesc('created')->firstOrFail();
        $this->assertTrue($saved->proposedChanges()->isEmpty());
    }

    /**
     * Both the contract and the version redraw the form when they change, and they do it by adding
     * a field the form never declared. It has to be unlocked whichever of them is on the page, or
     * the redraw is refused as tampering.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheFieldThatRedrawsTheFormIsUnlocked(): void
    {
        $this->login();
        $this->get('/customers/403bab0e-52cd-4a8e-83f8-43c2457d0481/contracts/'
            . self::CONTRACT_ID . '/contract-proposals/add');

        $this->assertResponseOk();
        // Both selectors are drawn wherever the form was opened, and either has to be able to
        // redraw it.
        $this->assertResponseContains('refresh');
        $this->assertResponseContains('name="contract_id"');
    }

    /**
     * Opened under a contract, the form fills its contract in rather than leaving it out. Every way
     * in reaches the same page and only the breadcrumbs say which one it was, so a field that
     * disappeared with the route is one the operator would go looking for.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheNestedFormOpensWithItsContractChosen(): void
    {
        $this->login();
        $this->get(self::NESTED . '/contract-proposals/add');

        $this->assertResponseOk();
        $this->assertSame(self::CONTRACT_ID, $this->viewVariable('contractProposal')->contract_id);
    }

    /**
     * And choosing another one there stands. The address fills the field in and nothing more, or
     * the papers would quietly be drawn up for the contract the operator came in under.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheContractChosenBeatsTheOneInTheAddress(): void
    {
        $another = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post(self::NESTED . '/contract-proposals/add', [
            'contract_id' => $another,
            'effective_from' => '2026-11-01',
            'confirmations' => [
                'fixed_term' => '1',
                'own_equipment' => '1',
                'does_not_use_ip_addresses' => '1',
                'does_not_use_radius' => '1',
            ],
        ]);

        $this->assertRedirect();

        $proposals = $this->getTableLocator()->get('ContractProposals');
        /** @var \App\Model\Entity\ContractProposal $saved */
        $saved = $proposals->find()->orderByDesc('created')->firstOrFail();
        $this->assertSame($another, $saved->contract_id);
    }

    /**
     * The number on the paper of what is being ended is asked only where something is being ended
     * - an ending, or a new contract replacing an earlier version. Those are the two cases the rule
     * that demands it asks about, so anywhere else it was a field with nothing to choose.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheNumberIsAskedForOnlyWhereSomethingIsEnded(): void
    {
        $this->login();

        $this->get(self::NESTED . '/contract-proposals/add?purpose='
            . ProposalPurpose::NewContract->value);
        $this->assertResponseOk();
        $this->assertResponseNotContains('name="terminated_contract_number"');

        $this->get(self::NESTED . '/contract-proposals/add?purpose='
            . ProposalPurpose::Termination->value);
        $this->assertResponseOk();
        $this->assertResponseContains('name="terminated_contract_number"');

        // And a new contract that replaces an earlier version does end something, so naming the
        // version draws the form again with the number on it.
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'refresh' => 'refresh',
            'purpose' => ProposalPurpose::NewContract->value,
            'contract_id' => self::CONTRACT_ID,
            'terminates_contract_version_id' => self::CONTRACT_VERSION_ID,
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('name="terminated_contract_number"');
    }

    /**
     * Says something of the round these papers go out in.
     *
     * The sending and the signature belong to the envelope, so a test that puts papers in either
     * state puts the envelope in it.
     *
     * @param array<string, mixed> $says What the round says.
     * @return void
     */
    private function theRoundSays(array $says): void
    {
        $envelopes = $this->getTableLocator()->get('CustomerProposals');
        $envelopes->saveOrFail(
            $envelopes->patchEntity($envelopes->get(self::ROUND_ID), $says),
            ['checkRules' => false],
        );
    }

    /**
     * The usual minimum term is offered rather than typed: the form names the day it would run to,
     * counted from the day the papers take effect, and clicking it fills the field in.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheUsualTermIsOfferedBesideTheObligation(): void
    {
        $version = $this->getTableLocator()->get('ContractVersions')->get(self::CONTRACT_VERSION_ID);
        $offered = TheUsualTerm::from($version->valid_from);

        $this->login();
        $this->get(self::NESTED . '/contract-proposals/add?purpose='
            . ProposalPurpose::ServiceChange->value
            . '&contract_version_id=' . self::CONTRACT_VERSION_ID);

        $this->assertResponseOk();
        // The day is said out loud, so nobody has to click to find out what they would get.
        $this->assertResponseContains(h((string)$offered));
        // And the click fills that very day in.
        $this->assertResponseContains($offered->toDateString());
    }

    /**
     * A line is added on a page of its own, the way a billing on a contract is.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::billingLine()
     */
    public function testABillingIsAddedOnItsOwnPage(): void
    {
        $this->login();
        $this->get(self::NESTED . '/contract-proposals/billing-line/' . self::PROPOSAL_ID);
        $this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/billing-line/' . self::PROPOSAL_ID, [
            'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
            'quantity' => '2',
            'price' => '299.00',
        ]);

        $this->assertRedirect();

        $proposals = $this->getTableLocator()->get('ContractProposals');
        $lines = $proposals->get(self::PROPOSAL_ID)->proposedChanges()->billings;

        $this->assertCount(1, $lines);
        $this->assertTrue($lines[0]->isAddition());
        $this->assertSame('299.00', $lines[0]->price?->toString());
        $this->assertNotEmpty($lines[0]->service, 'The chosen service did not come with the line.');
    }

    /**
     * A proposal is where new arrangements are made, so what is no longer sold is not offered -
     * the same as on a billing added to a contract.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::billingLine()
     */
    public function testARetiredServiceIsNotOffered(): void
    {
        $this->login();
        $this->get(self::NESTED . '/contract-proposals/billing-line/' . self::PROPOSAL_ID);

        $this->assertResponseOk();

        $offered = $this->viewVariable('services')->toArray();
        $this->assertArrayHasKey(self::OPEN_SERVICE_ID, $offered);
        $this->assertArrayNotHasKey(self::RETIRED_SERVICE_ID, $offered);
    }

    /**
     * Except the one the line is already on. Changing a line that runs on a tariff nobody may take
     * any more is what somebody comes to this page for, and a list without it would move them onto
     * another tariff without saying so.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::billingLine()
     */
    public function testARetiredServiceAlreadyChosenStaysOnTheList(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/billing-line/' . self::PROPOSAL_ID, [
            'service_id' => self::RETIRED_SERVICE_ID,
            'quantity' => '1',
            'price' => '299.00',
        ]);
        $this->assertRedirect();

        $proposals = $this->getTableLocator()->get('ContractProposals');
        $line = $proposals->get(self::PROPOSAL_ID)->proposedChanges()->billings[0];

        $this->login();
        $this->get(self::NESTED . '/contract-proposals/billing-line/' . self::PROPOSAL_ID . '/' . $line->id);

        $this->assertResponseOk();
        $this->assertArrayHasKey(
            self::RETIRED_SERVICE_ID,
            $this->viewVariable('services')->toArray(),
        );
    }

    /**
     * Changing something already billed for starts from what is there, so the operator changes the
     * one thing they came to change.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::billingLine()
     */
    public function testChangingABillingStartsFromWhatIsThere(): void
    {
        $this->login();
        $this->get(self::NESTED . '/contract-proposals/billing-line/' . self::PROPOSAL_ID
            . '?replaces=' . self::KNOWN_BILLING_ID);

        $this->assertResponseOk();
        $this->assertSame(self::KNOWN_BILLING_ID, $this->viewVariable('values')['billing_id']);
    }

    /**
     * A line below the contract's minimum is not saved, and the box means nothing coming from
     * somebody who is not offered it.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::billingLine()
     */
    public function testALineBelowTheMinimumIsRefusedOnTheForm(): void
    {
        $this->agreeMinimum('100');

        $this->login('bookkeeper');
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/billing-line/' . self::PROPOSAL_ID, [
            'service_id' => self::OPEN_SERVICE_ID,
            'quantity' => '1',
            'price' => '50',
            'below_minimum_allowed' => '1',
        ]);

        $this->assertNoRedirect();
        $this->assertSame('50', $this->viewVariable('values')['price'], 'What was typed was not kept.');
        $this->assertResponseContains('<div class="error-message">');
        $this->assertSame(
            [],
            $this->getTableLocator()->get('ContractProposals')->get(self::PROPOSAL_ID)->proposedChanges()->billings,
        );
    }

    /**
     * What an administrator allows stays on the line, until somebody else writes the line again.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::billingLine()
     */
    public function testAnAdministratorsAllowanceStaysOnTheLineUntilSomebodyElseWritesIt(): void
    {
        $this->agreeMinimum('100');
        $line = [
            'service_id' => self::OPEN_SERVICE_ID,
            'quantity' => '1',
            'price' => '50',
            'below_minimum_allowed' => '1',
        ];

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/billing-line/' . self::PROPOSAL_ID, $line);
        $this->assertRedirect();

        $proposals = $this->getTableLocator()->get('ContractProposals');
        $written = $proposals->get(self::PROPOSAL_ID)->proposedChanges()->billings[0];
        $this->assertTrue($written->below_minimum_allowed);

        $this->login('bookkeeper');
        $this->post('/contract-proposals/billing-line/' . self::PROPOSAL_ID . '/' . $written->id, [
            'note' => 'Written again',
        ] + $line);

        $this->assertNoRedirect();
        $this->assertNull($proposals->get(self::PROPOSAL_ID)->proposedChanges()->billings[0]->note);
    }

    /**
     * Puts a minimum on the contract the proposal is about.
     *
     * @param string $minimum The minimum.
     * @return void
     */
    private function agreeMinimum(string $minimum): void
    {
        $this->getTableLocator()->get('Contracts')->updateAll(
            ['minimum_connection_price' => $minimum],
            ['id' => self::CONTRACT_ID],
        );
    }

    /**
     * One change may be several lines: half price until a day, full price from it. The second line
     * carries its own start, and the first stops the day before it.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::billingLine()
     */
    public function testOneChangeMayBeSeveralLines(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/contract-proposals/billing-line/' . self::PROPOSAL_ID
            . '?replaces=' . self::KNOWN_BILLING_ID, [
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'quantity' => '1',
                'percentage_discount' => '50',
                'billing_until' => '2027-08-31',
            ]);
        $this->assertRedirect();

        $this->post('/contract-proposals/billing-line/' . self::PROPOSAL_ID, [
            'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
            'quantity' => '1',
            'billing_from' => '2027-09-01',
        ]);
        $this->assertRedirect();

        $proposals = $this->getTableLocator()->get('ContractProposals');
        $lines = $proposals->get(self::PROPOSAL_ID)->proposedChanges()->billings;

        $this->assertCount(2, $lines);
        $this->assertSame(50, $lines[0]->percentage_discount);
        $this->assertSame('2027-08-31', $lines[0]->billing_until?->toDateString());
        $this->assertSame('2027-09-01', $lines[1]->billing_from?->toDateString());
        $this->assertNull($lines[1]->percentage_discount);
    }

    /**
     * A line can be taken back out again, leaving what it acted on as it stands.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::dropBillingLine()
     */
    public function testALineCanBeTakenBackOut(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/billing-line/' . self::PROPOSAL_ID, [
            'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
            'quantity' => '1',
        ]);

        $proposals = $this->getTableLocator()->get('ContractProposals');
        $line = $proposals->get(self::PROPOSAL_ID)->proposedChanges()->billings[0];

        $this->post('/contract-proposals/drop-billing-line/'
            . self::PROPOSAL_ID . '/' . $line->id);

        $this->assertRedirect();
        $this->assertTrue($proposals->get(self::PROPOSAL_ID)->proposedChanges()->isEmpty());
    }

    /**
     * Ending a billing that is already being replaced replaces that line rather than adding a
     * second one to the same billing.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::endBilling()
     */
    public function testEndingSomethingAlreadyBeingReplacedReplacesThatLine(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/contract-proposals/billing-line/' . self::PROPOSAL_ID
            . '?replaces=' . self::KNOWN_BILLING_ID, [
                'service_id' => 'eaacfeb3-1430-43ce-842e-497c5c95d953',
                'quantity' => '1',
            ]);

        $this->post('/contract-proposals/end-billing/'
            . self::PROPOSAL_ID . '/' . self::KNOWN_BILLING_ID);
        $this->assertRedirect();

        $proposals = $this->getTableLocator()->get('ContractProposals');
        $lines = $proposals->get(self::PROPOSAL_ID)->proposedChanges()->billings;

        $this->assertCount(1, $lines);
        $this->assertTrue($lines[0]->terminatesOnly());
    }

    /**
     * A settled proposal has its lines left alone.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::billingLine()
     */
    public function testTheLinesOfASentProposalAreLeftAlone(): void
    {
        $this->theRoundSays(['sent_date' => '2026-10-01', 'delivery_type' => DocumentsDeliveryType::Email]);

        $this->login();
        $this->get(self::NESTED . '/contract-proposals/billing-line/' . self::PROPOSAL_ID);

        $this->assertRedirect();
    }

    /**
     * Every role that may draw papers up may put them together again, and the button to it is
     * drawn for them. Tests log in as an administrator unless they say otherwise, who is let
     * through before the rules for the roles are read at all.
     *
     * @param string $role The role to ask as.
     * @return void
     * @link \App\Controller\ContractProposalsController::recreate()
     */
    #[DataProvider('rolesThatDrawUpPapers')]
    public function testRecreateIsOpenToTheRolesThatDrawUpPapers(string $role): void
    {
        $this->login($role);

        $this->get(self::NESTED . '/contract-proposals/view/' . self::PROPOSAL_ID);
        $this->assertResponseOk();
        $this->assertResponseContains('/contract-proposals/recreate/' . self::PROPOSAL_ID);

        $this->get(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID);
        $this->assertResponseOk();
    }

    /**
     * And the same gate as deleting keeps them out once the papers have gone out.
     *
     * @param string $role The role to ask as.
     * @return void
     * @link \App\Controller\ContractProposalsController::recreate()
     */
    #[DataProvider('rolesThatDrawUpPapers')]
    public function testRecreateIsShutOnceThePapersHaveGoneOut(string $role): void
    {
        $proposal = $this->getTableLocator()->get('ContractProposals')->get(self::PROPOSAL_ID);
        $this->getTableLocator()->get('CustomerProposals')->updateAll(
            ['sent_date' => '2026-09-01', 'delivery_type' => DocumentsDeliveryType::cases()[0]->value],
            ['id' => $proposal->customer_proposal_id],
        );
        $this->login($role);

        $this->get(self::NESTED . '/contract-proposals/view/' . self::PROPOSAL_ID);
        $this->assertResponseOk();
        $this->assertResponseNotContains('/contract-proposals/recreate/' . self::PROPOSAL_ID);

        $this->get(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID);
        $this->assertRedirect();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function rolesThatDrawUpPapers(): array
    {
        return [
            'network manager' => ['network-manager'],
            'sales representative' => ['sales-representative'],
            'sales manager' => ['sales-manager'],
            'bookkeeper' => ['bookkeeper'],
        ];
    }

    /**
     * The form renders, and asks for nothing the papers are about.
     *
     * Those fields are what the snapshot and the changes were made from, so the page that offers
     * them is the one that draws papers up. Here there is nothing to offer but what is written
     * where it is read.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::recreate()
     */
    public function testRecreate(): void
    {
        $this->login();
        $this->get(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('name="note"');

        foreach (['purpose', 'contract_id', 'contract_version_id', 'effective_from', 'ends_on'] as $settled) {
            $this->assertResponseNotContains(
                sprintf('name="%s"', $settled),
                sprintf('The form offers %s, which the papers were drawn up from.', $settled),
            );
        }
    }

    /**
     * Taking the snapshot again and putting the papers right were two halves of one gesture, each
     * sending the operator to the other. They are one page now, and the old address still arrives.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::refreshSnapshot()
     */
    public function testTheOldAddressForTheSnapshotStillArrives(): void
    {
        $this->login();
        $this->get(self::NESTED . '/contract-proposals/refresh-snapshot/' . self::PROPOSAL_ID);

        $this->assertRedirectContains('/contract-proposals/recreate/' . self::PROPOSAL_ID);
    }

    /**
     * Putting the papers together again reads the contract afresh and takes back the lines the new
     * reading no longer knows.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::recreate()
     */
    public function testRecreatingTakesTheSnapshotAgain(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $billings = $this->getTableLocator()->get('Billings');

        $line = ProposedBilling::fromArray(['billing_id' => self::KNOWN_BILLING_ID, 'quantity' => 2]);
        $proposal = $proposals->get(self::PROPOSAL_ID);
        $proposal->set('changes', $proposal->proposedChanges()->withLine($line)->toArray());
        // Answered the way the form answers them, since saving asks whether the contract is ready.
        $proposal->set('confirmations', [
            'fixed_term' => true,
            'own_equipment' => true,
            'does_not_use_ip_addresses' => true,
            'does_not_use_radius' => true,
        ]);
        $proposals->saveOrFail($proposal);
        $billings->deleteOrFail($billings->get(self::KNOWN_BILLING_ID));

        $before = $proposals->get(self::PROPOSAL_ID)->snapshot_taken;

        $this->login();
        $this->get(self::NESTED . '/contract-proposals/view/' . self::PROPOSAL_ID);
        $this->assertResponseContains('/contract-proposals/recreate/' . self::PROPOSAL_ID);

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID, [
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertRedirectContains('/contract-proposals/view/' . self::PROPOSAL_ID);

        $after = $proposals->get(self::PROPOSAL_ID);
        $this->assertTrue($after->snapshot_taken > $before, 'The snapshot was not taken again.');
        $this->assertArrayNotHasKey(self::KNOWN_BILLING_ID, $after->stateOfThings()->billings());
        $this->assertNull($after->proposedChanges()->line($line->id));
    }

    /**
     * The documents we generated came from the old snapshot, so they go with it - and nothing else
     * does. An ending is filed with the customer's own notice and with what an office wrote, and
     * neither is ours to throw away.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::recreate()
     */
    public function testRecreatingDeletesOnlyTheDocumentsWeGenerated(): void
    {
        Configure::write('Files.root', TMP . 'recreate-papers-' . uniqid());
        $storage = new FileStorage();

        $ours = $storage->link(
            $storage->store('%PDF-1.7 ours', 'application/pdf'),
            ContractDocuments::MODEL,
            self::PROPOSAL_ID,
            ContractDocumentType::ContractTermination->value,
            DocumentVariant::Generated->value,
            ['name' => 'termination.pdf'],
        );
        $theirs = $storage->link(
            $storage->store('%PDF-1.7 theirs', 'application/pdf'),
            ContractDocuments::MODEL,
            self::PROPOSAL_ID,
            ContractDocumentType::TerminationNotice->value,
            DocumentVariant::Received->value,
            ['name' => 'notice.pdf'],
        );

        $this->login();
        $this->get(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID);

        // Named on the form, so nobody agrees to something they were not shown - and the one that
        // came from the customer is not among them.
        $this->assertResponseOk();
        $this->assertResponseContains('termination.pdf');
        $this->assertResponseNotContains('notice.pdf');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID, [
            'discard_the_documents' => '1',
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertRedirect();

        $links = $this->getTableLocator()->get('Files.FileLinks');
        $this->assertFalse($links->exists(['id' => $ours->id]), 'What we generated was kept.');
        $this->assertTrue($links->exists(['id' => $theirs->id]), 'The customer\'s own notice was deleted.');

        Configure::delete('Files.root');
    }

    /**
     * And they are not deleted behind anybody's back: without the box that says they may go, the
     * snapshot stands and so do they.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::recreate()
     */
    public function testTheGeneratedDocumentsAreNotDeletedUnasked(): void
    {
        Configure::write('Files.root', TMP . 'recreate-papers-' . uniqid());
        $storage = new FileStorage();

        $ours = $storage->link(
            $storage->store('%PDF-1.7 ours', 'application/pdf'),
            ContractDocuments::MODEL,
            self::PROPOSAL_ID,
            ContractDocumentType::ContractTermination->value,
            DocumentVariant::Generated->value,
            ['name' => 'termination.pdf'],
        );

        $proposals = $this->getTableLocator()->get('ContractProposals');
        $before = $proposals->get(self::PROPOSAL_ID)->snapshot_taken;

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID, [
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertResponseOk();
        $this->assertArrayHasKey(
            'discard_the_documents',
            $this->viewVariable('contractProposal')->getErrors(),
        );
        $this->assertTrue(
            $this->getTableLocator()->get('Files.FileLinks')->exists(['id' => $ours->id]),
            'A document was deleted without anybody agreeing to it.',
        );
        $this->assertEquals($before, $proposals->get(self::PROPOSAL_ID)->snapshot_taken);

        Configure::delete('Files.root');
    }

    /**
     * A fresh reading may raise a question that was answered against the old one, and the question
     * is asked on the very form that read it - which is why the two used to send the operator back
     * and forth to each other. Nothing is written until it is answered.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::recreate()
     */
    public function testAnUnansweredQuestionIsAskedOnTheForm(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $before = $proposals->get(self::PROPOSAL_ID)->snapshot_taken;

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
        $this->assertNotEmpty($this->viewVariable('contractProposal')->getErrors());
        $this->assertEquals($before, $proposals->get(self::PROPOSAL_ID)->snapshot_taken);
    }

    /**
     * The contracts are offered the way every other form offers them - number, service and where
     * it is - rather than as bare numbers nobody remembers.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testTheContractsAreNamedAsElsewhere(): void
    {
        $this->login();
        $this->get(self::NESTED . '/contract-proposals/add');

        $this->assertResponseOk();
        $contract = $this->getTableLocator()->get('Contracts')
            ->get(self::CONTRACT_ID, contain: ['InstallationAddresses', 'ServiceTypes']);
        $offered = iterator_to_array($this->viewVariable('contracts'));

        $this->assertSame($contract->name, $offered[self::CONTRACT_ID] ?? null);
        $this->assertNotSame((string)$contract->number, $contract->name, 'The fixture names nothing but the number.');
    }

    /**
     * Taking the snapshot again survives a billing having gone from the contract since - which is
     * the very case somebody asks for it in, and which saving the snapshot on its own would have
     * been refused for.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::recreate()
     */
    public function testTheSnapshotIsTakenAgainEvenWhenABillingHasGone(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $billings = $this->getTableLocator()->get('Billings');

        // A line acting on that billing, put there while it was still on the contract.
        $line = ProposedBilling::fromArray(['billing_id' => self::KNOWN_BILLING_ID, 'quantity' => 2]);
        $proposal = $proposals->get(self::PROPOSAL_ID);
        $proposal->set('changes', $proposal->proposedChanges()->withLine($line)->toArray());
        $proposals->saveOrFail($proposal);

        // The proposal's snapshot knows this billing; the contract will not.
        $billings->deleteOrFail($billings->get(self::KNOWN_BILLING_ID));

        $before = $proposals->get(self::PROPOSAL_ID)->snapshot_taken;

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID, [
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertRedirect();

        $after = $proposals->get(self::PROPOSAL_ID);
        $this->assertTrue($after->snapshot_taken > $before, 'The snapshot was not taken again.');
        $this->assertArrayNotHasKey(
            self::KNOWN_BILLING_ID,
            $after->stateOfThings()->billings(),
        );
        $this->assertNull(
            $after->proposedChanges()->line($line->id),
            'The line was left acting on a billing that is no longer there.',
        );
    }

    /**
     * An ending is drawn up from one day: the version stops being valid on it, the contract is
     * terminated on it, and the papers take effect the day after, because what is billed for stops
     * the day before they apply.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testAnEndingIsDrawnUpFromOneDay(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::Termination->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => self::CONTRACT_VERSION_ID,
            'terminated_contract_number' => '2022/0001',
            'ends_on' => '2026-12-31',
            'confirmations' => [
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertSame([], $this->viewVariable('contractProposal')?->getErrors() ?? []);
        $this->assertRedirect();

        /** @var \App\Model\Entity\ContractProposal $drawn */
        $drawn = $this->getTableLocator()->get('ContractProposals')
            ->find()
            ->orderByDesc('created')
            ->firstOrFail();

        $changes = $drawn->proposedChanges();
        $this->assertSame('2026-12-31', $changes->version->get('valid_until')?->toDateString());
        $this->assertSame('2026-12-31', $changes->contract->get('termination_date')?->toDateString());
        $this->assertSame('2027-01-01', $drawn->effective_from->toDateString());
    }

    /**
     * An ending stops what the contract is still billed for, and leaves alone what stopped on its
     * own before the papers take effect. A line for something long dead would read as a change on
     * the proposal and would be one in the records.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::endWhatTheContractIsBilledFor()
     */
    public function testAnEndingLeavesAloneWhatHasAlreadyStopped(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::Termination->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => self::CONTRACT_VERSION_ID,
            'terminated_contract_number' => '2022/0001',
            'ends_on' => '2026-12-31',
            'confirmations' => [
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertRedirect();

        /** @var \App\Model\Entity\ContractProposal $drawn */
        $drawn = $this->getTableLocator()->get('ContractProposals')
            ->find()
            ->orderByDesc('created')
            ->firstOrFail();

        $ended = array_keys($drawn->proposedChanges()->billingsByBillingId());

        $this->assertContains(self::KNOWN_BILLING_ID, $ended, 'What is still billed for was not ended.');
        $this->assertNotContains(
            self::CLOSED_BILLING_ID,
            $ended,
            'A billing that stopped years ago was given an ending.',
        );
    }

    /**
     * A version ending while the contract runs on leaves the billings alone. What is billed for
     * hangs off the contract, which carries on, and the version that follows says what becomes of
     * it - so stopping it here would stop it for good, in the records as well as on the paper.
     *
     * It used to be ended all the same: the question asked was whether the papers end anything,
     * and a version ending is an ending.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::endWhatTheContractIsBilledFor()
     */
    public function testEndingOneVersionLeavesWhatIsBilledForAlone(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::Termination->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => self::CONTRACT_VERSION_ID,
            'terminated_contract_number' => '2022/0001',
            'ends_on' => '2026-12-31',
            'version_only' => '1',
            'confirmations' => [
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertRedirect();

        /** @var \App\Model\Entity\ContractProposal $drawn */
        $drawn = $this->getTableLocator()->get('ContractProposals')
            ->find()
            ->orderByDesc('created')
            ->firstOrFail();

        $changes = $drawn->proposedChanges();

        $this->assertSame('2026-12-31', $changes->version->get('valid_until')?->toDateString());
        $this->assertFalse(
            $changes->contract->endsTheContract(),
            'The contract was ended by papers that end one version of it.',
        );
        $this->assertSame(
            [],
            $changes->billings,
            'What is billed for was stopped although the contract runs on.',
        );
    }

    /**
     * An ending with no day says so on the field that asks for it. The columns that would
     * otherwise complain - the day the papers apply from, and what the proposal asks for - are not
     * on this form, and a complaint about a field nobody can see reads as no complaint at all.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testAnEndingWithoutADaySaysSoWhereItIsAsked(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::Termination->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => self::CONTRACT_VERSION_ID,
            'terminated_contract_number' => '2022/0001',
            'ends_on' => '',
        ]);

        $this->assertResponseOk();

        $errors = $this->viewVariable('contractProposal')->getErrors();
        $this->assertArrayHasKey('ends_on', $errors);
        $this->assertArrayNotHasKey('effective_from', $errors);
        $this->assertArrayNotHasKey('changes', $errors);
    }

    /**
     * Choosing the version draws the form again, and an ending whose day was typed first arrives
     * there asking for the contract to end - with no snapshot behind it yet, because a form being
     * drawn again never takes one. Stopping what is billed for read the snapshot all the same and
     * the form came back as an error page, which is why the two fields had to be filled in in one
     * order and not the other.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testAnEndingWhoseDayWasTypedBeforeTheVersionOnlyRedrawsTheForm(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $before = $proposals->find()->count();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'refresh' => 'refresh',
            'purpose' => ProposalPurpose::Termination->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => self::CONTRACT_VERSION_ID,
            'ends_on' => '2026-12-31',
        ]);

        $this->assertResponseOk();
        $this->assertSame([], $this->viewVariable('contractProposal')->getErrors());
        $this->assertSame($before, $proposals->find()->count());
    }

    /**
     * And an ending that names no version is a question put to the operator, not a failure: the
     * snapshot was never taken, so there is nothing to stop billing against either.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::add()
     */
    public function testAnEndingWithoutAVersionIsAsked(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/add', [
            'purpose' => ProposalPurpose::Termination->value,
            'contract_id' => self::CONTRACT_ID,
            'contract_version_id' => '',
            'ends_on' => '2026-12-31',
        ]);

        $this->assertResponseOk();
        $this->assertArrayHasKey(
            'contract_version_id',
            $this->viewVariable('contractProposal')->getErrors(),
        );
    }

    /**
     * Papers go out more than once - by another means, or after the first attempt came back - so
     * the day and the way they went may be recorded again. What they stand on stays settled.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::send()
     */
    public function testPapersMayGoOutAgain(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/customer-proposals/send/' . self::ROUND_ID, [
            'sent_date' => '2026-10-01',
            'delivery_type' => DocumentsDeliveryType::Email->value,
        ]);
        $this->assertRedirect();

        $this->post('/customer-proposals/send/' . self::ROUND_ID, [
            'sent_date' => '2026-10-08',
            'delivery_type' => DocumentsDeliveryType::Post->value,
        ]);
        $this->assertRedirect();

        // Read with the envelope, because that is where the papers read the sending from.
        $sent = $proposals->get(self::PROPOSAL_ID, contain: ['CustomerProposals']);
        $this->assertSame('2026-10-08', $sent->sent_date?->toDateString());
        $this->assertSame(DocumentsDeliveryType::Post, $sent->delivery_type);
    }

    /**
     * Nothing may be applied without the day the customer agreed to it, so there is a way to
     * record that day - and to correct it, for as long as the proposal is open.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::conclude()
     */
    public function testTheSignatureIsRecordedAndMayBeCorrected(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        foreach (['2026-10-05', '2026-10-06'] as $day) {
            $this->post('/customer-proposals/conclude/' . self::ROUND_ID, [
                'conclusion_date' => $day,
            ]);

            $this->assertRedirect();
            $this->assertSame(
                $day,
                $proposals->get(self::PROPOSAL_ID, contain: ['CustomerProposals'])
                    ->conclusion_date?->toDateString(),
            );
        }
    }

    /**
     * Recording that the papers went out settles what stands behind them: nothing about the
     * proposal may be changed afterwards.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::send()
     */
    public function testSendingSettlesTheProposal(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customer-proposals/send/' . self::ROUND_ID, [
            'sent_date' => '2026-10-01',
            'delivery_type' => DocumentsDeliveryType::Email->value,
        ]);

        $this->assertRedirect();

        $proposals = $this->getTableLocator()->get('ContractProposals');
        $sent = $proposals->get(self::PROPOSAL_ID, contain: ['CustomerProposals']);

        $this->assertTrue($sent->hasBeenSent());
        $this->assertFalse($proposals->mayBeEdited($sent));

        $this->get(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID);
        $this->assertRedirect();
    }

    /**
     * Giving up on a proposal touches nothing else, because the live records never moved.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::revoke()
     */
    public function testRevokingTouchesNothingLive(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $before = $billings->find()->count();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post(self::NESTED . '/contract-proposals/revoke/' . self::PROPOSAL_ID);

        // Given up on where it stands: the papers are still what the reader was looking at.
        $this->assertRedirectContains('/contract-proposals/view/' . self::PROPOSAL_ID);

        $proposal = $this->getTableLocator()->get('ContractProposals')->get(self::PROPOSAL_ID);
        $this->assertTrue($proposal->hasBeenRevoked());
        $this->assertSame($before, $billings->find()->count());
    }

    /**
     * The preview says what applying the changes would run into before anybody presses the button.
     *
     * @return void
     * @link \App\Controller\CustomerProposalsController::applyChanges()
     */
    public function testThePreviewOfTheChangesSaysWhatStandsInTheWay(): void
    {
        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/customer-proposals/apply-changes/' . self::ROUND_ID);

        $this->assertResponseOk();
        // Nobody has signed it, so it says so and does not offer the button.
        $this->assertResponseContains(__('This proposal has not been signed yet, so its changes'
            . ' cannot be applied.'));
    }

    /**
     * The papers are said out loud without standing in the way: the day the customer agreed is
     * known before the post arrives, and holding the records back for a scan would leave the
     * service waiting on the scanner.
     *
     * @return void
     * @link \App\Contracts\Proposal\ChangePreview::of()
     */
    public function testAMissingScanIsSaidOutLoudAndStopsNothing(): void
    {
        $this->theRoundSays(['conclusion_date' => '2026-09-15']);

        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/customer-proposals/apply-changes/' . self::ROUND_ID);

        $this->assertResponseOk();
        $this->assertResponseContains(
            __('The signature is recorded, but no signed documents have been uploaded for this'
                . ' proposal.'),
        );
        // Said, not stopped: the button is still there. This proposal changes nothing, so it
        // reads as marking the job done rather than as moving anything.
        $this->assertResponseContains(__('Mark as Settled'));
    }

    /**
     * The customer's own notice ending the contract is the answer by itself, so nothing is said
     * about a missing scan once it is on file.
     *
     * @return void
     * @link \App\Contracts\Proposal\ChangePreview::of()
     */
    public function testANoticeOnFileIsTheAnswer(): void
    {
        $this->theRoundSays(['conclusion_date' => '2026-09-15']);

        $root = TMP . 'notice-papers-' . uniqid();
        Configure::write('Files.root', $root);
        $storage = new FileStorage();
        $storage->link(
            $storage->store('%PDF-1.7 notice', 'application/pdf'),
            ContractDocuments::MODEL,
            self::PROPOSAL_ID,
            ContractDocumentType::TerminationNotice->value,
            DocumentVariant::Received->value,
            ['name' => 'notice.pdf'],
        );

        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/customer-proposals/apply-changes/' . self::ROUND_ID);

        $this->assertResponseOk();
        $this->assertResponseNotContains(
            __('The signature is recorded, but no signed documents have been uploaded for this'
                . ' proposal.'),
        );

        Configure::delete('Files.root');
    }

    /**
     * A minimum raised after a line was written does not hold the rest of the proposal up: the line
     * was asked when it was written, and what it says now is for the preview to tell.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::recreate()
     */
    public function testAMinimumRaisedSinceDoesNotHoldUpPuttingThePapersRight(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $this->aConnectionLineAtFifty();
        $this->agreeMinimum('100');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post(self::NESTED . '/contract-proposals/recreate/' . self::PROPOSAL_ID, [
            'note' => 'Written after the minimum was raised',
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);

        $this->assertRedirect();
        $this->assertSame('Written after the minimum was raised', $proposals->get(self::PROPOSAL_ID)->note);
    }

    /**
     * Puts a line on the proposal pricing the connection at fifty, bypassing the form.
     *
     * @return void
     */
    private function aConnectionLineAtFifty(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $proposals->saveOrFail(
            $proposals->patchEntity($proposals->get(self::PROPOSAL_ID), [
                'changes' => ['billings' => [[
                    'billing_id' => self::KNOWN_BILLING_ID,
                    'terminates_only' => false,
                    'service_id' => self::OPEN_SERVICE_ID,
                    'quantity' => 1,
                    'price' => '50.00',
                    // the service comes with the line, the way the form brings it
                    'service' => [
                        'id' => self::OPEN_SERVICE_ID,
                        'name' => 'Internet',
                        'price' => '2',
                        'queue' => ['id' => '9a2952ed-9947-4c0e-bda8-97f00614eab4', 'name' => 'Internet'],
                    ],
                ]]],
            ]),
            ['checkRules' => false],
        );
    }

    /**
     * A minimum raised after a line was written is said before the button, and the administrator
     * may apply the line anyway by ticking the box.
     *
     * @return void
     * @link \App\Contracts\Proposal\ChangePreview::of()
     */
    public function testAMinimumRaisedSinceIsSaidAndMayBeGoneBelowDeliberately(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $this->aConnectionLineAtFifty();
        $this->theRoundSays(['conclusion_date' => '2026-09-15']);
        $this->agreeMinimum('100');

        $this->login();
        $this->get('/customers/' . self::CUSTOMER_ID . '/customer-proposals/apply-changes/' . self::ROUND_ID);

        $this->assertResponseOk();
        $this->assertResponseContains('minimum set on the contract');
        $this->assertResponseContains('allow_below_minimum');

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customer-proposals/apply-changes/' . self::ROUND_ID, ['allow_below_minimum' => '1']);

        $this->assertRedirect();
        $this->assertTrue($proposals->get(self::PROPOSAL_ID)->hasBeenApplied());
    }

    /**
     * A signed proposal that changes nothing is applied all the same, so that it stops being
     * listed as waiting - and nothing of the contract moves.
     *
     * @return void
     * @link \App\Controller\CustomerProposalsController::applyChanges()
     */
    public function testAnEmptyProposalIsMarkedAsDealtWith(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $this->theRoundSays(['conclusion_date' => '2026-09-15']);

        $billings = $this->getTableLocator()->get('Billings');
        $before = $billings->find()->count();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customer-proposals/apply-changes/' . self::ROUND_ID);

        $this->assertRedirect();
        $this->assertTrue($proposals->get(self::PROPOSAL_ID)->hasBeenApplied());
        $this->assertSame($before, $billings->find()->count());
    }

    /**
     * An unsigned proposal is not applied even when the request is made straight at it.
     *
     * @return void
     * @link \App\Controller\CustomerProposalsController::applyChanges()
     */
    public function testAnUnsignedProposalIsNotApplied(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/customer-proposals/apply-changes/' . self::ROUND_ID);

        $this->assertFalse(
            $this->getTableLocator()->get('ContractProposals')
                ->get(self::PROPOSAL_ID)
                ->hasBeenApplied(),
        );
    }

    /**
     * A proposal that never went anywhere may be removed; one that did may not.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::delete()
     */
    public function testOnlyAProposalThatWentNowhereIsRemoved(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/delete/' . self::PROPOSAL_ID);
        // The papers are gone, the proposal that held them is not.
        $this->assertRedirectContains(
            '/customers/' . self::CUSTOMER_ID . '/customer-proposals/view/' . self::ROUND_ID,
        );
        $this->assertSame(0, $proposals->find()->where(['id' => self::PROPOSAL_ID])->count());
    }

    /**
     * A sent proposal is not removed either.
     *
     * @return void
     * @link \App\Controller\ContractProposalsController::delete()
     */
    public function testASentProposalIsNotRemoved(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $this->theRoundSays(['sent_date' => '2026-10-01', 'delivery_type' => DocumentsDeliveryType::Email]);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-proposals/delete/' . self::PROPOSAL_ID);

        $this->assertRedirectContains('/contract-proposals/view/' . self::PROPOSAL_ID);
        $this->assertSame(1, $proposals->find()->where(['id' => self::PROPOSAL_ID])->count());
    }
}
