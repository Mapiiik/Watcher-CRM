<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister;

use App\BusinessRegister\IdentityNumberStatus;
use App\BusinessRegister\Registry;
use App\BusinessRegister\Source\AresSource;
use App\BusinessRegister\Source\ViesSource;
use App\BusinessRegister\VatNumberCheck;
use App\BusinessRegister\VatNumberStatus;
use App\Model\Enum\CustomerLegalStatus;
use App\Test\TestCase\BusinessRegister\Source\BrokenSource;
use App\Test\TestCase\BusinessRegister\Source\StubSource;
use App\Test\Traits\ConfigureTestTrait;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\BusinessRegister\Registry Test Case
 */
#[CoversClass(Registry::class)]
class RegistryTest extends TestCase
{
    use ConfigureTestTrait;

    /**
     * An OIB that holds up.
     */
    private const OIB = '69435151530';

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
     * A register that can answer is offered under the name it is configured by.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::sources()
     */
    public function testConfiguredRegisterIsOffered(): void
    {
        $this->assertArrayHasKey('stub', Registry::sources());
        $this->assertInstanceOf(StubSource::class, Registry::get('stub'));
    }

    /**
     * A register that cannot answer never reaches the form, so nobody is offered a search that
     * would only fail.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::sources()
     */
    public function testUnconfiguredRegisterIsNotOffered(): void
    {
        StubSource::$configured = false;

        $this->assertSame([], Registry::sources());
        $this->assertNull(Registry::get('stub'));
        $this->assertNull(Registry::defaultKey());
    }

    /**
     * A name nobody is configured under is nobody.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::get()
     */
    public function testUnknownRegisterIsNobody(): void
    {
        $this->assertNull(Registry::get('nowhere'));
    }

    /**
     * Anything named in the configuration that is not a register is passed over rather than
     * brought down the form with it.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::sources()
     */
    public function testSomethingThatIsNotARegisterIsPassedOver(): void
    {
        $this->withConfigure(['BusinessRegister.sources' => [
            'stub' => StubSource::class,
            'nonsense' => self::class,
        ]]);

        $this->assertSame(['stub'], array_keys(Registry::sources()));
    }

    /**
     * The form is handed the registers as name to label.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::options()
     */
    public function testRegistersAreOfferedByTheirLabel(): void
    {
        $this->assertSame(['stub' => 'XX - Stub'], Registry::options());
    }

    /**
     * The register asked for is the one offered first.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::defaultKey()
     */
    public function testPreferredRegisterIsOfferedFirst(): void
    {
        $this->assertSame('stub', Registry::defaultKey('stub'));
    }

    /**
     * A register that cannot answer here is not offered first either, so the first one that can
     * stands in for it.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::defaultKey()
     */
    public function testDefaultFallsBackToARegisterThatCanAnswer(): void
    {
        $this->assertSame('stub', Registry::defaultKey('ares'));
        $this->assertSame('stub', Registry::defaultKey(null));
    }

    /**
     * A search goes to the register asked for, and nowhere when it is not one.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::search()
     */
    public function testSearchGoesToTheRegisterAskedFor(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.'],
        ];

        $this->assertSame(1, Registry::search('stub', 'netair')->orFail()->count());
        $this->assertTrue(Registry::search('nowhere', 'netair')->orFail()->isEmpty());
    }

    /**
     * An entry is fetched back by the reference its suggestion carried.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::byReferenceFromCache()
     */
    public function testEntryIsFetchedBackByItsReference(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.'],
        ];

        $subject = Registry::byReferenceFromCache('stub', '27496139')->data;

        $this->assertSame('NETAIR, s.r.o.', $subject?->name);
        $this->assertNull(Registry::byReferenceFromCache('stub', '00000000')->data);
    }

    /**
     * Who the registers say holds the identification number is what the customer is shown next
     * to it - a number that checks out but belongs to somebody else is a mistake the check digit
     * cannot catch.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testWhoHoldsTheIdentityNumberIsLookedUp(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.'],
        ];

        $check = Registry::identityNumberCheck('27496139');

        $this->assertNotNull($check);
        $this->assertSame(IdentityNumberStatus::Found, $check->status);
        $this->assertSame('NETAIR, s.r.o.', $check->company);
    }

    /**
     * Who the holder is in law comes along with the answer, which is what the customer's legal
     * status is measured against.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testTheLegalStatusComesAlongWithTheAnswer(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.', 'legal_status' => 'legal-entity'],
        ];

        $this->assertSame(
            CustomerLegalStatus::LegalEntity,
            Registry::identityNumberCheck('27496139')?->legalStatus,
        );
    }

    /**
     * A register that says nothing of the legal status, or something no status is called, has not
     * said who the holder is.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testALegalStatusTheRegisterDoesNotGiveIsUnknown(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.'],
            ['reference' => '27074358', 'name' => 'Asseco Central Europe, a.s.', 'legal_status' => 'company'],
        ];

        $this->assertNull(Registry::identityNumberCheck('27496139')?->legalStatus);
        $this->assertNull(Registry::identityNumberCheck('27074358')?->legalStatus);
    }

    /**
     * Where the register also says which address the seat is, that comes along with the answer -
     * an address form is then filled in from the address registry rather than from what the
     * business register wrote down.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testTheSeatComesAlongWithTheAnswer(): void
    {
        StubSource::$entries = [
            [
                'reference' => '27496139',
                'name' => 'NETAIR, s.r.o.',
                'addresses' => [['key' => 'cz|16903153', 'label' => 'č.p. 299', 'seat' => true]],
            ],
        ];

        $this->assertSame(
            'cz|16903153',
            Registry::identityNumberCheck('27496139')?->addresses[0]->key,
        );
    }

    /**
     * A register with no such reference to give leaves the seat unnamed, which is what a company
     * registered abroad looks like.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testASeatTheRegisterCannotPointAtIsLeftUnnamed(): void
    {
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.'],
        ];

        $this->assertSame([], Registry::identityNumberCheck('27496139')?->addresses);
    }

    /**
     * A register that could not be reached is not the others' answer - the rest are still asked,
     * so one being down does not silence a register that would have known.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testARegisterThatIsDownDoesNotSilenceTheOthers(): void
    {
        $this->withConfigure(['BusinessRegister.sources' => [
            'broken' => BrokenSource::class,
            'stub' => StubSource::class,
        ]]);
        StubSource::$entries = [
            ['reference' => '27496139', 'name' => 'NETAIR, s.r.o.'],
        ];

        $check = Registry::identityNumberCheck('27496139');

        $this->assertNotNull($check);
        $this->assertSame(IdentityNumberStatus::Found, $check->status);
        $this->assertSame('NETAIR, s.r.o.', $check->company);
    }

    /**
     * A register that is down may be the one holding the number, so the others having nothing is
     * not taken for nobody holding it - ARES turning a run of lookups away while VIES has nothing
     * to say of a bare number would otherwise be read as a wrong number.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testTheOthersHavingNothingIsNoAnswerWhileARegisterIsDown(): void
    {
        $this->withConfigure(['BusinessRegister.sources' => [
            'broken' => BrokenSource::class,
            'stub' => StubSource::class,
        ]]);

        $this->assertNull(Registry::identityNumberCheck('27496139'));
    }

    /**
     * Where no register could be reached at all, nobody has said anything - which is not the same
     * as every one of them saying the number is held by nobody.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testNoRegisterReachedSaysNothingRatherThanNotFound(): void
    {
        $this->withConfigure(['BusinessRegister.sources' => ['broken' => BrokenSource::class]]);

        $this->assertNull(Registry::identityNumberCheck('27496139'));
    }

    /**
     * Every register having been asked and none holding the number is an answer of its own, and
     * a useful one - the check digit cannot notice a number nobody was ever given.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testANumberNoRegisterHoldsIsSaidSoOutLoud(): void
    {
        $check = Registry::identityNumberCheck('27496139');

        $this->assertNotNull($check);
        $this->assertSame(IdentityNumberStatus::NotFound, $check->status);
        $this->assertNull($check->company);
    }

    /**
     * A number no register takes was looked up by nobody, so it is not said to be missing. The
     * customer of a Czech installation with a Croatian number used to get that in red.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberAnswer()
     */
    public function testANumberNoRegisterTakesIsNoVerdict(): void
    {
        StubSource::$takesNothing = true;

        $this->assertNull(Registry::identityNumberCheck('27496139'));
        $this->assertFalse(Registry::identityNumberAnswer('27496139')->asked);
    }

    /**
     * The real registers of one country stay out of the number of the other, without a request.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberAnswer()
     */
    public function testACroatianNumberIsNoVerdictWithoutARegisterForIt(): void
    {
        $this->withConfigure(['BusinessRegister.sources' => [
            'ares' => AresSource::class,
            'vies' => ViesSource::class,
        ]]);
        $this->assertCount(2, Registry::sources());

        $this->assertNull(Registry::identityNumberCheck(self::OIB));
    }

    /**
     * A register that stayed out of it does not take away the verdict of the one that looked.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberAnswer()
     */
    public function testTheRegisterThatTookTheNumberAnswersForIt(): void
    {
        $this->withConfigure(['BusinessRegister.sources' => [
            'vies' => ViesSource::class,
            'stub' => StubSource::class,
        ]]);
        $this->assertCount(2, Registry::sources());

        $this->assertSame(IdentityNumberStatus::NotFound, Registry::identityNumberCheck('27496139')?->status);
    }

    /**
     * The three states come out as what they are, which is what a command has to tell apart.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberAnswer()
     */
    public function testAnOutageIsToldFromNobodyBeingAsked(): void
    {
        StubSource::$unreachableOnReference = true;

        $this->assertTrue(Registry::identityNumberAnswer('27496139')->unanswered());
    }

    /**
     * A customer without a number, and no register to ask, are both answered with nothing - the
     * page then claims neither way.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testAnIdentityNumberNobodyCanSpeakForIsAnsweredWithNothing(): void
    {
        $this->assertNull(Registry::identityNumberCheck(null));
        $this->assertNull(Registry::identityNumberCheck('  '));

        StubSource::$configured = false;
        $this->assertNull(Registry::identityNumberCheck('27496139'));
    }

    /**
     * A register that could not be reached is not an answer either - it is not the same as no
     * register holding the number.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::identityNumberCheck()
     */
    public function testARegisterThatCouldNotBeReachedNamesNobody(): void
    {
        StubSource::$unreachableOnReference = true;

        $this->assertNull(Registry::identityNumberCheck('27496139'));
    }

    /**
     * A register not holding a number is kept as the answer it is, so a customer whose number no
     * register holds is not asked about again and again.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::byReferenceFromCache()
     */
    public function testNotHoldingANumberIsKeptAsAnAnswer(): void
    {
        $this->assertNull(Registry::byReferenceFromCache('stub', '00000000')->data);

        // the register is now down, but it is not asked again
        StubSource::$unreachableOnReference = true;
        $this->assertNull(Registry::byReferenceFromCache('stub', '00000000')->data);
    }

    /**
     * A company found in a search has just been held, whatever was kept about it before. Reading
     * the old answer would refuse a company that was registered after somebody looked it up.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::byReferenceFromCache()
     */
    public function testACompanyRegisteredSinceIsSeenWhenAskedAgain(): void
    {
        $this->assertNull($this->heldUnder('27496139'));

        StubSource::$entries = [['reference' => '27496139', 'name' => 'NETAIR, s.r.o.']];
        $this->assertNull($this->heldUnder('27496139'), 'The answer is kept.');

        $this->assertSame('NETAIR, s.r.o.', $this->heldUnder('27496139', askAgainIfNotHeld: true));

        StubSource::$entries = [];
        $this->assertSame('NETAIR, s.r.o.', $this->heldUnder('27496139'), 'What was found is kept in its place.');
    }

    /**
     * Who the stub register is said to hold under a reference, the cache having its say.
     *
     * @param string $reference The reference.
     * @param bool $askAgainIfNotHeld Whether having held nothing is asked about again.
     * @return string|null
     * @phpstan-impure
     */
    private function heldUnder(string $reference, bool $askAgainIfNotHeld = false): ?string
    {
        /** @var \App\BusinessRegister\Dto\Subject|null $subject */
        $subject = Registry::byReferenceFromCache('stub', $reference, $askAgainIfNotHeld)->data;

        return $subject?->name;
    }

    /**
     * A register that checks VAT numbers is asked, and its answer is what the customer is shown -
     * including the middle answer, that the number is held by someone who does not pay VAT.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::vatNumberCheck()
     */
    public function testVatNumberIsCheckedAgainstARegisterThatCanSay(): void
    {
        StubSource::$vatNumberCheck = new VatNumberCheck(VatNumberStatus::Registered, 'NETAIR, s.r.o.');
        $registered = Registry::vatNumberCheck('CZ27496139');
        $this->assertNotNull($registered);
        $this->assertSame(VatNumberStatus::Registered, $registered->status);

        Cache::clear('business_register');
        StubSource::$vatNumberCheck = new VatNumberCheck(VatNumberStatus::NotRegistered, 'NETAIR, s.r.o.');
        $notRegistered = Registry::vatNumberCheck('CZ27496139');
        $this->assertNotNull($notRegistered);
        $this->assertSame(VatNumberStatus::NotRegistered, $notRegistered->status);

        StubSource::$vatNumberCheck = new VatNumberCheck(VatNumberStatus::Invalid);
        $invalid = Registry::vatNumberCheck('CZ00000000');
        $this->assertNotNull($invalid);
        $this->assertSame(VatNumberStatus::Invalid, $invalid->status);
    }

    /**
     * Who the register says holds the number comes along with the answer - a number that checks
     * out but belongs to somebody else is a mistake the status alone would not show.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::vatNumberCheck()
     */
    public function testWhoHoldsTheNumberComesAlongWithTheAnswer(): void
    {
        StubSource::$vatNumberCheck = new VatNumberCheck(VatNumberStatus::Registered, 'NETAIR, s.r.o.');

        $check = Registry::vatNumberCheck('CZ27496139');

        $this->assertNotNull($check);
        $this->assertSame('NETAIR, s.r.o.', $check->company);
    }

    /**
     * A customer without a VAT number, and a register that cannot be asked about one, are both
     * answered with nothing - the page then claims neither way.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::vatNumberCheck()
     */
    public function testVatNumberNobodyCanSpeakForIsAnsweredWithNothing(): void
    {
        $this->assertNull(Registry::vatNumberCheck(null));
        $this->assertNull(Registry::vatNumberCheck('  '));

        // the register is there but says the number is not one it can be asked about
        StubSource::$vatNumberCheck = null;
        $this->assertNull(Registry::vatNumberCheck('27496139'));

        // no register that checks VAT numbers at all
        StubSource::$configured = false;
        $this->assertNull(Registry::vatNumberCheck('CZ27496139'));
    }

    /**
     * A register that could not be reached is not an answer, and is not remembered as one either
     * - an outage lasts as long as the outage rather than as long as the cache.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::vatNumberCheck()
     */
    public function testAnOutageIsNotAnAnswer(): void
    {
        StubSource::$unreachable = true;

        $this->assertNull(Registry::vatNumberCheck('CZ27496139'));
    }

    /**
     * A register that could not be reached is not remembered as an answer either - an outage
     * lasts as long as the outage rather than as long as the cache.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::vatNumberCheck()
     */
    public function testAnOutageIsNotRememberedAsAnAnswer(): void
    {
        // asked while down, so anything left behind would be the outage
        StubSource::$unreachable = true;
        Registry::vatNumberCheck('CZ27496139');

        StubSource::$unreachable = false;
        StubSource::$vatNumberCheck = new VatNumberCheck(VatNumberStatus::Registered);
        $afterwards = Registry::vatNumberCheck('CZ27496139');

        $this->assertNotNull($afterwards);
        $this->assertSame(VatNumberStatus::Registered, $afterwards->status);
    }

    /**
     * An answer is kept, so opening the same customer again does not ask a second time.
     *
     * @return void
     * @link \App\BusinessRegister\Registry::vatNumberCheck()
     */
    public function testAnAnswerIsKept(): void
    {
        StubSource::$vatNumberCheck = new VatNumberCheck(VatNumberStatus::Registered, 'NETAIR, s.r.o.');
        Registry::vatNumberCheck('CZ27496139');

        // the register would now say otherwise, but it is not asked again
        StubSource::$unreachable = true;
        $kept = Registry::vatNumberCheck('CZ27496139');

        $this->assertNotNull($kept);
        $this->assertSame(VatNumberStatus::Registered, $kept->status);
        $this->assertSame('NETAIR, s.r.o.', $kept->company);
    }
}
