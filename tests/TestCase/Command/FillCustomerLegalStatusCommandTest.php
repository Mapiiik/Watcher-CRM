<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use App\Command\FillCustomerLegalStatusCommand;
use App\Model\Enum\CustomerLegalStatus;
use App\Test\TestCase\BusinessRegister\Source\StubSource;
use App\Test\Traits\ConfigureTestTrait;
use Cake\Cache\Cache;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * App\Command\FillCustomerLegalStatusCommand Test Case
 *
 * The registers are stood in for, so what is asked here is what the command makes of their
 * answers: what it fills in, what it corrects, and what it leaves alone.
 */
#[UsesClass(FillCustomerLegalStatusCommand::class)]
class FillCustomerLegalStatusCommandTest extends TestCase
{
    use ConfigureTestTrait;
    use ConsoleIntegrationTestTrait;

    /**
     * The fixture customer with a company and an identification number.
     *
     * @var string
     */
    private const COMPANY_ID = '403bab0e-52cd-4a8e-83f8-43c2457d0481';

    /**
     * The fixture customer with neither an identification number nor a legal status.
     *
     * @var string
     */
    private const OTHER_ID = 'ae128a49-82fd-4b80-921f-f11af75fd113';

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.AccountingProfiles',
        'app.Customers',
        'app.Countries',
        'app.Addresses',
        'app.Emails',
        'app.Phones',
        'app.FulltextSearchCustomers',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        StubSource::reset();
        Cache::clear('business_register');

        $this->withConfigure(['BusinessRegister.sources' => ['stub' => StubSource::class]]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    #[Override]
    public function tearDown(): void
    {
        $this->restoreConfigure();
        Cache::clear('business_register');
        StubSource::reset();

        parent::tearDown();
    }

    /**
     * The command says what it is for, which is what somebody running it by hand reads.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::buildOptionParser()
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('customers fill_legal_status --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('business registers');
        $this->assertOutputContains('--recheck');
        $this->assertOutputContains('--dry-run');
    }

    /**
     * A customer with neither an identification number nor a company is a household.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testACustomerWithNeitherANumberNorACompanyIsANaturalPerson(): void
    {
        $this->givenCustomer(self::OTHER_ID, company: null);

        $this->exec('customers fill_legal_status');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::NaturalPerson, $this->legalStatusOf(self::OTHER_ID));
    }

    /**
     * A company without an identification number could be anybody, so it is left to be filled in
     * by hand and said so.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testACompanyWithoutANumberIsLeftEmptyAndListed(): void
    {
        $this->exec('customers fill_legal_status');

        $this->assertExitSuccess();
        $this->assertNull($this->legalStatusOf(self::OTHER_ID));
        $this->assertOutputContains('A company without an identification number.');
    }

    /**
     * A customer with an identification number is who the register says they are.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testTheRegisterFillsTheLegalStatusIn(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.', 'legal_status' => 'sole-trader'],
        ];
        $this->givenCustomer(self::COMPANY_ID, legalStatus: null);

        $this->exec('customers fill_legal_status');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::SoleTrader, $this->legalStatusOf(self::COMPANY_ID));
        $this->assertOutputContains('Sole Trader');
    }

    /**
     * A number no register holds is left empty and listed, rather than guessed at.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testANumberNotInTheRegisterIsLeftEmptyAndListed(): void
    {
        $this->givenCustomer(self::COMPANY_ID, legalStatus: null);

        $this->exec('customers fill_legal_status');

        $this->assertExitSuccess();
        $this->assertNull($this->legalStatusOf(self::COMPANY_ID));
        $this->assertOutputContains('Not in the business register.');
    }

    /**
     * A register that does not say who the subject is leaves nothing to go on.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testARegisterThatDoesNotSayLeavesItEmpty(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.'],
        ];
        $this->givenCustomer(self::COMPANY_ID, legalStatus: null);

        $this->exec('customers fill_legal_status');

        $this->assertExitSuccess();
        $this->assertNull($this->legalStatusOf(self::COMPANY_ID));
        $this->assertOutputContains('The business register does not say.');
    }

    /**
     * A register that turned the lookup away is asked again, and what it then says is filled in.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testALookupTurnedAwayIsAskedAgain(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.', 'legal_status' => 'sole-trader'],
        ];
        StubSource::$turnsAway = 1;
        $this->givenCustomer(self::COMPANY_ID, legalStatus: null);

        $this->exec('customers fill_legal_status --wait 0');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::SoleTrader, $this->legalStatusOf(self::COMPANY_ID));
        $this->assertOutputContains('asking again');
    }

    /**
     * A register that cannot be reached at all leaves the customer empty, said to be worth another
     * run - not taken for a number nobody holds.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testARegisterThatCannotBeReachedLeavesItForAnotherRun(): void
    {
        StubSource::$unreachableOnReference = true;
        $this->givenCustomer(self::COMPANY_ID, legalStatus: null);

        $this->exec('customers fill_legal_status --wait 0');

        $this->assertExitSuccess();
        $this->assertNull($this->legalStatusOf(self::COMPANY_ID));
        $this->assertOutputContains('No business register could be asked, try again later.');
        $this->assertOutputNotContains('Not in the business register.');
    }

    /**
     * Without being told to check them all, a legal status somebody filled in is not asked about.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testAFilledInStatusIsLeftAloneWithoutARecheck(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.', 'legal_status' => 'sole-trader'],
        ];

        $this->exec('customers fill_legal_status');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::LegalEntity, $this->legalStatusOf(self::COMPANY_ID));
    }

    /**
     * Told to check them all, the command corrects a legal status the register disagrees with,
     * and says what it was.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testARecheckCorrectsWhatTheRegisterDisagreesWith(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.', 'legal_status' => 'sole-trader'],
        ];

        $this->exec('customers fill_legal_status --recheck');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::SoleTrader, $this->legalStatusOf(self::COMPANY_ID));
        $this->assertOutputContains('Legal Entity');
        $this->assertOutputContains('Sole Trader');
    }

    /**
     * A recheck the register cannot answer keeps what is there.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testARecheckKeepsWhatTheRegisterCannotSay(): void
    {
        $this->exec('customers fill_legal_status --recheck');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::LegalEntity, $this->legalStatusOf(self::COMPANY_ID));
    }

    /**
     * A legal status filled in for a customer with no identification number stays: a sole trader
     * may have a number nobody wrote down, and nothing says otherwise.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testARecheckKeepsAStatusThereIsNoNumberToCheck(): void
    {
        $this->givenCustomer(self::OTHER_ID, company: null, legalStatus: CustomerLegalStatus::SoleTrader);

        $this->exec('customers fill_legal_status --recheck');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::SoleTrader, $this->legalStatusOf(self::OTHER_ID));
    }

    /**
     * What the register cannot say is guessed by the identification number, and a guess that takes
     * the customer for a business is listed for checking.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testWhatTheRegisterCannotSayIsGuessed(): void
    {
        $this->givenCustomer(self::COMPANY_ID, legalStatus: null);

        $this->exec('customers fill_legal_status --guess identity-number');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::LegalEntity, $this->legalStatusOf(self::COMPANY_ID));
        $this->assertOutputContains('Guessed to be in business, worth checking:');
        $this->assertOutputContains('Not in the business register.');
    }

    /**
     * Where the register does say, it is the register that counts, not the guess.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testARegisterAnswerBeatsTheGuess(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.', 'legal_status' => 'sole-trader'],
        ];
        $this->givenCustomer(self::COMPANY_ID, legalStatus: null);

        $this->exec('customers fill_legal_status --guess identity-number');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::SoleTrader, $this->legalStatusOf(self::COMPANY_ID));
        $this->assertOutputNotContains('Guessed to be in business');
    }

    /**
     * Without the registers, the company name decides and no register is asked - the stub one would
     * have said the number is a sole trader's.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testWithoutTheRegistersTheCompanyDecides(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.', 'legal_status' => 'sole-trader'],
        ];
        $this->givenCustomer(self::COMPANY_ID, legalStatus: null);

        $this->exec('customers fill_legal_status --guess company --no-registers');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::LegalEntity, $this->legalStatusOf(self::COMPANY_ID));
        // the other one has a company and no number, which the company name alone decides too
        $this->assertSame(CustomerLegalStatus::LegalEntity, $this->legalStatusOf(self::OTHER_ID));
    }

    /**
     * A guess fills in only what nobody has, so a sole trader somebody corrected by hand stays one,
     * a recheck included.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testAGuessNeverOverwrites(): void
    {
        $this->givenCustomer(self::COMPANY_ID, legalStatus: CustomerLegalStatus::SoleTrader);

        $this->exec('customers fill_legal_status --guess identity-number --recheck');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::SoleTrader, $this->legalStatusOf(self::COMPANY_ID));
    }

    /**
     * A register that cannot be reached may yet answer, so nothing is guessed over it.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testNothingIsGuessedOverARegisterThatCannotBeReached(): void
    {
        StubSource::$unreachableOnReference = true;
        $this->givenCustomer(self::COMPANY_ID, legalStatus: null);

        $this->exec('customers fill_legal_status --guess identity-number --wait 0');

        $this->assertExitSuccess();
        $this->assertNull($this->legalStatusOf(self::COMPANY_ID));
    }

    /**
     * Without the registers there is nothing to go on but a guess, and nothing to recheck by.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testWithoutTheRegistersAGuessIsNeeded(): void
    {
        $this->exec('customers fill_legal_status --no-registers');
        $this->assertExitError();

        $this->exec('customers fill_legal_status --guess company --no-registers --recheck');
        $this->assertExitError();
    }

    /**
     * A dry run says what it would change and writes none of it.
     *
     * @return void
     * @link \App\Command\FillCustomerLegalStatusCommand::execute()
     */
    public function testADryRunWritesNothing(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.', 'legal_status' => 'sole-trader'],
        ];
        $this->givenCustomer(self::OTHER_ID, company: null);

        $this->exec('customers fill_legal_status --recheck --dry-run');

        $this->assertExitSuccess();
        $this->assertSame(CustomerLegalStatus::LegalEntity, $this->legalStatusOf(self::COMPANY_ID));
        $this->assertNull($this->legalStatusOf(self::OTHER_ID));
        $this->assertOutputContains('Sole Trader');
        $this->assertOutputContains('Natural Person');
        $this->assertErrorContains('dry run');
    }

    /**
     * Gives a fixture customer a company and a legal status.
     *
     * @param string $id The customer.
     * @param string|null $company The company to give them.
     * @param \App\Model\Enum\CustomerLegalStatus|null $legalStatus The legal status to give them.
     * @return void
     */
    private function givenCustomer(
        string $id,
        ?string $company = 'NETAIR, s.r.o.',
        ?CustomerLegalStatus $legalStatus = null,
    ): void {
        $customers = $this->getTableLocator()->get('Customers');
        /** @var \App\Model\Entity\Customer $customer */
        $customer = $customers->get($id);
        $customer->company = $company;
        $customer->legal_status = $legalStatus;
        $customers->saveOrFail($customer);
    }

    /**
     * The legal status a customer has in the database.
     *
     * @param string $id The customer.
     * @return \App\Model\Enum\CustomerLegalStatus|null
     */
    private function legalStatusOf(string $id): ?CustomerLegalStatus
    {
        /** @var \App\Model\Entity\Customer $customer */
        $customer = $this->getTableLocator()->get('Customers')->get($id);

        return $customer->legal_status;
    }
}
