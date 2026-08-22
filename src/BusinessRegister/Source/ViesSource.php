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
        return __('EU - VIES (by VAT number only)');
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function search(string $query, int $limit = 25): Answer
    {
        return $this->byReference($query)->map(
            fn(?Subject $subject): CollectionInterface => new Collection($subject === null ? [] : [$subject]),
        );
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function byReference(string $reference): Answer
    {
        return $this->ask($reference)->map(
            fn(?array $asked): ?Subject => $asked === null
                ? null
                : self::toSubject(self::mapSubject($asked[2], $asked[0], $asked[1])),
        );
    }

    /**
     * VIES only knows VAT registrations, so a number it does not confirm is as far as it can
     * say invalid - it has no way of telling a company that never registered from one that does
     * not exist.
     *
     * @return \App\Http\Answer<\App\BusinessRegister\VatNumberCheck|null>
     * @inheritDoc
     */
    #[Override]
    public function vatNumberCheck(string $vatNumber): Answer
    {
        return $this->ask($vatNumber)->map(function (?array $asked): ?VatNumberCheck {
            if ($asked === null) {
                return null;
            }

            $answer = $asked[2];

            if (($answer['isValid'] ?? false) !== true) {
                return new VatNumberCheck(VatNumberStatus::Invalid);
            }

            return new VatNumberCheck(VatNumberStatus::Registered, self::readValue($answer['name'] ?? null));
        });
    }

    /**
     * Ask VIES about a number, null when it is not a number VIES can be asked about.
     *
     * @param string $vatNumber The number as it was given, prefix included.
     * @return \App\Http\Answer Answering with the member state, the number without it, and what
     *      VIES said - or with null where this is not a number VIES can be asked about.
     * @return \App\Http\Answer<array{0: string, 1: string, 2: array<int|string, mixed>}|null>
     */
    private function ask(string $vatNumber): Answer
    {
        $vatNumber = strtoupper(self::withoutWhitespace($vatNumber));
        if (!preg_match('/^([A-Z]{2})([0-9A-Z]{2,12})$/', $vatNumber, $matches)) {
            return Answer::of(null);
        }

        [, $memberState, $number] = $matches;

        return $this->readOrMissing(
            fn(): Response => $this->http()->get($this->endpoint(sprintf(
                'ms/%s/vat/%s',
                urlencode($memberState),
                urlencode($number),
            ))),
        )->map(fn(?array $answer): ?array => $answer === null ? null : [$memberState, $number, $answer]);
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
            'name' => $company ?? '',
            'company' => $company ?? '',
            'title' => null,
            'first_name' => null,
            'last_name' => null,
            'suffix' => null,
            'date_of_birth' => null,
            'officers' => [],
            'identity_number' => $identityNumber,
            'vat_number' => $memberState . $number,
            'address' => $address,
            // VIES gives the address as one line and no reference to anything
            'addresses' => [],
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
