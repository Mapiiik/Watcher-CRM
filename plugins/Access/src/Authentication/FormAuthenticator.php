<?php
declare(strict_types=1);

namespace Access\Authentication;

use Access\Model\Table\UsersTable;
use Authentication\Authenticator\FormAuthenticator as BaseFormAuthenticator;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Cake\Datasource\EntityInterface;
use Cake\Http\ServerRequest;
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
        $result = $this->authenticateCounted($request);

        // checked now, the password and the one-time secret are not for the session to keep
        $identity = $result->getData();
        if ($identity instanceof EntityInterface) {
            $identity->unset(UsersTable::SECRETS);
        }

        return $result;
    }

    /**
     * Checks the password, unless the account or the address failed too often.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return \Authentication\Authenticator\ResultInterface
     */
    private function authenticateCounted(ServerRequestInterface $request): ResultInterface
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
                'reason' => 'wrong username or password',
            ]);
        }

        return $result;
    }

    /**
     * The account a request tries to sign in to, if it is a sign-in at all.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return string|null
     */
    private function attemptedAccount(ServerRequestInterface $request): ?string
    {
        if (!$request instanceof ServerRequest || !$request->is('post') || !$this->_checkUrl($request)) {
            return null;
        }

        $account = $request->getData('username');

        return is_string($account) && $account !== '' ? $account : null;
    }
}
