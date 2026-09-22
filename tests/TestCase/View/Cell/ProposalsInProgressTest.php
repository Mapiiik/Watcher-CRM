<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Cell;

use App\Test\Traits\ControllerTestTrait;
use App\View\Cell\ProposalsInProgressCell;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The proposals still being worked on are shown on the cards of the contract and the customer they
 * are about, and only while they are: given up on, or done with, they leave the card alone.
 */
#[UsesClass(ProposalsInProgressCell::class)]
class ProposalsInProgressTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';
    private const ROUND_ID = 'a7c1d5e2-3f48-4b90-9c61-2d0e7a5b8f34';
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
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.ContractVersions',
        'app.CustomerProposals',
        'app.ContractProposals',
        'app.ConnectionProfiles',
        'app.Services',
        'app.Billings',
        'app.EquipmentTypes',
        'app.BorrowedEquipments',
        'app.Emails',
        'app.Labels',
        'app.CustomerLabels',
        'app.Logins',
        'app.Phones',
        'app.SoldEquipments',
        'app.IpAddresses',
        'app.RemovedIpAddresses',
        'app.IpNetworks',
        'app.RemovedIpNetworks',
        'app.TaskStates',
        'app.TaskTypes',
        'app.Tasks',
        'app.TaskCollaborators',
        'app.DealerCommissions',
        'app.FulltextSearchCustomers',
    ];

    /**
     * Signs in, the cards being nobody else's to see.
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->login();
    }

    /**
     * An open proposal is on both cards, with its way into the sections down the side.
     *
     * @return void
     */
    public function testAnOpenProposalIsOnBothCards(): void
    {
        foreach ($this->cards() as $card) {
            $this->get($card);

            $this->assertResponseOk();
            $this->assertResponseContains('id="proposals"');
            $this->assertResponseContains('#proposals');
            $this->assertResponseContains('customer-proposals/view/' . self::ROUND_ID);
        }
    }

    /**
     * A proposal given up on is nobody's work, so the cards say nothing about it.
     *
     * @return void
     */
    public function testARevokedProposalIsNotShown(): void
    {
        $rounds = $this->getTableLocator()->get('CustomerProposals');
        $rounds->giveUpOnTheRound($rounds->get(self::ROUND_ID), null);

        $this->assertNothingInProgress();
    }

    /**
     * Nor one that is done with: signed, and every change in it applied.
     *
     * @return void
     */
    public function testAProposalDealtWithIsNotShown(): void
    {
        $rounds = $this->getTableLocator()->get('CustomerProposals');
        $rounds->updateAll(['conclusion_date' => '2026-09-15'], ['id' => self::ROUND_ID]);
        $this->getTableLocator()->get('ContractProposals')
            ->updateAll(['applied' => DateTime::now()], ['id' => self::PROPOSAL_ID]);

        $this->assertNothingInProgress();
    }

    /**
     * One signed and still waiting for its changes is what matters most, and it stays.
     *
     * @return void
     */
    public function testASignedProposalWaitingToBeAppliedIsShown(): void
    {
        $this->getTableLocator()->get('CustomerProposals')
            ->updateAll(['conclusion_date' => '2026-09-15'], ['id' => self::ROUND_ID]);

        foreach ($this->cards() as $card) {
            $this->get($card);

            $this->assertResponseContains('id="proposals"');
        }
    }

    /**
     * Neither card grows a section, nor a way into one, when there is nothing to show.
     *
     * @return void
     */
    private function assertNothingInProgress(): void
    {
        foreach ($this->cards() as $card) {
            $this->get($card);

            $this->assertResponseOk();
            $this->assertResponseNotContains('id="proposals"');
            $this->assertResponseNotContains('#proposals');
        }
    }

    /**
     * The contract's card and the customer's.
     *
     * @return list<string>
     */
    private function cards(): array
    {
        return [
            '/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID,
            '/customers/view/' . self::CUSTOMER_ID,
        ];
    }
}
