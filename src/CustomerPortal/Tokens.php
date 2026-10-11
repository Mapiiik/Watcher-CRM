<?php
declare(strict_types=1);

namespace App\CustomerPortal;

use Cake\Utility\Security;

/**
 * The token a customer portal holds for a signed-in customer.
 *
 * It names the login and the customer and when it stops being valid, signed with a key derived
 * from the application's salt, so nothing has to be stored for it. The portal can ask only about
 * the customer a token names, and only until it expires.
 */
final class Tokens
{
    /**
     * Issues a token for a signed-in login.
     *
     * @param string $loginId The login that signed in.
     * @param string $customerId The customer it belongs to.
     * @param int $hours How long the token is valid.
     * @return array{token: string, expires: int}
     */
    public static function issue(string $loginId, string $customerId, int $hours): array
    {
        $expires = time() + max(1, $hours) * 3600;
        $payload = self::encode((string)json_encode(['l' => $loginId, 'c' => $customerId, 'e' => $expires]));

        return ['token' => $payload . '.' . self::encode(self::sign($payload)), 'expires' => $expires];
    }

    /**
     * Reads a token, or null when it is not one this application signed or it has expired.
     *
     * @param string $token The token.
     * @return array{login_id: string, customer_id: string, expires: int}|null
     */
    public static function read(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2 || !hash_equals(self::encode(self::sign($parts[0])), $parts[1])) {
            return null;
        }
        $data = json_decode((string)self::decode($parts[0]), true);
        if (!is_array($data) || !isset($data['l'], $data['c'], $data['e']) || (int)$data['e'] < time()) {
            return null;
        }

        return ['login_id' => (string)$data['l'], 'customer_id' => (string)$data['c'], 'expires' => (int)$data['e']];
    }

    /**
     * @param string $payload The encoded payload.
     * @return string
     */
    private static function sign(string $payload): string
    {
        $key = hash('sha256', 'customer-portal-token|' . Security::getSalt(), true);

        return hash_hmac('sha256', $payload, $key, true);
    }

    /**
     * @param string $data Raw bytes.
     * @return string
     */
    private static function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data Base64url text.
     * @return string|false
     */
    private static function decode(string $data): string|false
    {
        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}
