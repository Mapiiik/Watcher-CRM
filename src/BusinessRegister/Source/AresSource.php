<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use App\BusinessRegister\Dto\Subject;
use App\BusinessRegister\IdentityNumber;
use App\BusinessRegister\VatNumberCheck;
use App\BusinessRegister\VatNumberStatus;
use App\Http\Answer;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Client\Response;
use Override;

/**
 * The Czech business register (ARES).
 *
 * It is public and needs no credentials, and it answers to a name as readily as to a number.
 * Entries without an IČO - foreign companies it carries for reference - are left out, because
 * the IČO is what an entry is fetched back by.
 */
class AresSource extends BaseSource implements VatNumberCheckInterface
{
    /**
     * What ARES calls a registration that is in force.
     *
     * @var string
     */
    private const ACTIVE = 'AKTIVNI';

    /**
     * @inheritDoc
     */
    #[Override]
    public function key(): string
    {
        return 'ares';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function label(): string
    {
        return __('CZ - ARES');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function search(string $query, int $limit = 25): Answer
    {
        $query = trim($query);
        if ($query === '') {
            return Answer::of(new Collection([]));
        }

        // A number is the entry itself, and asking for it by name would only find it by accident.
        $number = self::withoutWhitespace($query);
        if (IdentityNumber::isValidCzech($number)) {
            return $this->byReference($number)
                ->map(fn(?Subject $subject): array => $subject === null ? [] : [$subject])
                ->map(fn(array $subjects): CollectionInterface => new Collection($subjects));
        }

        return $this->read(fn(): Response => $this->http()->post(
            $this->endpoint('ekonomicke-subjekty/vyhledat'),
            ['obchodniJmeno' => $query, 'pocet' => $limit, 'start' => 0],
            ['type' => 'json'],
        ))->map(function (array $data): CollectionInterface {
            $subjects = $data['ekonomickeSubjekty'] ?? [];
            $mapped = [];

            foreach (is_array($subjects) ? $subjects : [] as $subject) {
                if (is_array($subject)) {
                    $mapped[] = self::mapSubject($subject);
                }
            }

            return self::toSubjects($mapped);
        });
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function byReference(string $reference): Answer
    {
        $basic = $this->fetch($reference);
        if (!$basic->ok() || $basic->data === null) {
            // a failure and a register that holds nothing both travel out as they came
            return $basic;
        }

        /** @var array<int|string, mixed> $subject */
        $subject = $basic->data;

        $mapped = self::mapSubject($subject);
        if ($mapped['reference'] === null) {
            return Answer::of(null);
        }

        // Where a subject does business besides its seat, who sits in its statutory body, and how
        // a sole trader's name comes apart, are all held by registers of their own that the basic
        // record does not carry. Each is worth a further request, but only for an entry that was
        // actually picked - a search asks for none of it, and would otherwise fire a handful of
        // requests per line of the suggestion list.
        //
        // The register of persons holds the establishments of anyone at all, so it is asked
        // whoever the subject is.
        $person = $this->fetchPersonRecord($mapped['reference']);
        if (!$person->ok()) {
            return $person;
        }

        /** @var array<int|string, mixed>|null $personRecord */
        $personRecord = $person->data;

        // an establishment may well be at the seat, and the seat is named as such
        foreach (self::readEstablishments($personRecord) as $establishment) {
            if (!in_array($establishment['key'], array_column($mapped['addresses'], 'key'), true)) {
                $mapped['addresses'][] = $establishment;
            }
        }

        if ($mapped['company'] === null) {
            $trades = $this->fetchTradesRecord($mapped['reference']);

            return $trades->ok()
                ? Answer::of(self::toSubject(self::readTrader($trades->data) + $mapped))
                : $trades;
        }

        // A town, a school, a public body - a legal entity all the same, but not one the register
        // of companies holds. The basic record says whether it is worth asking, so it is not asked
        // in vain, and the register of persons has already answered for anyone it has never heard
        // of.
        if (self::isInCompanyRegister($subject)) {
            $companyRecord = $this->fetchRegisterRecord($mapped['reference']);
            if (!$companyRecord->ok()) {
                return $companyRecord;
            }

            $mapped['officers'] = self::readOfficers($companyRecord->data);
        } else {
            $mapped['officers'] = self::readPersonRegisterOfficers($personRecord);
        }

        // One member sitting is who the company is represented by, with nothing to choose. Where
        // several sit, none is filled in: the name goes onto a contract as who the company was
        // represented by, and picking one of them is the operator's to do rather than ours.
        if (count($mapped['officers']) === 1) {
            $mapped = array_diff_key($mapped['officers'][0], ['key' => null]) + $mapped;
        }

        return Answer::of(self::toSubject($mapped));
    }

    /**
     * ARES holds both whether the company exists and whether it pays VAT, so it can tell a
     * company that never registered from a number nobody holds. A Czech VAT number is the
     * identification number with a prefix, and anything else is not ARES's to answer.
     *
     * @inheritDoc
     */
    #[Override]
    public function vatNumberCheck(string $vatNumber): Answer
    {
        $vatNumber = strtoupper(self::withoutWhitespace($vatNumber));
        if (!preg_match('/^CZ(\d{8})$/', $vatNumber, $matches)) {
            // not a number ARES is asked about, which is an answer of nothing rather than a
            // failure - the next register may know it
            return Answer::of(null);
        }

        [, $identityNumber] = $matches;
        if (!IdentityNumber::isValidCzech($identityNumber)) {
            return Answer::of(new VatNumberCheck(VatNumberStatus::Invalid));
        }

        return $this->fetch($identityNumber)->map(self::readVatNumberCheck(...));
    }

    /**
     * Read what ARES says about a subject's VAT registration, and who it says holds it.
     *
     * @param array<int|string, mixed>|null $subject The subject as ARES returned it, null when
     *      ARES holds no such company.
     * @return \App\BusinessRegister\VatNumberCheck
     */
    public static function readVatNumberCheck(?array $subject): VatNumberCheck
    {
        if ($subject === null) {
            return new VatNumberCheck(VatNumberStatus::Invalid);
        }

        $registrations = is_array($subject['seznamRegistraci'] ?? null) ? $subject['seznamRegistraci'] : [];
        $status = ($registrations['stavZdrojeDph'] ?? null) === self::ACTIVE
            ? VatNumberStatus::Registered
            : VatNumberStatus::NotRegistered;

        return new VatNumberCheck($status, self::mapSubject($subject)['name'] ?: null);
    }

    /**
     * One subject as ARES returned it, null when ARES holds no such company.
     *
     * @param string $identityNumber The identification number to ask about.
     * @return \App\Http\Answer Answering with the record, or null where ARES holds none.
     */
    private function fetch(string $identityNumber): Answer
    {
        $identityNumber = self::withoutWhitespace($identityNumber);

        // ARES answers to a Czech identification number and refuses anything else outright - a
        // Croatian one is eleven digits and comes back a bad request, not an empty answer. Asking
        // at all would turn "this is not ours" into a register that could not be reached.
        if (!IdentityNumber::isValidCzech($identityNumber)) {
            return Answer::of(null);
        }

        return $this->record('ekonomicke-subjekty/' . urlencode($identityNumber));
    }

    /**
     * One of the records ARES keeps, or null where it keeps none.
     *
     * @param string $path The record below the register's address.
     * @return \App\Http\Answer
     */
    private function record(string $path): Answer
    {
        return $this->read(
            fn(): Response => $this->http()->get($this->endpoint($path)),
            missingIsAnAnswer: true,
        );
    }

    /**
     * Read one ARES subject into the shape every register answers in.
     *
     * @param array<int|string, mixed> $subject The subject as ARES returned it.
     * @return array<string, mixed>
     */
    public static function mapSubject(array $subject): array
    {
        $ico = isset($subject['ico']) ? trim((string)$subject['ico']) : '';
        $sidlo = is_array($subject['sidlo'] ?? null) ? $subject['sidlo'] : [];
        $address = isset($sidlo['textovaAdresa']) ? trim((string)$sidlo['textovaAdresa']) : '';
        $vatNumber = isset($subject['dic']) ? trim((string)$subject['dic']) : '';
        $name = trim((string)($subject['obchodniJmeno'] ?? ''));
        $seatKey = self::readAddressKey($sidlo);

        // A sole trader trades under their own name, so what ARES writes in the name field is a
        // person rather than a company. The two go to different fields: a filled-in company is
        // what the CRM reads as a legal entity, so a person put there would be taken for one.
        //
        // Who that person is comes from the trades register, which holds the name in parts - the
        // one line here would only be worth guessing at, and byReference() asks properly.
        $isPerson = self::isNaturalPerson($subject['pravniForma'] ?? null);

        return [
            'reference' => $ico !== '' ? $ico : null,
            'name' => $name,
            'company' => $isPerson ? null : $name,
            'title' => null,
            'first_name' => null,
            'last_name' => null,
            'suffix' => null,
            'date_of_birth' => null,
            'officers' => [],
            'identity_number' => $ico !== '' ? $ico : null,
            'vat_number' => $vatNumber !== '' ? $vatNumber : null,
            'address' => $address !== '' ? $address : null,
            'addresses' => $seatKey === null ? [] : [[
                'key' => $seatKey,
                'label' => $address,
                'seat' => true,
            ]],
        ];
    }

    /**
     * The sole trader behind the entry, as the trades register writes them.
     *
     * The trades register holds the name in parts, degrees and all, which is worth asking for:
     * the basic record has only the one line the name is written on, and taking that apart is a
     * reading of a convention rather than something certain.
     *
     * @param array<int|string, mixed>|null $record The record as the trades register returned it,
     *      null when it holds none.
     * @return array<string, string|null> The name in parts, empty when the register holds
     *      nobody - the entry then names no person at all, rather than one guessed at.
     */
    public static function readTrader(?array $record): array
    {
        if ($record === null) {
            return [];
        }

        $entries = is_array($record['zaznamy'] ?? null) ? $record['zaznamy'] : [];
        $entry = is_array($entries[0] ?? null) ? $entries[0] : [];
        $trader = is_array($entry['osobaPodnikatel'] ?? null) ? $entry['osobaPodnikatel'] : [];
        if ($trader === []) {
            return [];
        }

        return [
            'title' => self::officerValue($trader['titulPredJmenem'] ?? null),
            'first_name' => self::officerName($trader['jmeno'] ?? null),
            'last_name' => self::officerName($trader['prijmeni'] ?? null),
            'suffix' => self::officerValue($trader['titulZaJmenem'] ?? null),
            'date_of_birth' => self::officerValue($trader['datumNarozeni'] ?? null),
        ];
    }

    /**
     * Whether the register of companies holds the subject at all.
     *
     * Only what is entered in it is: a town, a region, a public body or a school is a legal entity
     * without ever appearing there. The basic record says as much, which saves asking a register
     * that would only answer that it has never heard of them.
     *
     * @param array<int|string, mixed> $subject The subject as ARES returned it.
     * @return bool
     */
    private static function isInCompanyRegister(array $subject): bool
    {
        $registrations = is_array($subject['seznamRegistraci'] ?? null) ? $subject['seznamRegistraci'] : [];

        return ($registrations['stavZdrojeVr'] ?? null) === self::ACTIVE;
    }

    /**
     * Everyone in the statutory body as the register of persons holds them.
     *
     * It holds the standing of every legal entity, which is what makes it the one to ask about a
     * town or a school. It keeps only what stands now, so there is nothing to filter, and it wraps
     * each value in a note on how sure of it it is - which is what the reading below unwraps. It
     * carries no degrees, so a name arrives without them.
     *
     * @param array<int|string, mixed>|null $record The record as the register of persons returned
     *      it, null when it holds none.
     * @return list<array<string, string|null>>
     */
    public static function readPersonRegisterOfficers(?array $record): array
    {
        if ($record === null) {
            return [];
        }

        $sitting = [];
        foreach ((array)($record['zaznamy'] ?? []) as $entry) {
            foreach ((array)($entry['statutarniOrgany'] ?? []) as $body) {
                $person = $body['osobaFyzicka']['osobaRob'] ?? null;
                if (!is_array($person) || $person === []) {
                    continue;
                }

                $firstName = self::personRegisterValue($person['jmeno'] ?? null, 'hodnota');
                $lastName = self::personRegisterValue($person['prijmeni'] ?? null, 'hodnota');
                $dateOfBirth = self::personRegisterValue($person['datumNarozeni'] ?? null, 'datum');
                if ($firstName === null && $lastName === null) {
                    continue;
                }

                $identity = implode('|', [(string)$firstName, (string)$lastName, (string)$dateOfBirth]);
                $sitting[$identity] = [
                    'key' => md5($identity),
                    'title' => null,
                    'first_name' => self::officerName($firstName),
                    'last_name' => self::officerName($lastName),
                    'suffix' => null,
                    'date_of_birth' => $dateOfBirth,
                ];
            }
        }

        return array_values($sitting);
    }

    /**
     * Unwrap one value of the register of persons, which writes each with a note on how sure of
     * it it is.
     *
     * @param mixed $value The value as the register returned it.
     * @param string $held The key the value itself is held under.
     * @return string|null
     */
    private static function personRegisterValue(mixed $value, string $held): ?string
    {
        return is_array($value) ? self::officerValue($value[$held] ?? null) : null;
    }

    /**
     * The record the register of persons holds, null when it holds none.
     *
     * @param string $identityNumber The identification number to ask about.
     * @return \App\Http\Answer Answering with the record, or null where ARES keeps none.
     */
    private function fetchPersonRecord(string $identityNumber): Answer
    {
        return $this->record('ekonomicke-subjekty-ros/' . urlencode($identityNumber));
    }

    /**
     * Everyone still sitting in the company's statutory body.
     *
     * The register keeps everyone who ever sat in one; a member still sitting is one the register
     * has not written out, which is what an empty deletion date means.
     *
     * Which of them a company is represented by is not the register's to say, so all of them come
     * back and the choice is left to whoever is filling the form in. Each carries a `key` the form
     * hands back to name the one that was chosen.
     *
     * @param array<int|string, mixed>|null $record The record as the register of companies
     *      returned it, null when there is none.
     * @return list<array<string, string|null>>
     */
    public static function readOfficers(?array $record): array
    {
        if ($record === null) {
            return [];
        }

        $sitting = [];
        foreach ((array)($record['zaznamy'] ?? []) as $entry) {
            foreach ((array)($entry['statutarniOrgany'] ?? []) as $body) {
                foreach ((array)($body['clenoveOrganu'] ?? []) as $member) {
                    $person = is_array($member['fyzickaOsoba'] ?? null) ? $member['fyzickaOsoba'] : [];
                    if (($member['datumVymazu'] ?? null) !== null || $person === []) {
                        continue;
                    }

                    // The same person is written down once per record they appear in, and the
                    // entry carries their address as well - which may have changed between two
                    // of them, so it is the name and date of birth that say who they are. The
                    // same three make the key the form hands back, so a choice survives the
                    // entry being read again.
                    $identity = implode('|', [
                        (string)($person['jmeno'] ?? ''),
                        (string)($person['prijmeni'] ?? ''),
                        (string)($person['datumNarozeni'] ?? ''),
                    ]);
                    $sitting[$identity] = [
                        'key' => md5($identity),
                        'title' => self::officerValue($person['titulPredJmenem'] ?? null),
                        'first_name' => self::officerName($person['jmeno'] ?? null),
                        'last_name' => self::officerName($person['prijmeni'] ?? null),
                        'suffix' => self::officerValue($person['titulZaJmenem'] ?? null),
                        'date_of_birth' => self::officerValue($person['datumNarozeni'] ?? null),
                    ];
                }
            }
        }

        return array_values($sitting);
    }

    /**
     * A name the register writes in capitals throughout, put back the way a name is written.
     *
     * Only a value that is capitals all the way through is touched - anything else is left as it
     * was written, since a name is nobody's to tidy up.
     *
     * @param mixed $value The value as the register returned it.
     * @return string|null
     */
    private static function officerName(mixed $value): ?string
    {
        $value = self::officerValue($value);
        if ($value === null || mb_strtoupper($value) !== $value) {
            return $value;
        }

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * The value where there is one, null where the register left it empty.
     *
     * @param mixed $value The value as the register returned it.
     * @return string|null
     */
    private static function officerValue(mixed $value): ?string
    {
        $value = trim((string)$value);

        return $value !== '' ? $value : null;
    }

    /**
     * The record the trades register holds, null when it holds none.
     *
     * @param string $identityNumber The identification number to ask about.
     * @return \App\Http\Answer Answering with the record, or null where ARES keeps none.
     */
    private function fetchTradesRecord(string $identityNumber): Answer
    {
        return $this->record('ekonomicke-subjekty-rzp/' . urlencode($identityNumber));
    }

    /**
     * The record the register of companies holds, null when it holds none.
     *
     * @param string $identityNumber The identification number to ask about.
     * @return \App\Http\Answer Answering with the record, or null where ARES keeps none.
     */
    private function fetchRegisterRecord(string $identityNumber): Answer
    {
        return $this->record('ekonomicke-subjekty-vr/' . urlencode($identityNumber));
    }

    /**
     * Whether the legal form is one a person carries rather than a company.
     *
     * The codes run 100 to 108 for a natural person - a sole trader under the trades act, a
     * farmer, a person trading under some other act - and from 111 up for the legal entities.
     *
     * @param mixed $legalForm The `pravniForma` as ARES returned it.
     * @return bool
     */
    private static function isNaturalPerson(mixed $legalForm): bool
    {
        $legalForm = trim((string)$legalForm);
        if (!ctype_digit($legalForm)) {
            return false;
        }

        return (int)$legalForm >= 100 && (int)$legalForm <= 108;
    }

    /**
     * The seat as the national address registry knows it.
     *
     * ARES carries the RÚIAN address place code, which is what the address registry answers to
     * for the Czech Republic - so the address itself is read from there rather than taken apart
     * here. A seat ARES did not standardise has no such code, which is what a company registered
     * abroad looks like.
     *
     * @param array<int|string, mixed> $seat The `sidlo` as ARES returned it.
     * @return string|null
     */
    private static function readAddressKey(array $seat): ?string
    {
        if (($seat['standardizaceAdresy'] ?? false) !== true) {
            return null;
        }

        $code = trim((string)($seat['kodAdresnihoMista'] ?? ''));

        return $code !== '' ? 'cz|' . $code : null;
    }

    /**
     * Where the subject does business, besides the seat it is registered at.
     *
     * The register of persons keeps every establishment ever opened, so the ones closed since are
     * left out - an establishment still open is one with no closing date. Each carries the same
     * RÚIAN code the seat does, which is what an address form is filled in from.
     *
     * @param array<int|string, mixed>|null $record The record as the register of persons returned
     *      it, null when it holds none.
     * @return list<array{key: string, label: string, seat: bool}>
     */
    public static function readEstablishments(?array $record): array
    {
        if ($record === null) {
            return [];
        }

        $open = [];
        foreach ((array)($record['zaznamy'] ?? []) as $entry) {
            foreach ((array)($entry['provozovny'] ?? []) as $establishment) {
                $closed = $establishment['datumUkonceniCinnosti'] ?? null;
                if (is_array($closed) && ($closed['datum'] ?? null) !== null) {
                    continue;
                }

                $address = is_array($establishment['adresaProvozovny'] ?? null)
                    ? $establishment['adresaProvozovny']
                    : [];
                $key = self::readAddressKey($address);
                if ($key === null) {
                    continue;
                }

                // two establishments may share an address, and one of them may be the seat
                $open[$key] = [
                    'key' => $key,
                    'label' => trim((string)($address['textovaAdresa'] ?? '')),
                    'seat' => false,
                ];
            }
        }

        return array_values($open);
    }
}
