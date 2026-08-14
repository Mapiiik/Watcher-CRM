<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use App\BusinessRegister\IdentityNumber;
use App\BusinessRegister\VatNumberCheck;
use App\BusinessRegister\VatNumberStatus;
use Override;
use Throwable;

/**
 * The European VAT number check (VIES).
 *
 * It confirms a VAT number and says who holds it, which makes it a register of last resort for
 * the countries that have no register of their own here. It cannot be searched by name, so a
 * name is answered with nothing rather than with an error - the field says as much.
 *
 * A number is given whole, prefix included ("HR12345678901"), because the prefix is what says
 * which member state to ask.
 */
class ViesSource extends BaseSource implements VatNumberCheckInterface
{
    /**
     * @inheritDoc
     */
    #[Override]
    public function key(): string
    {
        return 'vies';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function label(): string
    {
        return __('EU - VIES (by number only)');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function search(string $query, int $limit = 25): array
    {
        $subject = $this->byReference($query);

        return $subject === null ? [] : [$subject];
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function byReference(string $reference): ?array
    {
        $asked = $this->ask($reference);
        if ($asked === null) {
            return null;
        }

        [$memberState, $number, $answer] = $asked;

        return self::mapSubject($answer, $memberState, $number);
    }

    /**
     * VIES only knows VAT registrations, so a number it does not confirm is as far as it can
     * say invalid - it has no way of telling a company that never registered from one that does
     * not exist.
     *
     * @inheritDoc
     */
    #[Override]
    public function vatNumberCheck(string $vatNumber): ?VatNumberCheck
    {
        $asked = $this->ask($vatNumber);
        if ($asked === null) {
            return null;
        }

        $answer = $asked[2];

        if (($answer['isValid'] ?? false) !== true) {
            return new VatNumberCheck(VatNumberStatus::Invalid);
        }

        return new VatNumberCheck(VatNumberStatus::Registered, self::readValue($answer['name'] ?? null));
    }

    /**
     * Ask VIES about a number, null when it is not a number VIES can be asked about.
     *
     * @param string $vatNumber The number as it was given, prefix included.
     * @return array{0: string, 1: string, 2: array<int|string, mixed>}|null
     *      The member state, the number without it, and what VIES answered.
     */
    private function ask(string $vatNumber): ?array
    {
        $vatNumber = strtoupper(self::withoutWhitespace($vatNumber));
        if (!preg_match('/^([A-Z]{2})([0-9A-Z]{2,12})$/', $vatNumber, $matches)) {
            return null;
        }

        [, $memberState, $number] = $matches;

        try {
            $response = $this->http()->get($this->endpoint(sprintf(
                'ms/%s/vat/%s',
                urlencode($memberState),
                urlencode($number),
            )));
        } catch (Throwable $e) {
            throw $this->unreachable($e);
        }

        if ($response->getStatusCode() === 404) {
            return null;
        }

        return [$memberState, $number, $this->decodeOrThrow($response)];
    }

    /**
     * Read one VIES answer into the shape every register answers in, null when the number is
     * not one VIES knows.
     *
     * VIES writes "---" where it has nothing, and returns the address as several lines, which
     * are joined back into the one line the suggestion list has room for.
     *
     * @param array<int|string, mixed> $subject The answer as VIES returned it.
     * @param string $memberState The two letter member state the number was asked against.
     * @param string $number The number without its member state prefix.
     * @return array<string, mixed>|null
     */
    public static function mapSubject(array $subject, string $memberState, string $number): ?array
    {
        if (($subject['isValid'] ?? false) !== true) {
            return null;
        }

        $company = self::readValue($subject['name'] ?? null);
        $address = self::readValue($subject['address'] ?? null);
        if ($address !== null) {
            $address = trim((string)preg_replace('/\s*\R\s*/u', ', ', $address));
        }

        // For both countries the CRM knows, the VAT number without its prefix is the
        // identification number - but only where it holds up as one.
        $identityNumber = IdentityNumber::isValid($number, $memberState) ? $number : null;

        return [
            'reference' => $memberState . $number,
            'company' => $company ?? '',
            'identity_number' => $identityNumber,
            'vat_number' => $memberState . $number,
            'address' => $address,
        ];
    }

    /**
     * A value VIES actually holds, null where it wrote its placeholder instead.
     *
     * @param mixed $value The value as VIES returned it.
     * @return string|null
     */
    private static function readValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' || trim($value, '-') === '' ? null : $value;
    }
}
