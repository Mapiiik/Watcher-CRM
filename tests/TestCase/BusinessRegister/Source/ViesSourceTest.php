<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister\Source;

use App\BusinessRegister\Source\ViesSource;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * App\BusinessRegister\ViesSource Test Case
 *
 * Only the reading of an answer is checked here. Asking VIES is left alone: a test of that
 * would only be as reliable as the service itself.
 */
#[CoversClass(ViesSource::class)]
class ViesSourceTest extends TestCase
{
    /**
     * Two words are a verdict, and they are read as one.
     *
     * @return void
     * @link \App\BusinessRegister\Source\ViesSource::confirmed()
     */
    public function testAVerdictIsReadAsOne(): void
    {
        $this->assertTrue(ViesSource::confirmed(['isValid' => true, 'userError' => 'VALID']));
        $this->assertFalse(ViesSource::confirmed(['isValid' => false, 'userError' => 'INVALID']));
    }

    /**
     * A member state that is down, or too busy, or too slow, is not a number nobody holds - VIES
     * says so in the same breath as a verdict, with `isValid` false either way, and that is the
     * pair that once put a good VAT number in the cache as an invalid one.
     *
     * @param string $userError What VIES said instead of a verdict.
     * @return void
     * @link \App\BusinessRegister\Source\ViesSource::confirmed()
     */
    #[DataProvider('troubles')]
    public function testTroubleIsNotAVerdict(string $userError): void
    {
        $this->assertNull(ViesSource::confirmed(['isValid' => false, 'userError' => $userError]));
    }

    /**
     * Everything VIES answers with where it never got as far as checking.
     *
     * @return array<string, array{0: string}>
     */
    public static function troubles(): array
    {
        return [
            'a member state that is down' => ['MS_UNAVAILABLE'],
            'a member state that is busy' => ['MS_MAX_CONCURRENT_REQ'],
            'a service that is busy' => ['GLOBAL_MAX_CONCURRENT_REQ'],
            'a service that is down' => ['SERVICE_UNAVAILABLE'],
            'a state that took too long' => ['TIMEOUT'],
            'a number it could not read' => ['INVALID_INPUT'],
            // read as a whitelist, so a word added later lands here rather than among the verdicts
            'a word nobody has seen yet' => ['SOMETHING_NEW'],
            'nothing at all' => [''],
        ];
    }

    /**
     * A confirmed number comes out in the shape every register answers in, with the address on
     * the one line a suggestion has room for.
     *
     * @return void
     * @link \App\BusinessRegister\ViesSource::mapSubject()
     */
    public function testConfirmedNumberIsReadIntoTheSharedShape(): void
    {
        $subject = [
            'isValid' => true,
            'name' => 'Asseco Central Europe, a.s.',
            'address' => "Budějovická 778/3a\nPRAHA 4 - MICHLE\n140 00  PRAHA 4",
        ];

        $this->assertSame(
            [
                'reference' => 'CZ27074358',
                'name' => 'Asseco Central Europe, a.s.',
                'company' => 'Asseco Central Europe, a.s.',
                'title' => null,
                'first_name' => null,
                'last_name' => null,
                'suffix' => null,
                'date_of_birth' => null,
                'officers' => [],
                'identity_number' => '27074358',
                'vat_number' => 'CZ27074358',
                'legal_status' => null,
                'address' => 'Budějovická 778/3a, PRAHA 4 - MICHLE, 140 00  PRAHA 4',
                'addresses' => [],
            ],
            ViesSource::mapSubject($subject, 'CZ', '27074358'),
        );
    }

    /**
     * A number VIES does not confirm is not an entry.
     *
     * @return void
     * @link \App\BusinessRegister\ViesSource::mapSubject()
     */
    public function testUnconfirmedNumberIsNotAnEntry(): void
    {
        $subject = ['isValid' => false, 'name' => '---', 'address' => '---'];

        $this->assertNull(ViesSource::mapSubject($subject, 'HR', '00000000000'));
    }

    /**
     * VIES writes a row of dashes where it holds nothing, which is nothing and not a name.
     *
     * @return void
     * @link \App\BusinessRegister\ViesSource::mapSubject()
     */
    public function testPlaceholdersAreReadAsNothing(): void
    {
        $subject = ['isValid' => true, 'name' => '---', 'address' => '---'];

        $mapped = ViesSource::mapSubject($subject, 'CZ', '27074358');

        $this->assertIsArray($mapped);
        $this->assertSame('', $mapped['company']);
        $this->assertNull($mapped['address']);
    }

    /**
     * The number without its prefix is only taken as an identification number where it holds up
     * as one - a Czech VAT number may be a personal one that is not an IČO at all.
     *
     * @return void
     * @link \App\BusinessRegister\ViesSource::mapSubject()
     */
    public function testNumberIsOnlyTakenAsAnIdentityNumberWhereItHoldsUp(): void
    {
        $subject = ['isValid' => true, 'name' => 'Someone', 'address' => 'Somewhere'];

        $personal = ViesSource::mapSubject($subject, 'CZ', '6012310123');
        $this->assertIsArray($personal);
        $this->assertNull($personal['identity_number']);

        $croatian = ViesSource::mapSubject($subject, 'HR', '80625159724');
        $this->assertIsArray($croatian);
        $this->assertSame('80625159724', $croatian['identity_number']);

        // eight digits that pass as a Czech number and belong to a company in Slovenia
        $slovene = ViesSource::mapSubject($subject, 'SI', '27496139');
        $this->assertIsArray($slovene);
        $this->assertNull($slovene['identity_number']);
    }

    /**
     * A name reads like a number, a country and the rest, but no such country is in the register.
     *
     * @return void
     * @link \App\BusinessRegister\Source\ViesSource::ask()
     */
    public function testANameIsNotAskedAbout(): void
    {
        $answer = (new ViesSource())->search('Netair');

        $this->assertTrue($answer->ok());
        $this->assertCount(0, $answer->data);
    }

    /**
     * An identification number without a member state is not a VAT number, so VIES was not asked
     * and holds nothing about it either way.
     *
     * @return void
     * @link \App\BusinessRegister\Source\ViesSource::byReference()
     */
    public function testAnIdentificationNumberIsNotAskedAbout(): void
    {
        $this->assertFalse((new ViesSource())->byReference('27496139')->asked);
    }
}
