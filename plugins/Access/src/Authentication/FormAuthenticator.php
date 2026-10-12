<?php
declare(strict_types=1);

namespace Access\Authentication;

use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Identifier\IdentifierInterface;
use Cake\Http\ServerRequest;
use CakeDC\Auth\Authenticator\FormAuthenticator as BaseFormAuthenticator;
use CakeDC\Users\Utility\UsersUrl;
use Override;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Signing in with a username and a password, with failures counted.
 *
 * An account that failed too often from one address, or an address that failed too often, is
 * refused for a while without its password being looked at. Every failure and refusal is logged.
 */
class FormAuthenticator extends BaseFormAuthenticator
{
    /**
     * Refused for too many failures, without the password being looked at.
     */
    public const FAILURE_BLOCKED = 'FAILURE_BLOCKED';

    /**
     * @inheritDoc
     */
    #[Override]
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        $account = $this->attemptedAccount($request);
        if ($account === null) {
            return parent::authenticate($request);
        }

        $address = AuthLog::address($request);
        $throttle = LoginThrottle::fromSettings();
        if ($throttle->blocked($account, $address)) {
            AuthLog::warning('sign-in blocked', ['user' => $account, 'ip' => $address]);

            return new Result(null, self::FAILURE_BLOCKED);
        }

        $result = parent::authenticate($request);
        if ($result->isValid()) {
            $throttle->succeeded($account, $address);
        } elseif ($result->getStatus() !== Result::FAILURE_CREDENTIALS_MISSING) {
            $throttle->failed($account, $address);
            AuthLog::warning('sign-in failed', [
                'user' => $account,
                'ip' => $address,
                'reason' => $result->getStatus() === self::FAILURE_INVALID_RECAPTCHA
                    ? 'invalid reCAPTCHA'
                    : 'wrong username or password',
            ]);
        }

        return $result;
    }

    /**
     * The identifiers that check the password.
     *
     * Signing in asks for them to store a password hashed the old way again, and finds nothing to
     * ask without this.
     *
     * @return \Authentication\Identifier\IdentifierInterface
     */
    public function getIdentifier(): IdentifierInterface
    {
        return $this->identifier;
    }

    /**
     * The account a request tries to sign in to, if it is a sign-in at all.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return string|null
     */
    private function attemptedAccount(ServerRequestInterface $request): ?string
    {
        if (
            !$request instanceof ServerRequest
            || !$request->is('post')
            || !UsersUrl::checkActionOnRequest('login', $request)
        ) {
            return null;
        }

        $account = $request->getData('username');

        return is_string($account) && $account !== '' ? $account : null;
    }
}
