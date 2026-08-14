<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use App\BusinessRegister\IdentityNumber;
use App\BusinessRegister\VatNumberCheck;
use App\BusinessRegister\VatNumberStatus;
use Override;
use Throwable;

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
    public function search(string $query, int $limit = 25): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        // A number is the entry itself, and asking for it by name would only find it by accident.
        $number = self::withoutWhitespace($query);
        if (IdentityNumber::isValidCzech($number)) {
            $subject = $this->byReference($number);

            return $subject === null ? [] : [$subject];
        }

        try {
            $response = $this->http()->post(
                $this->endpoint('ekonomicke-subjekty/vyhledat'),
                ['obchodniJmeno' => $query, 'pocet' => $limit, 'start' => 0],
                ['type' => 'json'],
            );
        } catch (Throwable $e) {
            throw $this->unreachable($e);
        }

        $data = $this->decodeOrThrow($response);
        $subjects = $data['ekonomickeSubjekty'] ?? [];
        if (!is_array($subjects)) {
            return [];
        }

        $results = [];
        foreach ($subjects as $subject) {
            if (!is_array($subject)) {
                continue;
            }

            $mapped = self::mapSubject($subject);
            if ($mapped['reference'] !== null) {
                $results[] = $mapped;
            }
        }

        return $results;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function byReference(string $reference): ?array
    {
        $subject = $this->fetch($reference);
        if ($subject === null) {
            return null;
        }

        $mapped = self::mapSubject($subject);

        return $mapped['reference'] === null ? null : $mapped;
    }

    /**
     * ARES holds both whether the company exists and whether it pays VAT, so it can tell a
     * company that never registered from a number nobody holds. A Czech VAT number is the
     * identification number with a prefix, and anything else is not ARES's to answer.
     *
     * @inheritDoc
     */
    #[Override]
    public function vatNumberCheck(string $vatNumber): ?VatNumberCheck
    {
        $vatNumber = strtoupper(self::withoutWhitespace($vatNumber));
        if (!preg_match('/^CZ(\d{8})$/', $vatNumber, $matches)) {
            return null;
        }

        [, $identityNumber] = $matches;
        if (!IdentityNumber::isValidCzech($identityNumber)) {
            return new VatNumberCheck(VatNumberStatus::Invalid);
        }

        return self::readVatNumberCheck($this->fetch($identityNumber));
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

        return new VatNumberCheck($status, self::mapSubject($subject)['company'] ?: null);
    }

    /**
     * One subject as ARES returned it, null when ARES holds no such company.
     *
     * @param string $identityNumber The identification number to ask about.
     * @return array<int|string, mixed>|null
     */
    private function fetch(string $identityNumber): ?array
    {
        $identityNumber = self::withoutWhitespace($identityNumber);
        if ($identityNumber === '') {
            return null;
        }

        try {
            $response = $this->http()->get($this->endpoint('ekonomicke-subjekty/' . urlencode($identityNumber)));
        } catch (Throwable $e) {
            throw $this->unreachable($e);
        }

        if ($response->getStatusCode() === 404) {
            return null;
        }

        return $this->decodeOrThrow($response);
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

        return [
            'reference' => $ico !== '' ? $ico : null,
            'company' => trim((string)($subject['obchodniJmeno'] ?? '')),
            'identity_number' => $ico !== '' ? $ico : null,
            'vat_number' => $vatNumber !== '' ? $vatNumber : null,
            'address' => $address !== '' ? $address : null,
        ];
    }
}
