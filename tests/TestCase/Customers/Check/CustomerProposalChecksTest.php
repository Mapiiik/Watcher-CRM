<?php
declare(strict_types=1);

namespace App\Test\TestCase\Customers\Check;

use App\Customers\Check\AbstractCustomerProposalCheck;
use App\Customers\Check\UnfiledCustomerSignatureCheck;
use App\Customers\Check\UnsentCustomerProposalCheck;
use App\Customers\Check\UnsignedCustomerProposalCheck;
use App\Model\Enum\CustomerPrintType;
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
#[CoversClass(UnfiledCustomerSignatureCheck::class)]
class CustomerProposalChecksTest extends TestCase
{
    use TableTestTrait;

    /**
     * The customer the rounds hang on. The fixture contract of theirs is running, so they are
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
    public function testARoundNobodyHasSentIsFound(): void
    {
        $round = $this->round(['effective_from' => Date::now()->addDays(3)]);

        $this->assertContains($round, $this->unsent());
        $this->assertNotContains($round, $this->unanswered());
        $this->assertNotContains($round, $this->unfiled());
    }

    /**
     * A round for the spring is somebody's work in hand rather than a fault.
     *
     * @return void
     */
    public function testARoundWhoseDayIsStillFarOffIsNotAFinding(): void
    {
        $round = $this->round(['effective_from' => Date::now()->addDays(90)]);

        $this->assertNotContains($round, $this->unsent());
    }

    /**
     * Sent a month ago and nothing has come back.
     *
     * @return void
     */
    public function testARoundNobodyHasAnsweredIsFound(): void
    {
        $round = $this->round(['sent_date' => Date::now()->subDays(30)]);

        $this->assertContains($round, $this->unanswered());
        $this->assertNotContains($round, $this->unsent());
    }

    /**
     * Papers posted this week are nobody's fault yet.
     *
     * @return void
     */
    public function testARoundOnlyJustSentIsNotAFinding(): void
    {
        $round = $this->round(['sent_date' => Date::now()->subDays(1)]);

        $this->assertNotContains($round, $this->unanswered());
    }

    /**
     * Signed a month ago with nothing on the shelf behind it.
     *
     * @return void
     */
    public function testASignatureWithNoPapersBehindItIsFound(): void
    {
        $round = $this->round([
            'sent_date' => Date::now()->subDays(40),
            'conclusion_date' => Date::now()->subDays(30),
        ]);

        $this->assertContains($round, $this->unfiled());
        // Signed is not unanswered, whatever else is missing.
        $this->assertNotContains($round, $this->unanswered());
    }

    /**
     * Filing the scan is what ends it, and what we drew up ourselves is not the scan.
     *
     * @return void
     */
    public function testOnlyTheCustomersOwnSignatureTakesItOffTheList(): void
    {
        $round = $this->round([
            'sent_date' => Date::now()->subDays(40),
            'conclusion_date' => Date::now()->subDays(30),
        ]);

        $this->fileAScan($round, DocumentVariant::Generated);
        $this->assertContains($round, $this->unfiled());

        $this->fileAScan($round, DocumentVariant::ReceivedSignedByCustomer);
        $this->assertNotContains($round, $this->unfiled());
    }

    /**
     * A round somebody gave up on is nobody's work any more.
     *
     * @return void
     */
    public function testARevokedRoundIsNoneOfTheirBusiness(): void
    {
        $round = $this->round([
            'effective_from' => Date::now()->addDays(3),
            'revoked' => DateTime::now()->subDays(1),
        ]);

        $this->assertNotContains($round, $this->unsent());
        $this->assertNotContains($round, $this->unanswered());
        $this->assertNotContains($round, $this->unfiled());
    }

    /**
     * A round of papers, with what the test wants it to say.
     *
     * @param array<string, mixed> $says What it says.
     * @return string Its id.
     */
    private function round(array $says = []): string
    {
        $proposals = $this->getTableLocator()->get('CustomerProposals');

        $round = $proposals->newEntity($says + [
            'customer_id' => self::CUSTOMER_ID,
            'purpose' => CustomerProposalPurpose::GdprConsent->value,
            'effective_from' => Date::now()->subDays(1),
        ]);

        return (string)$proposals->saveOrFail($round, ['checkRules' => false])->get('id');
    }

    /**
     * Files one page against a round.
     *
     * @param string $round Which round.
     * @param \App\Model\Enum\DocumentVariant $variant Whose signatures it carries.
     * @return void
     */
    private function fileAScan(string $round, DocumentVariant $variant): void
    {
        $storage = new FileStorage();

        $storage->link(
            $storage->store('%PDF-1.7 ' . $round . $variant->value, 'application/pdf'),
            CustomerDocuments::MODEL,
            $round,
            CustomerPrintType::GdprNew->value,
            $variant->value,
            ['name' => 'scan.pdf'],
        );
    }

    /**
     * @return array<string> The rounds nobody has sent.
     */
    private function unsent(): array
    {
        return $this->found(new UnsentCustomerProposalCheck($this->proposals()));
    }

    /**
     * @return array<string> The rounds that went out and came back to nothing.
     */
    private function unanswered(): array
    {
        return $this->found(new UnsignedCustomerProposalCheck($this->proposals()));
    }

    /**
     * @return array<string> The rounds signed with nothing on the shelf.
     */
    private function unfiled(): array
    {
        return $this->found(new UnfiledCustomerSignatureCheck($this->proposals()));
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
