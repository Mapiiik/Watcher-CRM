<?php
declare(strict_types=1);

namespace Access\Authentication;

use Authentication\Authenticator\TokenAuthenticator as BaseTokenAuthenticator;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * API clients signing in with their key.
 *
 * The key is meant to come as a Bearer token. In the address it still works, as clients are moved
 * over one by one, but every such request is logged so that the ones left can be found.
 */
class TokenAuthenticator extends BaseTokenAuthenticator
{
    /**
     * @inheritDoc
     */
    #[Override]
    protected function getToken(ServerRequestInterface $request): ?string
    {
        if (
            $this->getTokenFromHeader($request, $this->getConfig('header')) === null
            && $this->getTokenFromQuery($request, $this->getConfig('queryParam')) !== null
        ) {
            AuthLog::notice('api key in the address', [
                'path' => $request->getUri()->getPath(),
                'ip' => AuthLog::address($request),
            ]);
        }

        return parent::getToken($request);
    }
}
