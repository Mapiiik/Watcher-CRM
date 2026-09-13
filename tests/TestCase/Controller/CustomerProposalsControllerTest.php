<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Controller\CustomerProposalsController;
use App\Model\Entity\CustomerProposal;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentsDeliveryType;
use App\Test\Traits\ControllerTestTrait;
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
    ];

    /**
     * The listing renders, with and without the ones already settled.
     *
     * @link \App\Controller\CustomerProposalsController::index()
     * @return void
     */
    public function testIndex(): void
    {
        $this->login();

        $this->get('/customer-proposals');
        $this->assertResponseOk();

        $this->get('/customer-proposals?show_settled=1&search=lorem');
        $this->assertResponseOk();
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
     * Signing ends the round. Nothing stands behind it waiting to be carried over, so there is
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

        $signed = $this->getTableLocator()->get('CustomerProposals')->get($proposal->id);
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
        $this->assertNotNull($proposals->find()->where(['id' => $stays->id])->first());

        $goes = $this->drawOneUp();
        $this->post('/customer-proposals/delete/' . $goes->id);
        $this->assertNull($proposals->find()->where(['id' => $goes->id])->first());
    }

    /**
     * Draws a round up the way the page does, and hands back what was saved.
     *
     * @return \App\Model\Entity\CustomerProposal
     */
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
        $this->assertRedirect();

        /** @var \App\Model\Entity\CustomerProposal $proposal */
        $proposal = $this->addedRecord('CustomerProposals', $before);

        return $proposal;
    }
}
