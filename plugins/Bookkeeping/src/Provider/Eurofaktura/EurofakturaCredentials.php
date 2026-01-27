<?php
declare(strict_types=1);

namespace Bookkeeping\Provider\Eurofaktura;

/**
 * Value object representing authentication credentials
 * for the Eurofaktura / E-racuni API.
 *
 * This object encapsulates all identity-related data required
 * to authenticate a single API request.
 *
 * Design notes:
 * - Immutable by design (readonly properties)
 * - Intentionally free of any transport or environment logic
 * - Suitable for credential pooling, rotation, or rate-limit strategies
 *
 * Typical usage:
 * - Primary vs secondary API accounts
 * - Explicit credential selection per request
 * - Future round-robin or failover implementations
 *
 * This class contains no behavior and serves purely as a
 * strongly-typed data carrier.
 */
final class EurofakturaCredentials
{
    /**
     * Create a new credentials value object.
     *
     * @param string $username API username / account identifier.
     * @param string $secretKey API secret key associated with the account.
     * @param string $token API access token.
     */
    public function __construct(
        public readonly string $username,
        public readonly string $secretKey,
        public readonly string $token,
    ) {
    }
}
