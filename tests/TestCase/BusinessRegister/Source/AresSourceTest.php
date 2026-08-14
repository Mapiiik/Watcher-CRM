<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister\Source;

use App\BusinessRegister\Source\AresSource;
use App\BusinessRegister\VatNumberStatus;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\BusinessRegister\AresSource Test Case
 *
 * Only the reading of an answer is checked here. Asking ARES is left alone: a test of that
 * would only be as reliable as the register itself.
 */
#[CoversClass(AresSource::class)]
class AresSourceTest extends TestCase
{
    /**
     * A subject as ARES returns it, cut down to the fields that are read.
     *
     * @return array<string, mixed>
     */
    private function subject(): array
    {
        return [
            'ico' => '27074358',
            'obchodniJmeno' => 'Asseco Central Europe, a.s.',
            'dic' => 'CZ27074358',
            'sidlo' => [
                'kodStatu' => 'CZ',
                'nazevObce' => 'Praha',
                'psc' => 14000,
                'kodAdresnihoMista' => 41405609,
                'standardizaceAdresy' => true,
                'textovaAdresa' => 'Budějovická 778/3a, Michle, 14000 Praha 4',
            ],
            'pravniForma' => '121',
        ];
    }

    /**
     * A subject comes out in the shape every register answers in.
     *
     * @return void
     * @link \App\BusinessRegister\AresSource::mapSubject()
     */
    public function testSubjectIsReadIntoTheSharedShape(): void
    {
        $this->assertSame(
            [
                'reference' => '27074358',
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
                'address' => 'Budějovická 778/3a, Michle, 14000 Praha 4',
                'addresses' => [[
                    'key' => 'cz|41405609',
                    'label' => 'Budějovická 778/3a, Michle, 14000 Praha 4',
                    'seat' => true,
                ]],
            ],
            AresSource::mapSubject($this->subject()),
        );
    }

    /**
     * The seat comes with the RÚIAN code the national address registry answers to, so an address
     * form is filled in from the registry rather than from what the business register wrote down.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::mapSubject()
     */
    public function testTheSeatCarriesTheAddressRegistryReference(): void
    {
        $this->assertSame('cz|41405609', AresSource::mapSubject($this->subject())['addresses'][0]['key']);
    }

    /**
     * A seat ARES did not standardise has no such code - that is what a company registered
     * abroad looks like, and there is nothing for the address registry to be asked.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::mapSubject()
     */
    public function testASeatAresDidNotStandardiseHasNoAddressRegistryReference(): void
    {
        $abroad = $this->subject();
        $abroad['sidlo']['standardizaceAdresy'] = false;
        unset($abroad['sidlo']['kodAdresnihoMista']);

        $this->assertSame([], AresSource::mapSubject($abroad)['addresses']);
    }

    /**
     * ARES carries foreign companies without an identification number, and the number is what an
     * entry would be fetched back by - so such an entry has no reference to offer.
     *
     * @return void
     * @link \App\BusinessRegister\AresSource::mapSubject()
     */
    public function testSubjectWithoutAnIdentityNumberHasNoReference(): void
    {
        $subject = $this->subject();
        unset($subject['ico']);

        $mapped = AresSource::mapSubject($subject);

        $this->assertNull($mapped['reference']);
        $this->assertNull($mapped['identity_number']);
        $this->assertSame('Asseco Central Europe, a.s.', $mapped['company']);
    }

    /**
     * A company that is not registered for VAT has no VAT number, which is not the same as an
     * empty one.
     *
     * @return void
     * @link \App\BusinessRegister\AresSource::mapSubject()
     */
    public function testSubjectWithoutAVatNumberSaysSo(): void
    {
        $subject = $this->subject();
        unset($subject['dic']);

        $this->assertNull(AresSource::mapSubject($subject)['vat_number']);
    }

    /**
     * ARES says whether the VAT registration is in force, which is what tells a VAT payer from a
     * company that holds a tax number without ever having registered.
     *
     * @return void
     * @link \App\BusinessRegister\AresSource::readVatNumberStatus()
     */
    public function testVatRegistrationIsReadFromTheRegistrationList(): void
    {
        $payer = $this->subject();
        $payer['seznamRegistraci'] = ['stavZdrojeDph' => 'AKTIVNI'];
        $this->assertSame(VatNumberStatus::Registered, AresSource::readVatNumberCheck($payer)->status);

        $nonPayer = $this->subject();
        $nonPayer['seznamRegistraci'] = ['stavZdrojeDph' => 'NEEXISTUJICI'];
        $this->assertSame(VatNumberStatus::NotRegistered, AresSource::readVatNumberCheck($nonPayer)->status);
    }

    /**
     * The name comes along with the status, because a number that checks out but belongs to
     * somebody else is a mistake the status alone would not show.
     *
     * @return void
     * @link \App\BusinessRegister\AresSource::readVatNumberCheck()
     */
    public function testWhoHoldsTheNumberComesAlongWithIt(): void
    {
        $this->assertSame(
            'Asseco Central Europe, a.s.',
            AresSource::readVatNumberCheck($this->subject())->company,
        );
    }

    /**
     * A company ARES does not hold at all has no number to speak of, and nobody to name.
     *
     * @return void
     * @link \App\BusinessRegister\AresSource::readVatNumberCheck()
     */
    public function testACompanyAresDoesNotHoldIsInvalid(): void
    {
        $check = AresSource::readVatNumberCheck(null);

        $this->assertSame(VatNumberStatus::Invalid, $check->status);
        $this->assertNull($check->company);
    }

    /**
     * A subject whose registrations ARES does not list is not a VAT payer as far as it says.
     *
     * @return void
     * @link \App\BusinessRegister\AresSource::readVatNumberCheck()
     */
    public function testASubjectWithoutARegistrationListIsNotAPayer(): void
    {
        $this->assertSame(VatNumberStatus::NotRegistered, AresSource::readVatNumberCheck($this->subject())->status);
    }

    /**
     * A subject whose seat ARES does not hold is still a subject.
     *
     * @return void
     * @link \App\BusinessRegister\AresSource::mapSubject()
     */
    public function testSubjectWithoutASeatIsStillRead(): void
    {
        $subject = $this->subject();
        unset($subject['sidlo']);

        $mapped = AresSource::mapSubject($subject);

        $this->assertNull($mapped['address']);
        $this->assertSame('27074358', $mapped['reference']);
    }
}
