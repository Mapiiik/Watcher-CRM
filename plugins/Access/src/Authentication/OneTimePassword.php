<?php
declare(strict_types=1);

namespace Access\Authentication;

use Com\Tecnick\Barcode\Barcode;

/**
 * One-time codes from an authenticator app: TOTP of RFC 6238, six digits and a code every 30
 * seconds, with the hash function the account was set up with.
 */
final class OneTimePassword
{
    public const DIGITS = 6;

    public const PERIOD = 30;

    private const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * A new secret in Base32, as long as the hash: 160 random bits for SHA-1, as RFC 4226
     * recommends.
     *
     * @param \Access\Authentication\OneTimePasswordAlgorithm $algorithm The hash function.
     * @return string
     */
    public static function createSecret(OneTimePasswordAlgorithm $algorithm = OneTimePasswordAlgorithm::Sha1): string
    {
        return self::base32Encode(random_bytes($algorithm->secretBytes()));
    }

    /**
     * The time step a code was made for, if it is the code of the step now or of the one before or
     * after it, so that a clock a little off still signs in.
     *
     * @param string $secret The secret in Base32.
     * @param string $code The code as typed, digits only.
     * @param int|null $time The time to check at, now where none is given.
     * @param \Access\Authentication\OneTimePasswordAlgorithm $algorithm The hash function.
     * @return int|null The time step, or null where the code is not right.
     */
    public static function verify(
        string $secret,
        string $code,
        ?int $time = null,
        OneTimePasswordAlgorithm $algorithm = OneTimePasswordAlgorithm::Sha1,
    ): ?int {
        if (preg_match('/^\d{' . self::DIGITS . '}$/', $code) !== 1) {
            return null;
        }

        $now = intdiv($time ?? time(), self::PERIOD);
        $match = null;
        // every step is compared, so that how long the answer takes says nothing
        foreach ([$now - 1, $now, $now + 1] as $step) {
            if (hash_equals(self::code($secret, $step, $algorithm), $code)) {
                $match = $step;
            }
        }

        return $match;
    }

    /**
     * The code of a time step.
     *
     * @param string $secret The secret in Base32.
     * @param int $step The time step.
     * @param \Access\Authentication\OneTimePasswordAlgorithm $algorithm The hash function.
     * @return string
     */
    public static function code(
        string $secret,
        int $step,
        OneTimePasswordAlgorithm $algorithm = OneTimePasswordAlgorithm::Sha1,
    ): string {
        $hash = hash_hmac($algorithm->hash(), pack('J', $step), self::base32Decode($secret), true);
        // where the code is read from is said by the last byte, whatever the length of the hash
        $offset = ord($hash[strlen($hash) - 1]) & 0x0f;
        $value = (ord($hash[$offset]) & 0x7f) << 24
            | ord($hash[$offset + 1]) << 16
            | ord($hash[$offset + 2]) << 8
            | ord($hash[$offset + 3]);

        return str_pad((string)($value % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * The address an authenticator app reads from the QR code.
     *
     * @param string $issuer What the app calls the application.
     * @param string $account The account.
     * @param string $secret The secret in Base32.
     * @param \Access\Authentication\OneTimePasswordAlgorithm $algorithm The hash function.
     * @return string
     */
    public static function uri(
        string $issuer,
        string $account,
        string $secret,
        OneTimePasswordAlgorithm $algorithm = OneTimePasswordAlgorithm::Sha1,
    ): string {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($account) . '?'
            . http_build_query([
                'secret' => $secret,
                'issuer' => $issuer,
                'algorithm' => $algorithm->value,
                'digits' => self::DIGITS,
                'period' => self::PERIOD,
            ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * The QR code of an address, as an SVG data URI. It is drawn here, so the secret goes nowhere
     * else.
     *
     * @param string $uri The address.
     * @return string
     */
    public static function qrCode(string $uri): string
    {
        $svg = (new Barcode())
            ->getBarcodeObj('QRCODE,M', $uri, -4, -4, 'black', [-4, -4, -4, -4])
            ->setBackgroundColor('white')
            ->getSvgCode();

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * @param string $bytes The bytes.
     * @return string
     */
    private static function base32Encode(string $bytes): string
    {
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::BASE32[(int)bindec(str_pad($chunk, 5, '0'))];
        }

        return $encoded;
    }

    /**
     * @param string $encoded Base32, in any letter case, with or without spaces and padding.
     * @return string
     */
    private static function base32Decode(string $encoded): string
    {
        $bits = '';
        foreach (str_split(strtoupper((string)preg_replace('/[\s=]+/', '', $encoded))) as $char) {
            $value = strpos(self::BASE32, $char);
            if ($value === false) {
                return '';
            }
            $bits .= str_pad(decbin($value), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr((int)bindec($chunk));
            }
        }

        return $bytes;
    }
}
