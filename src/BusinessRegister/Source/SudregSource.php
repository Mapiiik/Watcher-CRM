<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use App\BusinessRegister\Dto\Subject;
use App\BusinessRegister\IdentityNumber;
use App\Http\Answer;
use App\Model\Enum\CustomerLegalStatus;
use Cake\Cache\Cache;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Http\Client\FormData;
use Cake\Http\Client\Response;
use Override;

/**
 * The Croatian court register (Sudski registar).
 *
 * Unlike ARES it is not open: an account at sudreg-data.gov.hr is exchanged for a client id and
 * secret, which are traded for a token good for a few hours. The token is kept in the cache with
 * the moment it runs out, since it outlives any one request but not the day the cache holds.
 */
class SudregSource extends BaseSource
{
    /**
     * How long before a token runs out to ask for the next one, in seconds.
     */
    private const TOKEN_MARGIN = 60;

    /**
     * @inheritDoc
     */
    #[Override]
    public function key(): string
    {
        return 'sudreg';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function label(): string
    {
        return __('HR - Sudski registar (by OIB only)');
    }

    /**
     * A register that cannot log in is not offered at all.
     *
     * @inheritDoc
     */
    #[Override]
    public function isConfigured(): bool
    {
        return parent::isConfigured()
            && $this->setting('client_id') !== ''
            && $this->setting('client_secret') !== '';
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function search(string $query, int $limit = 25): Answer
    {
        // Only a number is looked for. The register can be searched by name, but what it answers
        // with is the numbers alone - the name and the seat are in the details of each, which are
        // given six a minute - so a list of hits would be one nobody could pick from.
        $number = self::withoutWhitespace($query);
        if (!IdentityNumber::isValidCroatian($number)) {
            return Answer::of(new Collection([]));
        }

        return $this->byReference($number)->map(
            fn(?Subject $subject): CollectionInterface => new Collection($subject ? [$subject] : []),
        );
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function byReference(string $reference): Answer
    {
        $reference = self::withoutWhitespace($reference);

        // a Czech number would go out as an OIB, and a miss would read as a company nobody holds
        if (!IdentityNumber::isValidCroatian($reference)) {
            return Answer::notAsked();
        }

        return $this->get('javni/detalji_subjekta', [
            'tip_identifikatora' => 'oib',
            'identifikator' => $reference,
            'expand_relations' => 'true',
            // otherwise a number nobody holds is answered with an error, as if the register were down
            'no_data_error' => '0',
        ])->map(function (?array $data): ?Subject {
            $data ??= [];

            // The register answers a single subject either on its own or as a list of one.
            if (array_is_list($data)) {
                $data = $data === [] ? [] : $data[0];
            }

            if (!is_array($data) || $data === []) {
                return null;
            }

            return self::toSubject(self::mapSubject($data));
        });
    }

    /**
     * Ask the register, with a token it will accept.
     *
     * @param string $path The path below the register's address.
     * @param array<string, mixed> $query What to ask for.
     * @return \App\Http\Answer<array<int|string, mixed>|null>
     */
    private function get(string $path, array $query): Answer
    {
        $token = $this->token();

        if (!$token->ok()) {
            return Answer::sameFailure($token);
        }

        return $this->readOrMissing(
            fn(): Response => $this->http(['Authorization' => 'Bearer ' . $token->data])
                ->get($this->endpoint($path), $query),
            $path,
        )->map(fn(?array $data): array => $data ?? []);
    }

    /**
     * A token that has not run out, asking for a new one when it has.
     *
     * @return \App\Http\Answer<string>
     */
    private function token(): Answer
    {
        $cached = Cache::read($this->tokenCacheKey(), 'business_register');
        if (
            is_array($cached)
            && is_string($cached['token'] ?? null)
            && is_int($cached['expires'] ?? null)
            && $cached['expires'] > time() + self::TOKEN_MARGIN
        ) {
            return Answer::of($cached['token']);
        }

        $body = new FormData();
        $body->add('grant_type', 'client_credentials');

        $answer = $this->read(fn(): Response => $this->http([
            'Authorization' => 'Basic ' . base64_encode(
                $this->setting('client_id') . ':' . $this->setting('client_secret'),
            ),
        ])->post($this->endpoint('oauth/token'), (string)$body, ['type' => $body->contentType()]), 'oauth/token');

        if (!$answer->ok()) {
            return Answer::sameFailure($answer);
        }

        /** @var array<string, mixed> $data */
        $data = $answer->data;
        $token = $data['access_token'] ?? null;
        if (!is_string($token) || $token === '') {
            return self::unanswered(__('The {0} register did not issue a token.', $this->label()));
        }

        Cache::write(
            $this->tokenCacheKey(),
            ['token' => $token, 'expires' => time() + (int)($data['expires_in'] ?? 3600)],
            'business_register',
        );

        return Answer::of($token);
    }

    /**
     * Where the token is kept. The client id is part of the key so a changed account does not
     * keep being let in on the old one's token.
     *
     * @return string
     */
    private function tokenCacheKey(): string
    {
        return 'sudreg_token_' . md5($this->setting('client_id'));
    }

    /**
     * Read one Sudreg subject into the shape every register answers in.
     *
     * The register gives the name under whichever of its two forms it holds, and the seat as
     * parts rather than as a line.
     *
     * @param array<int|string, mixed> $subject The subject as the register returned it.
     * @return array<string, mixed>
     */
    public static function mapSubject(array $subject): array
    {
        // The register answers the OIB as a number, which loses the zero one may start with.
        $oib = isset($subject['oib']) ? trim((string)$subject['oib']) : '';
        if (ctype_digit($oib) && strlen($oib) < 11) {
            $oib = str_pad($oib, 11, '0', STR_PAD_LEFT);
        }

        return [
            'reference' => $oib !== '' ? $oib : null,
            'name' => self::readName($subject),
            'company' => self::readName($subject),
            'title' => null,
            'first_name' => null,
            'last_name' => null,
            'suffix' => null,
            'date_of_birth' => null,
            'officers' => [],
            'identity_number' => $oib !== '' ? $oib : null,
            'vat_number' => $oib !== '' ? 'HR' . $oib : null,
            'legal_status' => self::legalStatus($subject['pravni_oblik'] ?? null)?->value,
            'address' => self::readAddress($subject),
            // the register gives the seat as text and no reference the address registry knows
            'addresses' => [],
        ];
    }

    /**
     * Who the legal form says the subject is, null where the register gave none.
     *
     * Everything the court register holds is a legal entity but the sole trader (trgovac
     * pojedinac), a natural person registered there. Its code is 6 and does not repeat, unlike
     * some of the register's others; the abbreviation is read as well, should the code move.
     * The crafts (obrt) are not in this register at all.
     *
     * @param mixed $legalForm The `pravni_oblik` as the register returned it.
     * @return \App\Model\Enum\CustomerLegalStatus|null
     */
    private static function legalStatus(mixed $legalForm): ?CustomerLegalStatus
    {
        $type = is_array($legalForm) ? ($legalForm['vrsta_pravnog_oblika'] ?? null) : null;
        if (!is_array($type) || ($type['sifra'] ?? $type['kratica'] ?? null) === null) {
            return null;
        }

        $abbreviation = mb_strtolower(trim((string)($type['kratica'] ?? '')));

        return (int)($type['sifra'] ?? 0) === 6 || $abbreviation === 't.p.'
            ? CustomerLegalStatus::SoleTrader
            : CustomerLegalStatus::LegalEntity;
    }

    /**
     * The name the subject trades under.
     *
     * @param array<int|string, mixed> $subject The subject as the register returned it.
     * @return string
     */
    private static function readName(array $subject): string
    {
        foreach (['skracena_tvrtka', 'tvrtka'] as $key) {
            $name = $subject[$key] ?? null;

            if (is_array($name) && isset($name['ime'])) {
                return trim((string)$name['ime']);
            }

            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        return trim((string)($subject['tvrtka_naziv'] ?? ''));
    }

    /**
     * The seat on one line, empty parts left out.
     *
     * @param array<int|string, mixed> $subject The subject as the register returned it.
     * @return string|null
     */
    private static function readAddress(array $subject): ?string
    {
        $seat = $subject['sjediste'] ?? null;
        if (!is_array($seat)) {
            return null;
        }

        $street = trim(implode(' ', array_filter([
            trim((string)($seat['ulica'] ?? '')),
            trim((string)($seat['kucni_broj'] ?? '')) . trim((string)($seat['kucni_podbroj'] ?? '')),
        ])));

        $address = implode(', ', array_filter([
            $street,
            trim((string)($seat['naziv_naselja'] ?? '')),
        ]));

        return $address !== '' ? $address : null;
    }
}
