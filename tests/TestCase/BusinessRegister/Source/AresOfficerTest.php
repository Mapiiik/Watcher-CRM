<?php
declare(strict_types=1);

namespace App\Test\TestCase\BusinessRegister\Source;

use App\BusinessRegister\Source\AresSource;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * App\BusinessRegister\Source\AresSource officer reading Test Case
 *
 * The records here are shaped the way the register of companies really answers, down to the
 * capitals it writes names in.
 */
#[CoversClass(AresSource::class)]
class AresOfficerTest extends TestCase
{
    /**
     * A record of a company with the given statutory body members.
     *
     * @param list<array<string, mixed>> $members The members, as the register writes them.
     * @return array<string, mixed>
     */
    private function record(array $members): array
    {
        return ['zaznamy' => [['statutarniOrgany' => [['clenoveOrganu' => $members]]]]];
    }

    /**
     * A sitting member comes back with the name in parts, and the register writes it in capitals
     * where a contract wants it written as a name.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::readOfficers()
     */
    public function testASittingMemberIsRead(): void
    {
        $record = $this->record([
            [
                'datumVymazu' => null,
                'fyzickaOsoba' => [
                    'jmeno' => 'PAVEL',
                    'prijmeni' => 'CHALOUPKA',
                    'titulPredJmenem' => 'Ing.',
                    'titulZaJmenem' => 'MBA',
                    'datumNarozeni' => '1970-05-31',
                ],
            ],
        ]);

        $officers = AresSource::readOfficers($record);

        $this->assertCount(1, $officers);
        $this->assertSame(
            [
                'title' => 'Ing.',
                'first_name' => 'Pavel',
                'last_name' => 'Chaloupka',
                'suffix' => 'MBA',
                'date_of_birth' => '1970-05-31',
            ],
            array_diff_key($officers[0], ['key' => null]),
        );
        $this->assertNotSame('', $officers[0]['key']);
    }

    /**
     * Diacritics survive being put back into the case a name is written in.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::readOfficers()
     */
    public function testDiacriticsSurviveTheCasing(): void
    {
        $record = $this->record([
            ['datumVymazu' => null, 'fyzickaOsoba' => ['jmeno' => 'MICHAELA', 'prijmeni' => 'CHALOUPKOVÁ']],
        ]);

        $this->assertSame('Chaloupková', AresSource::readOfficers($record)[0]['last_name']);
    }

    /**
     * A member the register has written out is no longer who the company is represented by.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::readOfficers()
     */
    public function testAMemberWrittenOutIsPassedOver(): void
    {
        $record = $this->record([
            ['datumVymazu' => '2020-11-10', 'fyzickaOsoba' => ['jmeno' => 'JANA', 'prijmeni' => 'JANATOVÁ']],
            ['datumVymazu' => null, 'fyzickaOsoba' => ['jmeno' => 'MARKO', 'prijmeni' => 'JUJNOVIĆ']],
        ]);

        $this->assertSame('Jujnović', AresSource::readOfficers($record)[0]['last_name']);
    }

    /**
     * Everyone still sitting comes back, each under a key of their own - which of them the
     * company is represented by is not the register's to say.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::readOfficers()
     */
    public function testEveryoneStillSittingComesBack(): void
    {
        $record = $this->record([
            ['datumVymazu' => null, 'fyzickaOsoba' => ['jmeno' => 'MARKO', 'prijmeni' => 'JUJNOVIĆ']],
            ['datumVymazu' => null, 'fyzickaOsoba' => ['jmeno' => 'JANA', 'prijmeni' => 'JANATOVÁ']],
        ]);

        $officers = AresSource::readOfficers($record);

        $this->assertSame(['Jujnović', 'Janatová'], array_column($officers, 'last_name'));
        $this->assertCount(2, array_unique(array_column($officers, 'key')));
    }

    /**
     * The same person written down under several records of the company is still one person.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::readOfficers()
     */
    public function testThePersonIsCountedOnceAcrossRecords(): void
    {
        // the entry carries the address too, and it may have been written differently in each -
        // it is the name and date of birth that say who the person is
        $person = ['jmeno' => 'MARKO', 'prijmeni' => 'JUJNOVIĆ', 'datumNarozeni' => '1970-05-31'];
        $record = [
            'zaznamy' => [
                ['statutarniOrgany' => [['clenoveOrganu' => [[
                    'datumVymazu' => null,
                    'fyzickaOsoba' => $person + ['adresa' => ['textovaAdresa' => 'Tam 1, 51243 Tam']],
                ]]]]],
                ['statutarniOrgany' => [['clenoveOrganu' => [[
                    'datumVymazu' => null,
                    'fyzickaOsoba' => $person + ['adresa' => ['textovaAdresa' => 'Jinde 2, 11000 Jinde']],
                ]]]]],
            ],
        ];

        $this->assertSame('Jujnović', AresSource::readOfficers($record)[0]['last_name']);
    }

    /**
     * The trades register holds a sole trader's name in parts, degrees and date of birth and all,
     * so it is read rather than the one line of the basic record taken apart.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::readTrader()
     */
    public function testASoleTraderIsReadFromTheTradesRegister(): void
    {
        $record = ['zaznamy' => [['osobaPodnikatel' => [
            'jmeno' => 'Tomáš',
            'prijmeni' => 'Prokůpek',
            'titulPredJmenem' => 'Ing. arch.',
            'datumNarozeni' => '1975-03-04',
        ]]]];

        $this->assertSame(
            [
                'title' => 'Ing. arch.',
                'first_name' => 'Tomáš',
                'last_name' => 'Prokůpek',
                'suffix' => null,
                'date_of_birth' => '1975-03-04',
            ],
            AresSource::readTrader($record),
        );
    }

    /**
     * With no such record, nothing is said at all - which leaves the reading of the one line the
     * basic record carries standing, rather than wiping it.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::readTrader()
     */
    public function testATraderTheTradesRegisterDoesNotHoldSaysNothing(): void
    {
        $this->assertSame([], AresSource::readTrader(null));
        $this->assertSame([], AresSource::readTrader(['zaznamy' => [[]]]));
    }

    /**
     * A company the register holds nothing about, and one whose body is empty, name nobody.
     *
     * @return void
     * @link \App\BusinessRegister\Source\AresSource::readOfficers()
     */
    public function testNothingToReadNamesNobody(): void
    {
        $this->assertSame([], AresSource::readOfficers(null));
        $this->assertSame([], AresSource::readOfficers($this->record([])));
    }
}
