<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\ContractVersionProposalsController;
use App\Model\Enum\ContractDeliveryMethod;
use App\Test\Traits\ControllerTestTrait;
use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\ContractVersionProposalsController Test Case
 *
 * Mostly smoke tests: every action is requested once and has to answer. Two of them go further,
 * because they are the ones that would let a paper and the record behind it part company - sending
 * settles the proposal, and taking the snapshot again has to survive a billing having gone.
 */
#[UsesClass(ContractVersionProposalsController::class)]
class ContractVersionProposalsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * Contract the nested routes hang off.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';

    /**
     * The proposal the fixture carries: open, unsent, changing nothing.
     *
     * @var string
     */
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';

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
        'app.ContractVersionProposals',
    ];

    /**
     * The listing renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::index()
     */
    public function testIndex(): void
    {
        $this->login();
        $this->get('/contract-version-proposals');

        $this->assertResponseOk();
    }

    /**
     * The listing renders with the search and the settled ones asked for, which builds a different
     * query than the plain listing does.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::index()
     */
    public function testIndexWithSearchAndSettled(): void
    {
        $this->login();
        $this->get('/contract-version-proposals?search=Lorem&show_settled=1');

        $this->assertResponseOk();
    }

    /**
     * The detail renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::view()
     */
    public function testView(): void
    {
        $this->login();
        $this->get('/contract-version-proposals/view/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
    }

    /**
     * The form for a new proposal renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::add()
     */
    public function testAdd(): void
    {
        $this->login();
        $this->get('/customers/403bab0e-52cd-4a8e-83f8-43c2457d0481/contracts/'
            . self::CONTRACT_ID . '/contract-version-proposals/add');

        $this->assertResponseOk();
    }

    /**
     * A link from the contract's own pages settles which contract the papers are for, so the form
     * has the versions of that contract to choose from rather than an empty list.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::add()
     */
    public function testTheFormFollowsTheContractTheLinkNamed(): void
    {
        $this->login();
        $this->get('/contract-version-proposals/add?contract_id=' . self::CONTRACT_ID);

        $this->assertResponseOk();
        $this->assertNotEmpty($this->viewVariable('versions')->toArray());
    }

    /**
     * Saying nothing about which version the papers are for is said on that field, not on the
     * columns behind the snapshot - those are not on the form, and an error nobody can see reads
     * as three required fields with nothing marked.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::add()
     */
    public function testAProposalWithoutAVersionSaysSoWhereItCanBeSeen(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-version-proposals/add', [
            'contract_id' => self::CONTRACT_ID,
            'effective_from' => '2026-11-01',
        ]);

        $this->assertResponseOk();

        $errors = $this->viewVariable('contractVersionProposal')->getErrors();
        $this->assertArrayHasKey('contract_version_id', $errors);
        $this->assertArrayNotHasKey('snapshot', $errors);
        $this->assertArrayNotHasKey('snapshot_taken', $errors);
    }

    /**
     * Changing the contract redraws the form with that contract's versions rather than trying to
     * save what is only half filled in.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::add()
     */
    public function testChangingTheContractOnlyRedrawsTheForm(): void
    {
        $proposals = $this->getTableLocator()->get('ContractVersionProposals');
        $before = $proposals->find()->count();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-version-proposals/add', [
            'refresh' => 'refresh',
            'contract_id' => self::CONTRACT_ID,
        ]);

        $this->assertResponseOk();
        $this->assertSame([], $this->viewVariable('contractVersionProposal')->getErrors());
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
     * @link \App\Controller\ContractVersionProposalsController::add()
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
            . self::CONTRACT_ID . '/contract-version-proposals/add', [
                'contract_version_id' => '74824fba-20b2-46fc-806c-df795aa9e429',
                'effective_from' => '2026-11-01',
                'acknowledgements' => ['fixed_term' => '1'],
            ]);

        $this->assertResponseOk();

        $errors = $this->viewVariable('contractVersionProposal')->getErrors();
        $this->assertArrayHasKey('acknowledgements', $errors);
        // Nested under the field the boxes are named after, so the form marks them.
        $this->assertArrayHasKey('own_equipment', $errors['acknowledgements']);
        $this->assertArrayNotHasKey('snapshot', $errors);
        $this->assertArrayNotHasKey('changes', $errors);
    }

    /**
     * Answering them lets the proposal through.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::add()
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
            . self::CONTRACT_ID . '/contract-version-proposals/add', [
                'contract_version_id' => '74824fba-20b2-46fc-806c-df795aa9e429',
                'effective_from' => '2026-11-01',
                'acknowledgements' => [
                    'fixed_term' => '1',
                    'own_equipment' => '1',
                    'does_not_use_ip_addresses' => '1',
                    'does_not_use_radius' => '1',
                ],
            ]);

        $this->assertRedirect();
    }

    /**
     * The form of an existing proposal renders.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::edit()
     */
    public function testEdit(): void
    {
        $this->login();
        $this->get('/contract-version-proposals/edit/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
    }

    /**
     * The form for taking the snapshot again renders, and says what it is about.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::refreshSnapshot()
     */
    public function testRefreshSnapshot(): void
    {
        $this->login();
        $this->get('/contract-version-proposals/refresh-snapshot/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
    }

    /**
     * Taking the snapshot again survives a billing having gone from the contract since - which is
     * the very case somebody asks for it in, and which saving the snapshot on its own would have
     * been refused for.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::refreshSnapshot()
     */
    public function testTheSnapshotIsTakenAgainEvenWhenABillingHasGone(): void
    {
        $proposals = $this->getTableLocator()->get('ContractVersionProposals');
        $billings = $this->getTableLocator()->get('Billings');

        // The proposal's snapshot knows this billing; the contract will not.
        $billings->deleteOrFail($billings->get('b2000000-0000-4000-8000-000000000002'));

        $before = $proposals->get(self::PROPOSAL_ID)->snapshot_taken;

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-version-proposals/refresh-snapshot/' . self::PROPOSAL_ID, [
            'acknowledgements' => [
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
            'b2000000-0000-4000-8000-000000000002',
            $after->stateOfThings()->billings(),
        );
    }

    /**
     * Recording that the papers went out settles what stands behind them: nothing about the
     * proposal may be changed afterwards.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::send()
     */
    public function testSendingSettlesTheProposal(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-version-proposals/send/' . self::PROPOSAL_ID, [
            'sent_date' => '2026-10-01',
            'sent_by' => ContractDeliveryMethod::Email->value,
        ]);

        $this->assertRedirect();

        $proposals = $this->getTableLocator()->get('ContractVersionProposals');
        $sent = $proposals->get(self::PROPOSAL_ID);

        $this->assertTrue($sent->hasBeenSent());
        $this->assertFalse($proposals->mayBeEdited($sent));

        $this->get('/contract-version-proposals/edit/' . self::PROPOSAL_ID);
        $this->assertRedirect();
    }

    /**
     * Giving up on a proposal touches nothing else, because the live records never moved.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::revoke()
     */
    public function testRevokingTouchesNothingLive(): void
    {
        $billings = $this->getTableLocator()->get('Billings');
        $before = $billings->find()->count();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-version-proposals/revoke/' . self::PROPOSAL_ID);

        $this->assertRedirect();

        $proposal = $this->getTableLocator()->get('ContractVersionProposals')->get(self::PROPOSAL_ID);
        $this->assertTrue($proposal->hasBeenRevoked());
        $this->assertSame($before, $billings->find()->count());
    }

    /**
     * The preview says what the transfer would run into before anybody presses the button.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::transfer()
     */
    public function testTheTransferPreviewSaysWhatStandsInTheWay(): void
    {
        $this->login();
        $this->get('/contract-version-proposals/transfer/' . self::PROPOSAL_ID);

        $this->assertResponseOk();
        // Nobody has signed it, so it says so and does not offer the button.
        $this->assertResponseContains(__('Nobody has signed this proposal yet, so there is nothing to carry over.'));
    }

    /**
     * A signed proposal that changes nothing is carried over all the same, so that it stops being
     * listed as waiting - and nothing of the contract moves.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::transfer()
     */
    public function testAnEmptyProposalIsMarkedAsDealtWith(): void
    {
        $proposals = $this->getTableLocator()->get('ContractVersionProposals');
        $proposal = $proposals->get(self::PROPOSAL_ID);
        $proposal->conclusion_date = new Date('2026-09-15');
        $proposals->saveOrFail($proposal, ['checkRules' => false]);

        $billings = $this->getTableLocator()->get('Billings');
        $before = $billings->find()->count();

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-version-proposals/transfer/' . self::PROPOSAL_ID);

        $this->assertRedirect();
        $this->assertTrue($proposals->get(self::PROPOSAL_ID)->hasBeenApplied());
        $this->assertSame($before, $billings->find()->count());
    }

    /**
     * An unsigned proposal is not carried over even when the request is made straight at it.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::transfer()
     */
    public function testAnUnsignedProposalIsNotCarriedOver(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-version-proposals/transfer/' . self::PROPOSAL_ID);

        $this->assertFalse(
            $this->getTableLocator()->get('ContractVersionProposals')
                ->get(self::PROPOSAL_ID)
                ->hasBeenApplied(),
        );
    }

    /**
     * A proposal that never went anywhere may be removed; one that did may not.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::delete()
     */
    public function testOnlyAProposalThatWentNowhereIsRemoved(): void
    {
        $proposals = $this->getTableLocator()->get('ContractVersionProposals');

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-version-proposals/delete/' . self::PROPOSAL_ID);
        $this->assertRedirect();
        $this->assertSame(0, $proposals->find()->where(['id' => self::PROPOSAL_ID])->count());
    }

    /**
     * A sent proposal is not removed either.
     *
     * @return void
     * @link \App\Controller\ContractVersionProposalsController::delete()
     */
    public function testASentProposalIsNotRemoved(): void
    {
        $proposals = $this->getTableLocator()->get('ContractVersionProposals');
        $proposal = $proposals->get(self::PROPOSAL_ID);
        $proposal->sent_date = new Date('2026-10-01');
        $proposal->sent_by = ContractDeliveryMethod::Email;
        $proposals->saveOrFail($proposal, ['checkRules' => false]);

        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->post('/contract-version-proposals/delete/' . self::PROPOSAL_ID);

        $this->assertSame(1, $proposals->find()->where(['id' => self::PROPOSAL_ID])->count());
    }
}
