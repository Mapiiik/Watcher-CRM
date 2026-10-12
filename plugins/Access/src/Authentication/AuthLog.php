<?php
declare(strict_types=1);

namespace Access\Authentication;

use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Psr\Http\Message\ServerRequestInterface;

/**
 * What happens around signing in, written to the `auth` log scope.
 *
 * Each line names what happened, then the account, the address and the reason as `key="value"`,
 * so that a log server can pick them apart. Passwords, codes and tokens are never written.
 */
final class AuthLog
{
    public const SCOPE = 'auth';

    /**
     * Something that went as it should.
     *
     * @param string $event What happened.
     * @param array<string, scalar|null> $context The account, the address and the like.
     * @return void
     */
    public static function info(string $event, array $context = []): void
    {
        Log::info(self::format($event, $context), ['scope' => [self::SCOPE]]);
    }

    /**
     * Something worth noticing, such as a recovery code used or a second factor reset.
     *
     * @param string $event What happened.
     * @param array<string, scalar|null> $context The account, the address and the like.
     * @return void
     */
    public static function notice(string $event, array $context = []): void
    {
        Log::notice(self::format($event, $context), ['scope' => [self::SCOPE]]);
    }

    /**
     * Something refused.
     *
     * @param string $event What happened.
     * @param array<string, scalar|null> $context The account, the address and the like.
     * @return void
     */
    public static function warning(string $event, array $context = []): void
    {
        Log::warning(self::format($event, $context), ['scope' => [self::SCOPE]]);
    }

    /**
     * The line written for an event.
     *
     * @param string $event What happened.
     * @param array<string, scalar|null> $context The account, the address and the like.
     * @return string
     */
    public static function format(string $event, array $context = []): string
    {
        $line = $event;
        foreach ($context as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $line .= ' ' . $key . '="' . addcslashes((string)$value, "\"\\\0..\37") . '"';
        }

        return $line;
    }

    /**
     * The address a request came from.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return string
     */
    public static function address(ServerRequestInterface $request): string
    {
        if ($request instanceof ServerRequest) {
            return $request->clientIp();
        }

        return (string)($request->getServerParams()['REMOTE_ADDR'] ?? '');
    }
}
