<?php
declare(strict_types=1);

namespace Access\Authentication;

use Access\Model\Entity\User;
use Authentication\Authenticator\Result;
use Authentication\Authenticator\ResultInterface;
use Authentication\Authenticator\SessionAuthenticator as BaseSessionAuthenticator;
use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\ORM\Locator\LocatorAwareTrait;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A session, for as long as the account behind it allows.
 *
 * What the session remembers from signing in is not enough: an account switched off or a role
 * taken away has to hold at once, not when the window is closed. So the account is read on every
 * request, and the role and the superuser flag come from it. The rest of what the session
 * remembers stays, the token for signing out of the identity provider among it.
 */
class SessionAuthenticator extends BaseSessionAuthenticator
{
    use LocatorAwareTrait;

    /**
     * Where the session keeps when it signed in, to compare with the moment sessions of the
     * account last ended. Kept beside the identity, which only ever holds what the account says.
     *
     * @var string
     */
    public const SIGNED_IN_AT = 'AuthSignedInAt';

    /**
     * @inheritDoc
     */
    #[Override]
    public function authenticate(ServerRequestInterface $request): ResultInterface
    {
        $result = parent::authenticate($request);
        /** @var \ArrayAccess<string, mixed>|null $identity */
        $identity = $result->getData();
        // an identity that names no stored account has nothing to be compared with
        if (!$result->isValid() || !isset($identity['id'])) {
            return $result;
        }

        /** @var \Cake\Http\Session $session */
        $session = $request->getAttribute('session');
        $account = $this->account((string)$identity['id']);
        if ($account === null || !$account->sessionsAllow((int)$session->read(self::SIGNED_IN_AT))) {
            AuthLog::notice('session ended', [
                'user' => (string)($identity['username'] ?? ''),
                'ip' => AuthLog::address($request),
            ]);
            $this->clearIdentity($request, new Response());

            return new Result(null, Result::FAILURE_IDENTITY_NOT_FOUND);
        }

        $identity['role'] = $account->role;
        $identity['is_superuser'] = $account->is_superuser;

        return $result;
    }

    /**
     * Keeps the identity, and on signing in the moment it happened.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param \ArrayAccess<string, mixed> $identity The identity to keep.
     * @return array<string, mixed>
     */
    #[Override]
    public function persistIdentity(ServerRequestInterface $request, ResponseInterface $response, $identity): array
    {
        /** @var \Cake\Http\Session $session */
        $session = $request->getAttribute('session');
        $signingIn = !$session->check($this->getConfig('sessionKey'));

        $result = parent::persistIdentity($request, $response, $identity);
        if ($signingIn) {
            $session->write(self::SIGNED_IN_AT, Chronos::now()->getTimestamp());
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function clearIdentity(ServerRequestInterface $request, ResponseInterface $response): array
    {
        /** @var \Cake\Http\Session $session */
        $session = $request->getAttribute('session');
        $session->delete(self::SIGNED_IN_AT);

        return parent::clearIdentity($request, $response);
    }

    /**
     * The account as it stands, if it may still be signed in to.
     *
     * @param string $id Its id.
     * @return \Access\Model\Entity\User|null
     */
    private function account(string $id): ?User
    {
        $users = $this->fetchTable((string)Configure::read('Users.table', 'Users'));

        /** @var \Access\Model\Entity\User|null */
        return $users->find('active')
            ->select(['id', 'role', 'is_superuser', 'sessions_valid_from'])
            ->where([$users->aliasField('id') => $id])
            ->first();
    }
}
