<?php
declare(strict_types=1);

namespace App\CustomerPortal;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Security;

/**
 * Links a customer is sent to set a password, or to get a login where they have none.
 *
 * A link is signed, so nothing is stored for it. It names the customer, the e-mail it was sent
 * to and a fingerprint of the customer's logins as they were: once a password is set or a login
 * made, the fingerprint no longer matches and the link is spent.
 */
final class AccessLinks
{
    use LocatorAwareTrait;

    /**
     * How long a link works.
     */
    public const HOURS = 1;

    /**
     * A link for a customer, sent to one of their e-mails.
     *
     * @param string $customerId The customer.
     * @param string $email The e-mail it is sent to.
     * @return array{token: string, expires: int}
     */
    public function issue(string $customerId, string $email): array
    {
        $expires = time() + self::HOURS * 3600;
        $payload = self::encode((string)json_encode([
            'c' => $customerId,
            'm' => mb_strtolower(trim($email)),
            'e' => $expires,
            'f' => $this->fingerprint($customerId),
        ]));

        return ['token' => $payload . '.' . self::encode(self::sign($payload)), 'expires' => $expires];
    }

    /**
     * Whom a link is for, while it works: signed here, not expired, not spent, and the e-mail
     * still one of the customer's.
     *
     * @param string $token The token of the link.
     * @return array{customer_id: string, email: string}|null
     */
    public function read(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2 || !hash_equals(self::encode(self::sign($parts[0])), $parts[1])) {
            return null;
        }
        $data = json_decode((string)self::decode($parts[0]), true);
        if (
            !is_array($data)
            || !isset($data['c'], $data['m'], $data['e'], $data['f'])
            || (int)$data['e'] < time()
            || !hash_equals($this->fingerprint((string)$data['c']), (string)$data['f'])
            || !$this->isEmailOf((string)$data['c'], (string)$data['m'])
        ) {
            return null;
        }

        return ['customer_id' => (string)$data['c'], 'email' => (string)$data['m']];
    }

    /**
     * Whether an e-mail is one the customer has, whatever it is used for.
     *
     * @param string $customerId The customer.
     * @param string $email The e-mail.
     * @return bool
     */
    public function isEmailOf(string $customerId, string $email): bool
    {
        return $this->fetchTable('Emails')->exists([
            'customer_id' => $customerId,
            'LOWER(email)' => mb_strtolower(trim($email)),
        ]);
    }

    /**
     * The customer's logins as they are now, changing with any password set and any login made.
     *
     * @param string $customerId The customer.
     * @return string
     */
    private function fingerprint(string $customerId): string
    {
        $logins = $this->fetchTable('Logins')->find()
            ->select(['id', 'password'])
            ->where(['customer_id' => $customerId])
            ->orderBy(['id' => 'ASC'])
            ->disableHydration()
            ->toArray();
        $lines = array_map(fn(array $login): string => $login['id'] . ':' . $login['password'], $logins);

        return self::encode(hash('sha256', implode("\n", $lines), true));
    }

    /**
     * @param string $payload What is signed.
     * @return string
     */
    private static function sign(string $payload): string
    {
        $key = hash('sha256', 'customer-portal-access|' . Security::getSalt(), true);

        return hash_hmac('sha256', $payload, $key, true);
    }

    /**
     * @param string $data Bytes.
     * @return string
     */
    private static function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @param string $data What encode() made.
     * @return string|false
     */
    private static function decode(string $data): string|false
    {
        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}
