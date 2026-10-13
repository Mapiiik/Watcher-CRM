<?php
declare(strict_types=1);

namespace Access\Authentication;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Requests from API clients that name themselves with a Bearer token.
 *
 * Such a request has no cookie to forge, so the applications skip CSRF and FormProtection for it.
 * A request with the key in the address, or from the application's own pages, keeps both guards.
 */
final class BearerRequest
{
    /**
     * Whether a request comes from an API client that names itself with a Bearer token.
     *
     * The scheme is matched in any letter case, as the token authenticator matches it.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return bool
     */
    public static function isApi(ServerRequestInterface $request): bool
    {
        return str_starts_with($request->getUri()->getPath(), '/api/')
            && stripos($request->getHeaderLine('Authorization'), 'Bearer ') === 0;
    }
}
