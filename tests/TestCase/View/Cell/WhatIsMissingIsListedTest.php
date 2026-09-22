<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Cell;

use App\Test\Traits\ControllerTestTrait;
use App\View\Cell\DocumentsCell;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * The papers page lists every round, including the ones nothing has come back for.
 *
 * A table of papers alone cannot be used to add the first one: the round it would hang on is
 * exactly the round with no row to start from. Listing the empty ones also says at a glance what
 * is still outstanding, which is the same question asked the other way round.
 *
 * A revoked round is left out when it has nothing, because nothing is ever coming for it.
 */
#[UsesClass(DocumentsCell::class)]
class WhatIsMissingIsListedTest extends TestCase
{
    use ControllerTestTrait;
    use IntegrationTestTrait;

    private const CUSTOMER_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';
    private const CONTRACT_ID = '7f76dc3f-a11b-4109-958b-4b0382545a66';
    private const PROPOSAL_ID = 'c9a1f2b3-4d5e-4f60-8a71-9b2c3d4e5f60';
    private const VERSION_ID = '74824fba-20b2-46fc-806c-df795aa9e429';
    private const ROUND_ID = 'a7c1d5e2-3f48-4b90-9c61-2d0e7a5b8f34';

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
        'plugin.Files.Files',
        'plugin.Files.FileLinks',
    ];

    /**
     * Where the papers of a record are read, for both agendas that have them.
     *
     * @return array<string, string>
     */
    private function papersPages(): array
    {
        $nested = '/customers/' . self::CUSTOMER_ID;

        return [
            'the customer' => $nested . '/documents/manage',
            'the contract' => $nested . '/contracts/' . self::CONTRACT_ID . '/documents/manage',
        ];
    }

    /**
     * @return void
     */
    public function testARoundWithNothingOnFileIsStillListed(): void
    {
        $this->login();

        foreach ($this->papersPages() as $whose => $url) {
            $this->get($url);
            $this->assertResponseOk();

            $this->assertResponseContains(
                '<span class="error-text">Nothing yet</span>',
                'The papers of ' . $whose . ' say nothing about the round waiting for them.',
            );
            $this->assertResponseContains(
                'proposal_id=' . self::PROPOSAL_ID,
                'The papers of ' . $whose . ' offer no way to add the first page.',
            );
        }
    }

    /**
     * @return void
     */
    public function testARevokedRoundWithNothingOnFileIsNotListed(): void
    {
        // The proposal it is a part of goes with it: it holds nothing else, so nothing is coming
        // for either of them.
        foreach (['ContractProposals' => self::PROPOSAL_ID, 'CustomerProposals' => self::ROUND_ID] as $agenda => $id) {
            $records = $this->getTableLocator()->get($agenda);
            $records->saveOrFail(
                $records->patchEntity($records->get($id), ['revoked' => new DateTime()]),
            );
        }

        $this->login();

        foreach ($this->papersPages() as $whose => $url) {
            $this->get($url);
            $this->assertResponseOk();

            $this->assertResponseNotContains(
                '<span class="error-text">Nothing yet</span>',
                'The papers of ' . $whose . ' wait for a round that was called off.',
            );
        }
    }

    /**
     * A round that asks nothing of the customer holds only the papers of their contracts, so it
     * has no paper of its own - nothing went out for it and nothing is coming. It says nothing on
     * the side that comes back, rather than waiting for a scan that cannot exist. This is what a
     * round made by the backfill looks like, so it is the common one.
     *
     * @return void
     */
    public function testARoundWithNoPaperOfItsOwnWaitsForNothing(): void
    {
        $rounds = $this->getTableLocator()->get('CustomerProposals');
        $rounds->saveOrFail(
            $rounds->patchEntity($rounds->get(self::ROUND_ID), ['purpose' => null]),
            ['checkRules' => false],
        );

        $this->login();

        foreach ($this->papersPages() as $whose => $url) {
            $this->get($url);
            $this->assertResponseOk();

            // The round is still listed among the proposals, which is where it belongs - what it
            // must not do is stand among the papers asking for a scan.
            $this->assertResponseNotContains(
                '/documents/add-pages?proposal_id=' . self::ROUND_ID,
                'The papers of ' . $whose . ' ask for a scan of a paper the round never had.',
            );
            // Its contract's papers are still waiting for theirs.
            $this->assertResponseContains(
                '/documents/add-pages?proposal_id=' . self::PROPOSAL_ID,
                'The papers of ' . $whose . ' forgot the contract that is waiting for a scan.',
            );
        }
    }

    /**
     * The card on the customer reads the papers too, and there an empty round is noise.
     *
     * @return void
     */
    public function testTheOtherPagesListOnlyWhatIsOnFile(): void
    {
        $this->login();

        $url = '/customers/' . self::CUSTOMER_ID;

        $this->get($url);
        $this->assertResponseOk();
        $this->assertResponseNotContains(
            '<span class="error-text">Nothing yet</span>',
            $url . ' lists rounds that have nothing to read.',
        );
    }

    /**
     * On the side we draw ourselves, what is missing is each paper the round owes rather than one
     * line saying the round is empty - so the gap is named and may be clicked on.
     *
     * @return void
     */
    public function testEachPaperTheRoundOwesIsNamedAndOffered(): void
    {
        $this->login();

        $this->get('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID
            . '/contract-versions/' . self::VERSION_ID . '/documents/manage'
            . '?agenda=ContractProposals&proposal_id=' . self::PROPOSAL_ID);
        $this->assertResponseOk();

        $this->assertResponseContains(__('Not generated yet'));
        $this->assertResponseContains(__('Generate'));
        $this->assertResponseContains('document_type=contract-summary');
        // Drawing the paper writes it down in this very table, so the page reads itself again
        // once the reader comes back from the document.
        $this->assertResponseContains('refresh-on-return');
        // A paper the round owes and has not got is a gap, and reads as one.
        $this->assertResponseContains('class="error-text');
    }

    /**
     * A paper that is drawn up only when somebody wants one is still listed, but quietly: its
     * absence is a choice rather than a gap.
     *
     * @return void
     */
    public function testAPaperNobodyHasToHaveIsSaidMoreQuietly(): void
    {
        // The handover protocol is offered where there is equipment to hand over, and the papers
        // are drawn from the snapshot rather than from the records.
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $proposal = $proposals->get(self::PROPOSAL_ID);
        $snapshot = $proposal->snapshot;
        $snapshot['contract']['service_type']['have_equipments'] = true;
        $proposals->saveOrFail(
            $proposals->patchEntity($proposal, ['snapshot' => $snapshot]),
            ['checkRules' => false],
        );

        $this->login();

        $this->get('/customers/' . self::CUSTOMER_ID . '/contracts/' . self::CONTRACT_ID
            . '/contract-versions/' . self::VERSION_ID . '/documents/manage'
            . '?agenda=ContractProposals&proposal_id=' . self::PROPOSAL_ID);
        $this->assertResponseOk();

        $this->assertResponseContains('class="warning-text');
    }
}
