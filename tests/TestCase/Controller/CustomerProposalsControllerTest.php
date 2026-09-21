<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CustomerProposalsController;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentsDeliveryType;
use App\Model\Enum\ProposalPurpose;
use App\Test\Traits\ControllerTestTrait;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Controller\CustomerProposalsController Test Case
 *
 * A round of papers put to the customer travels the same road a contract's proposal does, so what
 * is asked of it is the road: that it can be drawn up, that sending locks it, that signing ends it,
 * and that what has gone out cannot be taken back as though it never had.
 */
#[UsesClass(CustomerProposalsController::class)]
class CustomerProposalsControllerTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    /**
     * The customer the rounds hang on.
     *
     * @var string
     */
    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * A contract of theirs, which a round may be about as well.
     *
     * @var string
     */
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';
    private const CONTRACT_VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';

    /**
     * And another of theirs, so a proposal may be about two.
     *
     * @var string
     */
    private const OTHER_CONTRACT_ID = '9c0d5e5c-2a6b-4f8e-9a3d-1b7c4e2f6a90';

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
        'app.Queues',
        'app.Services',
        'app.Billings',
        'app.ContractProposals',
        'plugin.Settings.Settings',
    ];

    /**
     * A round with one contract's papers in it, drawn up the one way there is.
     *
     * @return \App\Model\Entity\CustomerProposal
     */
    private function drawOneUpWithPapers(): CustomerProposal
    {
        $round = $this->drawOneUp();

        $this->post('/customers/' . self::CUSTOMER_ID . '/contract-proposals/add', [
            'purpose' => ProposalPurpose::NewContract->value,
            'contract_id' => self::CONTRACT_ID,
            'customer_proposal_id' => $round->id,
            'contract_version_id' => '',
            'effective_from' => $round->effective_from->toDateString(),
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);
        $this->assertRedirectContains('/contract-proposals/view/');
        $this->assertCount(1, $this->papersOf((string)$round->id));

        // Asked again, because a round that now holds papers says so about itself.
        /** @var \App\Model\Entity\CustomerProposal $held */
        $held = $this->getTableLocator()->get('CustomerProposals')
            ->get($round->id, contain: ['ContractProposals']);

        return $held;
    }

    /**
     * A round is drawn up against the customer the page was opened under.
     *
     * @link \App\Controller\CustomerProposalsController::add()
     * @return void
     */
    public function testARoundIsDrawnUp(): void
    {
        $proposal = $this->drawOneUp();

        $this->assertSame(CustomerProposalPurpose::GdprConsent, $proposal->purpose);
        $this->assertSame(self::CUSTOMER_ID, $proposal->customer_id);
        $this->assertTrue($proposal->isOpen());
        $this->assertSame(__('Being prepared'), $proposal->getState());
    }

    /**
     * The detail and the forms render.
     *
     * @link \App\Controller\CustomerProposalsController::view()
     * @return void
     */
    public function testTheDetailAndTheFormsRender(): void
    {
        $proposal = $this->drawOneUp();

        foreach (['view', 'edit', 'send', 'conclude'] as $action) {
            // under the customer, which is where these pages belong and where a bare address is sent
            $this->get(sprintf(
                '/customers/%s/customer-proposals/%s/%s',
                $proposal->customer_id,
                $action,
                $proposal->id,
            ));
            $this->assertResponseOk(sprintf('%s did not render.', $action));
        }
    }

    /**
     * And they render for the roles that are asked the question rather than let straight through.
     *
     * Whether the Delete link is drawn is settled by reading the record, and the reading has to
     * carry what the answer is worked out from: whether anything hangs on the round is looked up
     * by the round's own id. Asked without it, the page of a round just drawn up - one neither
     * sent nor signed, so the only one the question gets that far on - came back as an error.
     *
     * Everything else here logs in as an administrator, who never reaches the question at all.
     *
     * @link \App\Controller\CustomerProposalsController::view()
     * @return void
     */
    public function testTheDetailRendersForWhoeverIsAskedWhetherItMayGo(): void
    {
        $proposal = $this->drawOneUp();

        foreach (['sales-representative', 'sales-manager', 'network-manager', 'bookkeeper'] as $role) {
            $this->login($role);
            $this->get(sprintf(
                '/customers/%s/customer-proposals/view/%s',
                $proposal->customer_id,
                $proposal->id,
            ));

            $this->assertResponseOk(sprintf('The detail did not render for a %s.', $role));
        }
    }

    /**
     * Sending is what locks a round: what stood behind a paper that has left the building is not
     * rewritten afterwards.
     *
     * @link \App\Controller\CustomerProposalsController::send()
     * @return void
     */
    public function testSendingLocksTheRound(): void
    {
        $proposal = $this->drawOneUp();
        $proposals = $this->getTableLocator()->get('CustomerProposals');

        $this->assertTrue($proposals->mayBeEdited($proposal));

        $this->post('/customer-proposals/send/' . $proposal->id, [
            'sent_date' => '2026-10-01',
            'delivery_type' => DocumentsDeliveryType::Post->value,
        ]);
        $this->assertRedirect();

        $sent = $proposals->get($proposal->id);
        $this->assertTrue($sent->hasBeenSent());
        $this->assertFalse($proposals->mayBeEdited($sent));
        $this->assertFalse($proposals->mayBeDeleted($sent));
        $this->assertSame(__('Sent'), $sent->getState());
    }

    /**
     * Every step taken on a proposal leaves the reader on the proposal. The card it hangs on is
     * where the nesting would otherwise send them, and then what was just recorded is off screen.
     *
     * @link \App\Controller\CustomerProposalsController::send()
     * @link \App\Controller\CustomerProposalsController::conclude()
     * @link \App\Controller\CustomerProposalsController::applyChanges()
     * @link \App\Controller\CustomerProposalsController::revoke()
     * @return void
     */
    public function testEveryStepLeavesTheReaderOnTheProposal(): void
    {
        $round = $this->drawOneUpWithPapers();
        $at = '/customers/' . self::CUSTOMER_ID . '/customer-proposals/';
        $its = '/customer-proposals/view/' . $round->id;

        $this->post($at . 'send/' . $round->id, [
            'sent_date' => '2026-10-01',
            'delivery_type' => DocumentsDeliveryType::Post->value,
        ]);
        $this->assertRedirectContains($its);

        $this->post($at . 'conclude/' . $round->id, ['conclusion_date' => '2026-10-05']);
        $this->assertRedirectContains($its);

        $this->post($at . 'apply-changes/' . $round->id);
        $this->assertRedirectContains($its);

        // The step used to be called applying the changes, and a bookmark to it still arrives.
        $this->get($at . 'transfer/' . $round->id);
        $this->assertRedirectContains('/customer-proposals/apply-changes/' . $round->id);

        $this->post($at . 'revoke/' . $round->id);
        $this->assertRedirectContains($its);
    }

    /**
     * The day and the way belong together, so a sending that does not say how is refused.
     *
     * @link \App\Model\Table\CustomerProposalsTable::buildRules()
     * @return void
     */
    public function testASendingHasToSayHow(): void
    {
        $proposal = $this->drawOneUp();

        $this->post('/customer-proposals/send/' . $proposal->id, ['sent_date' => '2026-10-01']);

        $this->assertResponseOk();
        $this->assertNull(
            $this->getTableLocator()->get('CustomerProposals')->get($proposal->id)->sent_date,
        );
    }

    /**
     * Signing ends the round. Nothing stands behind it waiting to be applied, so there is
     * nothing left to do with it afterwards.
     *
     * @link \App\Controller\CustomerProposalsController::conclude()
     * @return void
     */
    public function testSigningEndsTheRound(): void
    {
        $proposal = $this->drawOneUp();

        $this->post('/customer-proposals/conclude/' . $proposal->id, [
            'conclusion_date' => '2026-10-05',
        ]);
        $this->assertRedirect();

        // Read with what it holds, because after the signature the round says what is left to do
        // in it - and an empty one has nothing left.
        $signed = $this->getTableLocator()->get('CustomerProposals')
            ->get($proposal->id, contain: ['ContractProposals']);
        $this->assertFalse($signed->isOpen());
        $this->assertSame(__('Signed'), $signed->getState());

        // And the steps that only apply while it is open say so rather than doing anything.
        $this->post('/customer-proposals/send/' . $proposal->id, [
            'sent_date' => '2026-10-06',
            'delivery_type' => DocumentsDeliveryType::Email->value,
        ]);
        $this->assertRedirect();
        $this->assertNull(
            $this->getTableLocator()->get('CustomerProposals')->get($proposal->id)->sent_date,
        );
    }

    /**
     * A round is settled by the signature, but the day it was signed is a typed-in date like any
     * other, so it stays correctable until the round is given up on.
     *
     * @link \App\Controller\CustomerProposalsController::conclude()
     * @return void
     */
    public function testTheDayOfTheSignatureStaysCorrectable(): void
    {
        $proposal = $this->drawOneUp();
        $proposals = $this->getTableLocator()->get('CustomerProposals');

        $this->post('/customer-proposals/conclude/' . $proposal->id, ['conclusion_date' => '2026-10-05']);
        $this->assertRedirect();

        $this->post('/customer-proposals/conclude/' . $proposal->id, ['conclusion_date' => '2026-10-06']);
        $this->assertRedirect();
        $this->assertSame('2026-10-06', $proposals->get($proposal->id)->conclusion_date?->toDateString());

        // Giving up on a round is the one thing that closes the door, and it can only be done
        // before there is a signature to speak of.
        $abandoned = $this->drawOneUp();
        $this->post('/customer-proposals/revoke/' . $abandoned->id);
        $this->post('/customer-proposals/conclude/' . $abandoned->id, ['conclusion_date' => '2026-10-07']);
        $this->assertRedirect();
        $this->assertNull($proposals->get($abandoned->id)->conclusion_date);
    }

    /**
     * Giving up on a round settles it without pretending it never happened.
     *
     * @link \App\Controller\CustomerProposalsController::revoke()
     * @return void
     */
    public function testGivingUpSettlesIt(): void
    {
        $proposal = $this->drawOneUp();

        $this->post('/customer-proposals/revoke/' . $proposal->id);
        $this->assertRedirect();

        $revoked = $this->getTableLocator()->get('CustomerProposals')->get($proposal->id);
        $this->assertFalse($revoked->isOpen());
        $this->assertSame(__('Revoked'), $revoked->getState());
    }

    /**
     * A round that went out is history and stays. One that never went anywhere is somebody's
     * mistake and may go.
     *
     * @link \App\Controller\CustomerProposalsController::delete()
     * @return void
     */
    public function testOnlyARoundThatWentNowhereIsRemoved(): void
    {
        $proposals = $this->getTableLocator()->get('CustomerProposals');

        $stays = $this->drawOneUp();
        $proposals->saveOrFail(
            $proposals->patchEntity($stays, [
                'sent_date' => '2026-10-01',
                'delivery_type' => DocumentsDeliveryType::Post->value,
            ]),
        );

        $this->post('/customer-proposals/delete/' . $stays->id);
        $this->assertRedirectContains('/customer-proposals/view/' . $stays->id);
        $this->assertNotNull($proposals->find()->where(['id' => $stays->id])->first());

        $goes = $this->drawOneUp();
        $this->post('/customer-proposals/delete/' . $goes->id);
        $this->assertRedirectContains('/customers/' . self::CUSTOMER_ID . '/documents/manage');
        $this->assertNull($proposals->find()->where(['id' => $goes->id])->first());
    }

    /**
     * Draws a round up the way the page does, and hands back what was saved.
     *
     * @return \App\Model\Entity\CustomerProposal
     */

    /**
     * A round may be for nothing of the customer's own and hold only the papers of their
     * contracts - a new contract needs an envelope whether or not anything is asked beside it.
     *
     * @link \App\Controller\CustomerProposalsController::add()
     * @return void
     */
    public function testARoundNeedNotBeForAnythingOfTheCustomersOwn(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('CustomerProposals');

        $this->post('/customers/' . self::CUSTOMER_ID . '/customer-proposals/add', [
            'customer_id' => self::CUSTOMER_ID,
            'purpose' => '',
            'effective_from' => '2026-09-30',
        ]);
        $this->assertRedirect();

        /** @var \App\Model\Entity\CustomerProposal $round */
        $round = $this->addedRecord('CustomerProposals', $before);

        $this->assertNull($round->purpose);
    }

    /**
     * Papers of a contract are drawn up on the form that draws papers up, and opened from inside a
     * round they start out in it and speak about the day it does - which is what makes the two
     * sets of papers one envelope.
     *
     * @link \App\Controller\ContractProposalsController::add()
     * @return void
     */
    public function testPapersDrawnUpFromInsideARoundGoOutInIt(): void
    {
        $round = $this->drawOneUp();

        $this->get('/customers/' . self::CUSTOMER_ID . '/contract-proposals/add'
            . '?proposal_id=' . $round->id);
        $this->assertResponseOk();

        $drawn = $this->viewVariable('contractProposal');
        $this->assertSame($round->id, $drawn->customer_proposal_id);
        $this->assertSame(
            $round->effective_from->toDateString(),
            $drawn->effective_from->toDateString(),
        );
    }

    /**
     * The listing lists proposals. What one does for a contract is a part of it, so it is named on
     * the proposal's own row rather than listed beside it as a second kind of thing.
     *
     * @link \App\Controller\DocumentsController::manage()
     * @return void
     */
    public function testOnlyTheProposalIsListedAndItsContractsAreNamedOnItsRow(): void
    {
        $round = $this->drawOneUpWithPapers();
        $papers = $this->papersOf((string)$round->id)[0];
        $papers = $this->getTableLocator()->get('ContractProposals')
            ->get($papers->id, contain: ['Contracts']);

        $this->get('/customers/' . self::CUSTOMER_ID . '/documents/manage');
        $this->assertResponseOk();

        // Asked of the listing itself: the papers of a contract are all over the page, because
        // that is what the documents hang on - they are just not one of the things listed.
        $listed = array_column((array)$this->viewVariable('rounds'), 'id');
        $this->assertContains((string)$round->id, $listed);
        $this->assertNotContains(
            (string)$papers->id,
            $listed,
            'The papers of a contract stand beside the proposal they are a part of.',
        );

        // The row says which contract its papers are about, from when and what they ask of it.
        $this->assertResponseContains(h(__(
            '{0} ({1} - {2})',
            $papers->contract->number,
            (string)$papers->effective_from,
            $papers->purpose->label(),
        )));
    }

    /**
     * The proposal's own page says what it does for each contract, and offers the same way to
     * change it - so a billing line is edited where the rest of the proposal is read.
     *
     * @link \App\Controller\CustomerProposalsController::view()
     * @return void
     */
    public function testTheProposalSaysWhatItDoesForEachContract(): void
    {
        $round = $this->drawOneUpWithPapers();
        $papers = $this->papersOf((string)$round->id)[0];

        $this->get('/customers/' . self::CUSTOMER_ID . '/customer-proposals/view/' . $round->id);
        $this->assertResponseOk();

        $this->assertResponseContains(__('Billing after the change'));
        $this->assertResponseContains('billing-line/' . $papers->id);
    }

    /**
     * A round holding the papers of a contract is not removed either. They keep their sending and
     * their signature in it, so letting it go would leave them saying nothing about either.
     *
     * @link \App\Controller\CustomerProposalsController::delete()
     * @return void
     */
    public function testARoundWithPapersInItIsNotRemoved(): void
    {
        $proposals = $this->getTableLocator()->get('CustomerProposals');
        $round = $this->drawOneUpWithPapers();

        $this->post('/customer-proposals/delete/' . $round->id);

        $this->assertNotNull($proposals->find()->where(['id' => $round->id])->first());
    }

    /**
     * A proposal about two contracts is one proposal, and is listed once - which is what a join
     * to what is inside it would quietly get wrong.
     *
     * @link \App\Controller\DocumentsController::manage()
     * @return void
     */
    public function testAProposalAboutTwoContractsIsListedOnce(): void
    {
        $round = $this->drawOneUpWithPapers();

        // Papers for a second contract of the same customer, in the same proposal.
        $papers = $this->getTableLocator()->get('ContractProposals');
        $first = $this->papersOf((string)$round->id)[0]->toArray();
        // The envelope came along to be read; copied on, it would be marshalled as a record of
        // its own. What the copy needs is which envelope, which it already has.
        unset($first['id'], $first['customer_proposal']);
        $first['contract_id'] = self::OTHER_CONTRACT_ID;
        $papers->saveOrFail($papers->newEntity($first), ['checkRules' => false]);

        $this->assertCount(2, $this->papersOf((string)$round->id));

        foreach (['', '/contracts/' . self::CONTRACT_ID] as $nesting) {
            $this->get('/customers/' . self::CUSTOMER_ID . $nesting . '/documents/manage');
            $this->assertResponseOk();

            $listed = array_column((array)$this->viewVariable('rounds'), 'id');
            $this->assertSame(
                array_unique($listed),
                $listed,
                'The proposal is listed once for each contract it is about.',
            );
        }
    }

    /**
     * Papers drawn up from a contract get a proposal of their own, and the form says what that one
     * asks of the customer themselves - nothing, unless somebody says so.
     *
     * @link \App\Controller\ContractProposalsController::add()
     * @return void
     */
    public function testPapersDrawnUpOnTheirOwnSayWhatTheirProposalIsFor(): void
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $drawnUp = function (array $said): CustomerProposal {
            $before = $this->idsIn('CustomerProposals');

            $this->post('/customers/' . self::CUSTOMER_ID . '/contract-proposals/add', $said + [
                'purpose' => ProposalPurpose::NewContract->value,
                'contract_id' => self::CONTRACT_ID,
                'contract_version_id' => '',
                'effective_from' => '2026-09-30',
                'confirmations' => [
                    'fixed_term' => 1,
                    'own_equipment' => 1,
                    'does_not_use_ip_addresses' => 1,
                    'does_not_use_radius' => 1,
                ],
            ]);
            $this->assertRedirectContains('/contract-proposals/view/');

            /** @var \App\Model\Entity\CustomerProposal $round */
            $round = $this->addedRecord('CustomerProposals', $before);

            return $round;
        };

        $this->assertNull($drawnUp([])->purpose);
        $this->assertSame(
            CustomerProposalPurpose::GdprConsent,
            $drawnUp(['new_round_purpose' => CustomerProposalPurpose::GdprConsent->value])->purpose,
        );
    }

    /**
     * A proposal opened while standing on a contract holds nothing yet, and that is exactly the
     * one somebody is about to put the contract into - so the contract's workbench lists it.
     *
     * Hiding it made a dead end: the proposal existed and there was nowhere to reach it from.
     *
     * @link \App\Controller\DocumentsController::manage()
     * @return void
     */
    public function testAProposalWithNothingInItIsStillListedOnAContract(): void
    {
        $round = $this->drawOneUp();
        $this->assertSame([], $this->papersOf((string)$round->id));

        $this->get('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID
            . '/documents/manage');
        $this->assertResponseOk();

        $this->assertContains(
            (string)$round->id,
            array_column((array)$this->viewVariable('rounds'), 'id'),
            'A proposal with nothing in it cannot be reached from the contract it was opened on.',
        );

        // A version is narrower than that: a proposal that says nothing about any contract says
        // nothing about one of its versions either, and the page would only be the longer for it.
        $this->get('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID
            . '/contract-versions/' . self::CONTRACT_VERSION_ID . '/documents/manage');
        $this->assertResponseOk();

        $this->assertNotContains(
            (string)$round->id,
            array_column((array)$this->viewVariable('rounds'), 'id'),
        );

        // And once it is settled it is waiting for nothing, so the contract stops offering it.
        $rounds = $this->getTableLocator()->get('CustomerProposals');
        $rounds->saveOrFail(
            $rounds->patchEntity($rounds->get($round->id), ['conclusion_date' => '2026-10-05']),
            ['checkRules' => false, 'validate' => false],
        );

        $this->get('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID
            . '/documents/manage');
        $this->assertResponseOk();

        $this->assertNotContains(
            (string)$round->id,
            array_column((array)$this->viewVariable('rounds'), 'id'),
        );
    }

    /**
     * Papers of a contract are a part of a proposal, so the way out of them runs through it - the
     * proposal is a step of the path even when what is open is one of its parts.
     *
     * @link \App\Controller\DocumentsController::manage()
     * @return void
     */
    public function testThePathThroughAContractsPapersRunsThroughTheProposal(): void
    {
        $round = $this->drawOneUpWithPapers();
        $papers = $this->papersOf((string)$round->id)[0];

        $asked = '?agenda=ContractProposals&proposal_id=' . $papers->id;

        // The papers say which contract they are about, so the address is filled in before the
        // page is drawn - what was asked for is there, only at the address that says where it is.
        $this->get('/customers/' . self::CUSTOMER_ID . '/documents/manage' . $asked);
        $this->assertRedirectContains(
            '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID . '/documents/manage',
        );
        $this->assertRedirectContains('proposal_id=' . $papers->id);

        $this->get('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID
            . '/documents/manage' . $asked);
        $this->assertResponseOk();

        // The proposal stands in the path, and stands there as the way back up to it.
        $this->assertResponseContains('proposal_id=' . $round->id);
        $this->assertResponseContains(h($round->whatItIsFor()));
    }

    /**
     * The envelope goes out in one piece, so what it holds is among its papers - and the one
     * switch above the table is what takes the parts back out of view.
     *
     * @link \App\Controller\DocumentsController::manage()
     * @return void
     */
    public function testThePapersOfTheContractsAreInViewWithTheProposalsOwn(): void
    {
        $round = $this->drawOneUpWithPapers();
        $papers = $this->papersOf((string)$round->id)[0];

        $at = '/customers/' . self::CUSTOMER_ID . '/documents/manage'
            . '?agenda=CustomerProposals&proposal_id=' . $round->id;
        // Asked of the table of papers itself: a way to draw one is a row of it and nowhere else.
        $drawn = 'generate.pdf?proposal_id=' . $papers->id;

        $this->get($at);
        $this->assertResponseOk();
        $this->assertResponseContains($drawn, 'The papers of the contract are asked for nowhere.');

        $this->get($at . '&with_contracts=0');
        $this->assertResponseOk();
        $this->assertResponseNotContains($drawn);
    }

    /**
     * The envelope went out in one piece, so the day it went out is written on everything in it.
     *
     * @link \App\Controller\CustomerProposalsController::send()
     * @return void
     */
    public function testSendingTheRoundSendsEverythingInIt(): void
    {
        $round = $this->drawOneUpWithPapers();

        $this->post('/customers/' . self::CUSTOMER_ID . '/customer-proposals/send/' . $round->id, [
            'sent_date' => '2026-10-01',
            'delivery_type' => DocumentsDeliveryType::Email->value,
        ]);
        $this->assertRedirect();

        foreach ($this->papersOf((string)$round->id) as $papers) {
            $this->assertSame('2026-10-01', $papers->sent_date?->toDateString());
            $this->assertSame(DocumentsDeliveryType::Email, $papers->delivery_type);
        }
    }

    /**
     * And it came back in one piece, so the signature reaches all of it - and a signed consent is
     * what the customer's own record has been waiting for.
     *
     * @link \App\Controller\CustomerProposalsController::conclude()
     * @return void
     */
    public function testSigningTheRoundSignsEverythingInItAndRecordsTheConsent(): void
    {
        $round = $this->drawOneUpWithPapers();

        $customers = $this->getTableLocator()->get('Customers');
        $customers->saveOrFail(
            $customers->patchEntity($customers->get(self::CUSTOMER_ID), ['agree_gdpr' => false]),
        );

        $this->post('/customers/' . self::CUSTOMER_ID . '/customer-proposals/conclude/' . $round->id, [
            'conclusion_date' => '2026-10-05',
        ]);
        $this->assertRedirect();

        foreach ($this->papersOf((string)$round->id) as $papers) {
            $this->assertSame('2026-10-05', $papers->conclusion_date?->toDateString());
        }

        $this->assertTrue(
            $customers->get(self::CUSTOMER_ID)->agree_gdpr,
            'A signed consent left the customer reading as having refused.',
        );
    }

    /**
     * Giving up on the round gives up on the papers it holds: they went out in it and there is
     * nothing left for them to travel in, so they stop waiting for anybody.
     *
     * @link \App\Model\Table\CustomerProposalsTable::giveUpOnTheRound()
     * @return void
     */
    public function testGivingUpOnTheRoundGivesUpOnThePapersInIt(): void
    {
        $round = $this->drawOneUpWithPapers();

        $this->post('/customers/' . self::CUSTOMER_ID . '/customer-proposals/revoke/' . $round->id);
        $this->assertRedirect();

        $papers = $this->papersOf((string)$round->id);
        $this->assertNotSame([], $papers);

        foreach ($papers as $of) {
            $this->assertTrue($of->hasBeenRevoked(), 'The papers were left waiting in an envelope nobody sent.');
            $this->assertFalse($of->isOpen());
        }
    }

    /**
     * Applying the changes is offered while something is left to apply, and a round given up on has
     * nothing - so neither the page nor the action lead anywhere from it.
     *
     * @link \App\Model\Entity\CustomerProposal::hasChangesToApply()
     * @link \App\Controller\CustomerProposalsController::applyChanges()
     * @return void
     */
    public function testARevokedRoundOffersNothingToApply(): void
    {
        $round = $this->drawOneUpWithPapers();
        $at = '/customers/' . self::CUSTOMER_ID . '/customer-proposals/';
        $applyChanges = 'customer-proposals/apply-changes/' . $round->id;

        $this->get($at . 'view/' . $round->id);
        $this->assertResponseContains($applyChanges);

        $this->post($at . 'revoke/' . $round->id);

        $this->get($at . 'view/' . $round->id);
        $this->assertResponseOk();
        $this->assertResponseNotContains($applyChanges);

        $this->get($at . 'apply-changes/' . $round->id);
        $this->assertRedirectContains('/customer-proposals/view/' . $round->id);
    }

    /**
     * Applying the package passes by the papers given up on inside it: they stay given up
     * on, and the rest of the package is applied all the same.
     *
     * @link \App\Controller\CustomerProposalsController::applyChanges()
     * @return void
     */
    public function testApplyingTheChangesPassesByThePapersGivenUpOn(): void
    {
        $round = $this->drawOneUpWithPapers();
        $given_up = $this->papersOf((string)$round->id)[0];

        $this->post('/customers/' . self::CUSTOMER_ID . '/contract-proposals/add', [
            'purpose' => ProposalPurpose::NewContract->value,
            'contract_id' => self::OTHER_CONTRACT_ID,
            'customer_proposal_id' => $round->id,
            'contract_version_id' => '',
            'effective_from' => $round->effective_from->toDateString(),
            'confirmations' => [
                'fixed_term' => 1,
                'own_equipment' => 1,
                'does_not_use_ip_addresses' => 1,
                'does_not_use_radius' => 1,
            ],
        ]);
        $this->assertRedirectContains('/contract-proposals/view/');

        $this->post('/contract-proposals/revoke/' . $given_up->id);

        $at = '/customers/' . self::CUSTOMER_ID . '/customer-proposals/';
        $this->post($at . 'send/' . $round->id, [
            'sent_date' => '2026-10-01',
            'delivery_type' => DocumentsDeliveryType::Post->value,
        ]);
        $this->post($at . 'conclude/' . $round->id, ['conclusion_date' => '2026-10-05']);

        $this->get($at . 'apply-changes/' . $round->id);
        $this->assertResponseOk();
        $offered = array_map(
            fn(array $part): string => (string)$part['papers']->id,
            (array)$this->viewVariable('parts'),
        );
        $this->assertNotContains((string)$given_up->id, $offered, 'The preview offered papers given up on.');
        $this->assertCount(1, $offered);

        $this->post($at . 'apply-changes/' . $round->id);
        $this->assertRedirectContains('/customer-proposals/view/' . $round->id);

        foreach ($this->papersOf((string)$round->id) as $papers) {
            if ($papers->id === $given_up->id) {
                $this->assertTrue($papers->hasBeenRevoked());
                $this->assertFalse($papers->hasBeenApplied(), 'Papers given up on were carried over.');
            } else {
                $this->assertTrue($papers->hasBeenApplied(), 'The rest of the package was not carried over.');
            }
        }
    }

    /**
     * Papers given up on by themselves keep the day and the name that are against them, because
     * that is when somebody gave up on those papers.
     *
     * @link \App\Model\Table\CustomerProposalsTable::giveUpOnTheRound()
     * @return void
     */
    public function testPapersGivenUpOnEarlierKeepTheirOwnDay(): void
    {
        $round = $this->drawOneUpWithPapers();
        $papers = $this->getTableLocator()->get('ContractProposals');
        $of = $papers->get($this->papersOf((string)$round->id)[0]->id);

        $of->revoked = new DateTime('2026-09-01 08:00:00');
        $papers->saveOrFail($of, ['checkRules' => false]);

        $this->post('/customers/' . self::CUSTOMER_ID . '/customer-proposals/revoke/' . $round->id);
        $this->assertRedirect();

        $this->assertSame(
            '2026-09-01 08:00:00',
            $papers->get($of->id)->revoked?->format('Y-m-d H:i:s'),
        );
    }

    /**
     * The papers that went out in one envelope.
     *
     * @param string $proposal_id Which envelope.
     * @return array<\App\Model\Entity\ContractProposal>
     */
    private function papersOf(string $proposal_id): array
    {
        /** @var array<\App\Model\Entity\ContractProposal> $papers */
        $papers = $this->getTableLocator()->get('ContractProposals')
            ->find()
            // What went out and when is the envelope's, so the papers are read with it.
            ->contain(['CustomerProposals'])
            ->where(['ContractProposals.customer_proposal_id' => $proposal_id])
            ->toArray();

        return $papers;
    }

    private function drawOneUp(): CustomerProposal
    {
        $this->login();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $before = $this->idsIn('CustomerProposals');

        $this->post('/customers/' . self::CUSTOMER_ID . '/customer-proposals/add', [
            'customer_id' => self::CUSTOMER_ID,
            'purpose' => CustomerProposalPurpose::GdprConsent->value,
            'effective_from' => '2026-09-30',
        ]);
        // A proposal is read where it now stands, not on the card it was opened from.
        $this->assertRedirectContains('/customer-proposals/view/');

        /** @var \App\Model\Entity\CustomerProposal $proposal */
        $proposal = $this->addedRecord('CustomerProposals', $before);

        return $proposal;
    }
}
