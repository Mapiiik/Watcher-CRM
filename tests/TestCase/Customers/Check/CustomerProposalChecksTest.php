<?php
declare(strict_types=1);

namespace App\Test\TestCase\Customers\Check;

use App\Customers\Check\AbstractCustomerProposalCheck;
use App\Customers\Check\UnfiledCustomerProposalCheck;
use App\Customers\Check\UnsentCustomerProposalCheck;
use App\Customers\Check\UnsignedCustomerProposalCheck;
use App\Model\Enum\CustomerDocumentType;
use App\Model\Enum\CustomerProposalPurpose;
use App\Model\Enum\DocumentVariant;
use App\Model\Table\CustomerProposalsTable;
use App\Service\CustomerPrint\CustomerDocuments;
use App\Test\Traits\TableTestTrait;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Files\Service\FileStorage;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * The three checks that read the papers put to a customer themselves.
 *
 * Written together because they are one question asked at three points of the same journey, and
 * what is worth testing is that each of them speaks about its own step and passes the others on.
 */
#[CoversClass(UnsentCustomerProposalCheck::class)]
#[CoversClass(UnsignedCustomerProposalCheck::class)]
#[CoversClass(UnfiledCustomerProposalCheck::class)]
class CustomerProposalChecksTest extends TestCase
{
    use TableTestTrait;

    /**
     * The customer the proposals hang on. The fixture contract of theirs is running, so they are
     * somebody the checks look at at all.
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
        'app.Commissions',
        'app.ContractStates',
        'app.ServiceTypes',
        'app.Contracts',
        'app.CustomerProposals',
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

        $this->root = TMP . 'customer-proposal-checks-' . uniqid();
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
     * Drawn up for a day that is nearly here and never sent.
     *
     * @return void
     */
    public function testAProposalNobodyHasSentIsFound(): void
    {
        $proposal = $this->proposal(['effective_from' => Date::now()->addDays(3)]);

        $this->assertContains($proposal, $this->unsent());
        $this->assertNotContains($proposal, $this->unanswered());
        $this->assertNotContains($proposal, $this->unfiled());
    }

    /**
     * A proposal for the spring is somebody's work in hand rather than a fault.
     *
     * @return void
     */
    public function testAProposalWhoseDayIsStillFarOffIsNotAFinding(): void
    {
        $proposal = $this->proposal(['effective_from' => Date::now()->addDays(90)]);

        $this->assertNotContains($proposal, $this->unsent());
    }

    /**
     * Sent a month ago and nothing has come back.
     *
     * @return void
     */
    public function testAProposalNobodyHasAnsweredIsFound(): void
    {
        $proposal = $this->proposal(['sent_date' => Date::now()->subDays(30)]);

        $this->assertContains($proposal, $this->unanswered());
        $this->assertNotContains($proposal, $this->unsent());
    }

    /**
     * Papers posted this week are nobody's fault yet.
     *
     * @return void
     */
    public function testAProposalOnlyJustSentIsNotAFinding(): void
    {
        $proposal = $this->proposal(['sent_date' => Date::now()->subDays(1)]);

        $this->assertNotContains($proposal, $this->unanswered());
    }

    /**
     * Signed a month ago with nothing on the shelf behind it.
     *
     * @return void
     */
    public function testASignatureWithNoPapersBehindItIsFound(): void
    {
        $proposal = $this->proposal([
            'sent_date' => Date::now()->subDays(40),
            'conclusion_date' => Date::now()->subDays(30),
        ]);

        $this->assertContains($proposal, $this->unfiled());
        // Signed is not unanswered, whatever else is missing.
        $this->assertNotContains($proposal, $this->unanswered());
    }

    /**
     * Filing the scan is what ends it, and what we drew up ourselves is not the scan.
     *
     * @return void
     */
    public function testOnlyTheCustomersOwnSignatureTakesItOffTheList(): void
    {
        $proposal = $this->proposal([
            'sent_date' => Date::now()->subDays(40),
            'conclusion_date' => Date::now()->subDays(30),
        ]);

        $this->fileAScan($proposal, DocumentVariant::Generated);
        $this->assertContains($proposal, $this->unfiled());

        $this->fileAScan($proposal, DocumentVariant::ReceivedSignedByCustomer);
        $this->assertNotContains($proposal, $this->unfiled());
    }

    /**
     * A proposal with no purpose of its own only carries the contracts' papers, and their signed
     * copies are filed against the contracts.
     *
     * @return void
     */
    public function testAProposalThatCarriesOnlyContractPapersIsNotAskedForAScan(): void
    {
        $proposal = $this->proposal([
            'purpose' => null,
            'sent_date' => Date::now()->subDays(40),
            'conclusion_date' => Date::now()->subDays(30),
        ]);

        $this->assertNotContains($proposal, $this->unfiled());
    }

    /**
     * A proposal somebody gave up on is nobody's work any more.
     *
     * @return void
     */
    public function testARevokedProposalIsNoneOfTheirBusiness(): void
    {
        $proposal = $this->proposal([
            'effective_from' => Date::now()->addDays(3),
            'revoked' => DateTime::now()->subDays(1),
        ]);

        $this->assertNotContains($proposal, $this->unsent());
        $this->assertNotContains($proposal, $this->unanswered());
        $this->assertNotContains($proposal, $this->unfiled());
    }

    /**
     * A proposal, with what the test wants it to say.
     *
     * @param array<string, mixed> $says What it says.
     * @return string Its id.
     */
    private function proposal(array $says = []): string
    {
        $proposals = $this->getTableLocator()->get('CustomerProposals');

        $proposal = $proposals->newEntity($says + [
            'customer_id' => self::CUSTOMER_ID,
            'purpose' => CustomerProposalPurpose::GdprConsent->value,
            'effective_from' => Date::now()->subDays(1),
        ]);

        return (string)$proposals->saveOrFail($proposal, ['checkRules' => false])->get('id');
    }

    /**
     * Files one page against a proposal.
     *
     * @param string $proposal Which proposal.
     * @param \App\Model\Enum\DocumentVariant $variant Whose signatures it carries.
     * @return void
     */
    private function fileAScan(string $proposal, DocumentVariant $variant): void
    {
        $storage = new FileStorage();

        $storage->link(
            $storage->store('%PDF-1.7 ' . $proposal . $variant->value, 'application/pdf'),
            CustomerDocuments::MODEL,
            $proposal,
            CustomerDocumentType::GdprNew->value,
            $variant->value,
            ['name' => 'scan.pdf'],
        );
    }

    /**
     * @return array<string> The proposals nobody has sent.
     */
    private function unsent(): array
    {
        return $this->found(new UnsentCustomerProposalCheck($this->proposals()));
    }

    /**
     * @return array<string> The proposals that went out and came back to nothing.
     */
    private function unanswered(): array
    {
        return $this->found(new UnsignedCustomerProposalCheck($this->proposals()));
    }

    /**
     * @return array<string> The proposals signed with nothing on the shelf.
     */
    private function unfiled(): array
    {
        return $this->found(new UnfiledCustomerProposalCheck($this->proposals()));
    }

    /**
     * @param \App\Customers\Check\AbstractCustomerProposalCheck $check The check to ask.
     * @return array<string> What it found.
     */
    private function found(AbstractCustomerProposalCheck $check): array
    {
        return $check->find()->all()->extract('id')->toList();
    }

    /**
     * @return \App\Model\Table\CustomerProposalsTable
     */
    private function proposals(): CustomerProposalsTable
    {
        /** @var \App\Model\Table\CustomerProposalsTable $proposals */
        $proposals = $this->getTableLocator()->get(CustomerProposalsTable::class);

        return $proposals;
    }
}
