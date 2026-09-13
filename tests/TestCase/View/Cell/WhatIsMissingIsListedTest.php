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
        'app.ContractProposals',
        'app.Queues',
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
            'the customer' => $nested . '/documents',
            'the contract' => $nested . '/contracts/' . self::CONTRACT_ID . '/documents',
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
                '/contract-proposals/documents/' . self::PROPOSAL_ID,
                'The papers of ' . $whose . ' offer no way to add the first page.',
            );
        }
    }

    /**
     * @return void
     */
    public function testARevokedRoundWithNothingOnFileIsNotListed(): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $proposal = $proposals->get(self::PROPOSAL_ID);
        $proposal->revoked = new DateTime();
        $proposals->saveOrFail($proposal);

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
     * The card and the printing page read the papers too, and there an empty round is noise.
     *
     * @return void
     */
    public function testTheOtherPagesListOnlyWhatIsOnFile(): void
    {
        $this->login();

        foreach (['', '/print'] as $page) {
            $url = '/customers/' . self::CUSTOMER_ID . $page;

            $this->get($url);
            $this->assertResponseOk();
            $this->assertResponseNotContains(
                '<span class="error-text">Nothing yet</span>',
                $url . ' lists rounds that have nothing to read.',
            );
        }
    }
}
