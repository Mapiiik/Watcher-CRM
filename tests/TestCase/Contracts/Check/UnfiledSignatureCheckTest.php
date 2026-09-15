<?php
declare(strict_types=1);

namespace App\Test\TestCase\Contracts\Check;

use App\Contracts\Check\UnfiledSignatureCheck;
use App\Model\Enum\DocumentVariant;
use App\Model\Table\ContractProposalsTable;
use App\Service\ContractPrint\ContractDocuments;
use App\Test\Traits\TableTestTrait;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\Contracts\Check\UnfiledSignatureCheck Test Case
 *
 * The gap this reports is between two things that are both fine: a day somebody typed in, and a
 * scan that has not arrived. What is worth testing is therefore where it stops - a signature
 * recorded this morning is not a finding, and the scan itself makes it one no longer.
 */
#[CoversClass(UnfiledSignatureCheck::class)]
class UnfiledSignatureCheckTest extends TestCase
{
    use TableTestTrait;

    /**
     * The proposal the fixture carries.
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
     * setUp method
     *
     * @return void
     */
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->root = TMP . 'unfiled-signature-' . uniqid();
        Configure::write('Files.root', $this->root);
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

        parent::tearDown();
    }

    /**
     * A signature written down a month ago with nothing on the shelf behind it.
     *
     * @return void
     */
    public function testASignatureWithNoPapersBehindItIsFound(): void
    {
        $this->proposalSays(['conclusion_date' => Date::now()->subDays(30)]);

        $this->assertContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * The scan arrives when the post does, so a signature written down this morning is nothing to
     * report yet.
     *
     * @return void
     */
    public function testASignatureWrittenDownTodayIsNotAFindingYet(): void
    {
        $this->proposalSays(['conclusion_date' => Date::now()]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * Filing the scan is what ends it.
     *
     * @return void
     */
    public function testFilingTheScanTakesItOffTheList(): void
    {
        $this->proposalSays(['conclusion_date' => Date::now()->subDays(30)]);
        $this->fileAScan(DocumentVariant::ReceivedSignedByCustomer);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * What we drew up ourselves is not what is being waited for - it is the customer's signature
     * that has to come back.
     *
     * @return void
     */
    public function testThePaperWeDrewUpOurselvesIsNotTheOneBeingWaitedFor(): void
    {
        $this->proposalSays(['conclusion_date' => Date::now()->subDays(30)]);
        $this->fileAScan(DocumentVariant::Generated);

        $this->assertContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * A proposal nobody has signed belongs to the other check.
     *
     * @return void
     */
    public function testAProposalNobodyHasSignedIsNotFound(): void
    {
        $this->proposalSays(['conclusion_date' => null]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * Nor is one that was given up on, whatever was written down before that.
     *
     * @return void
     */
    public function testARevokedProposalIsNotFound(): void
    {
        $this->proposalSays([
            'conclusion_date' => Date::now()->subDays(30),
            'revoked' => DateTime::now()->subDays(20),
        ]);

        $this->assertNotContains(self::PROPOSAL_ID, $this->found());
    }

    /**
     * The proposal, with what the test wants it to say.
     *
     * @param array<string, mixed> $says What it says.
     * @return void
     */
    private function proposalSays(array $says): void
    {
        $proposals = $this->getTableLocator()->get('ContractProposals');
        $proposal = $proposals->get(self::PROPOSAL_ID);

        // The sending and the signature are the envelope's, so anything said about them is said
        // there - the papers keep the day they take effect and whether they were given up on.
        $envelopes = $this->getTableLocator()->get('CustomerProposals');
        $ofTheRound = array_intersect_key(
            $says,
            array_flip(['sent_date', 'delivery_type', 'conclusion_date']),
        );

        if ($ofTheRound !== []) {
            $envelopes->saveOrFail(
                $envelopes->patchEntity($envelopes->get($proposal->customer_proposal_id), $ofTheRound),
                ['checkRules' => false],
            );
        }

        $ofThePapers = array_diff_key($says, $ofTheRound);

        if ($ofThePapers !== []) {
            $proposals->saveOrFail(
                $proposals->patchEntity($proposal, $ofThePapers),
                ['checkRules' => false],
            );
        }
    }

    /**
     * Files one page against the proposal.
     *
     * @param \App\Model\Enum\DocumentVariant $variant Whose signatures it carries.
     * @return void
     */
    private function fileAScan(DocumentVariant $variant): void
    {
        $storage = new FileStorage();

        $storage->link(
            $storage->store('%PDF-1.7 ' . $variant->value, 'application/pdf'),
            ContractDocuments::MODEL,
            self::PROPOSAL_ID,
            'contract-new',
            $variant->value,
            ['name' => 'scan.pdf'],
        );
    }

    /**
     * What the check finds.
     *
     * @return array<string>
     */
    private function found(): array
    {
        /** @var \App\Model\Table\ContractProposalsTable $proposals */
        $proposals = $this->getTableLocator()->get(ContractProposalsTable::class);

        return (new UnfiledSignatureCheck($proposals))
            ->find()
            ->all()
            ->extract('id')
            ->toList();
    }
}
