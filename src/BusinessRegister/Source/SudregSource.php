<?php
declare(strict_types=1);

namespace App\BusinessRegister\Source;

use App\BusinessRegister\IdentityNumber;
use Cake\Cache\Cache;
use Cake\Http\Client\FormData;
use Override;
use RuntimeException;
use Throwable;

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
        return __('HR - Sudski registar');
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
    public function search(string $query, int $limit = 25): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        // A number is the entry itself, and asking for it by name would only find it by accident.
        $number = self::withoutWhitespace($query);
        if (IdentityNumber::isValidCroatian($number)) {
            $subject = $this->byReference($number);

            return $subject === null ? [] : [$subject];
        }

        $data = $this->get('javni/subjekti', [
            'tvrtka_naziv' => $query,
            'offset' => 0,
            'limit' => $limit,
            'only_active' => 'true',
            'expand_relations' => 'true',
        ]);

        $results = [];
        foreach ($data as $subject) {
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
        $reference = self::withoutWhitespace($reference);
        if ($reference === '') {
            return null;
        }

        $data = $this->get('javni/detalji_subjekta', [
            'tip_identifikatora' => 'oib',
            'identifikator' => $reference,
            'expand_relations' => 'true',
        ]);

        // The register answers a single subject either on its own or as a list of one.
        if (array_is_list($data)) {
            $data = $data === [] ? [] : $data[0];
        }

        if (!is_array($data) || $data === []) {
            return null;
        }

        $mapped = self::mapSubject($data);

        return $mapped['reference'] === null ? null : $mapped;
    }

    /**
     * Ask the register, with a token it will accept.
     *
     * @param string $path The path below the register's address.
     * @param array<string, mixed> $query What to ask for.
     * @return array<int|string, mixed>
     */
    private function get(string $path, array $query): array
    {
        try {
            $response = $this->http(['Authorization' => 'Bearer ' . $this->token()])
                ->get($this->endpoint($path), $query);
        } catch (Throwable $e) {
            throw $this->unreachable($e);
        }

        if ($response->getStatusCode() === 404) {
            return [];
        }

        return $this->decodeOrThrow($response);
    }

    /**
     * A token that has not run out, asking for a new one when it has.
     *
     * @return string
     */
    private function token(): string
    {
        $cached = Cache::read($this->tokenCacheKey(), 'business_register');
        if (
            is_array($cached)
            && is_string($cached['token'] ?? null)
            && is_int($cached['expires'] ?? null)
            && $cached['expires'] > time() + self::TOKEN_MARGIN
        ) {
            return $cached['token'];
        }

        try {
            $body = new FormData();
            $body->add('grant_type', 'client_credentials');

            $response = $this->http([
                'Authorization' => 'Basic ' . base64_encode(
                    $this->setting('client_id') . ':' . $this->setting('client_secret'),
                ),
            ])->post($this->endpoint('oauth/token'), (string)$body, ['type' => $body->contentType()]);
        } catch (Throwable $e) {
            throw $this->unreachable($e);
        }

        $data = $this->decodeOrThrow($response);
        $token = $data['access_token'] ?? null;
        if (!is_string($token) || $token === '') {
            throw new RuntimeException(__('The {0} register did not issue a token.', $this->label()));
        }

        Cache::write(
            $this->tokenCacheKey(),
            ['token' => $token, 'expires' => time() + (int)($data['expires_in'] ?? 3600)],
            'business_register',
        );

        return $token;
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
        $oib = isset($subject['oib']) ? trim((string)$subject['oib']) : '';

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
            'address' => self::readAddress($subject),
            // the register gives the seat as text and no reference the address registry knows
            'address_key' => null,
        ];
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
            trim((string)($seat['kucni_broj'] ?? '')),
        ])));

        $address = implode(', ', array_filter([
            $street,
            trim((string)($seat['naziv_naselja'] ?? '')),
        ]));

        return $address !== '' ? $address : null;
    }
}
