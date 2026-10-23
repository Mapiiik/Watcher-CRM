<?php
declare(strict_types=1);

namespace Access\Identifier;

use Access\Authentication\AuthLog;
use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\Resolver\ResolverAwareTrait;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use CakeDC\Auth\Identifier\SocialIdentifier;
use Override;

/**
 * Finds the account an identity at another provider has been linked to, and nothing else.
 *
 * It never makes an account for an identity it does not know, nor changes one it finds - not
 * even by matching the e-mail, which the provider vouches for only to itself. An identity nobody
 * has linked is logged with its reference, for an admin to link it.
 */
class LinkedSocialIdentifier extends AbstractIdentifier
{
    use LocatorAwareTrait;
    use ResolverAwareTrait;

    /**
     * Whether the provider asked for a second factor, on the account signed in.
     */
    public const SECOND_FACTOR = 'second_factor_at_provider';

    /**
     * The ID token the account signed in with, on the account signed in.
     */
    public const ID_TOKEN = 'provider_id_token';

    /**
     * The methods the provider says the person signed in with (`amr`), on the account signed in.
     */
    public const METHODS = 'provider_methods';

    /**
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'resolver' => 'Authentication.Orm',
    ];

    /**
     * The account the identity is linked to, if it is linked and the account is active.
     *
     * @param array<string, mixed> $credentials The identity, under the key of CakeDC's social identifier.
     * @return \ArrayAccess<string, mixed>|array<string, mixed>|null
     */
    #[Override]
    public function identify(array $credentials): ArrayAccess|array|null
    {
        $identity = $credentials[SocialIdentifier::CREDENTIAL_KEY] ?? null;
        $provider = (string)($identity['provider'] ?? '');
        $reference = (string)($identity['id'] ?? '');
        if ($provider === '' || $reference === '') {
            return null;
        }

        $link = $this->fetchTable('Access.SocialAccounts')->find()
            ->select(['user_id'])
            ->where(['provider' => $provider, 'reference' => $reference, 'active' => true])
            ->disableHydration()
            ->first();
        if (!is_array($link)) {
            $request = Router::getRequest();
            AuthLog::warning('social account not linked', [
                'provider' => $provider,
                'reference' => $reference,
                'ip' => $request !== null ? AuthLog::address($request) : null,
            ]);

            return null;
        }

        $user = $this->getResolver()->find(['id' => $link['user_id']]);
        if ($user instanceof EntityInterface) {
            $this->carry($user, $identity, $provider);
        }

        return $user;
    }

    /**
     * Puts on the account what the provider said of this sign-in: the methods, for the log, whether
     * it asked for a second factor, for the account not to be asked again, and the ID token, for
     * ending the session there on signing out. None of it is stored.
     *
     * @param \Cake\Datasource\EntityInterface $user The account.
     * @param \ArrayAccess<string, mixed>|array<string, mixed> $identity The identity.
     * @param string $provider The provider.
     * @return void
     */
    private function carry(EntityInterface $user, ArrayAccess|array $identity, string $provider): void
    {
        if (is_array($identity['methods'] ?? null)) {
            $user->set(self::METHODS, array_values(array_filter($identity['methods'], 'is_string')));
        }
        if (isset($identity['second_factor_at_provider'])) {
            $user->set(self::SECOND_FACTOR, (bool)$identity['second_factor_at_provider']);
            if (!$identity['second_factor_at_provider']) {
                $request = Router::getRequest();
                AuthLog::notice('no second factor at provider', [
                    'user' => (string)$user->get('username'),
                    'provider' => $provider,
                    'methods' => implode('+', (array)$user->get(self::METHODS)),
                    'ip' => $request !== null ? AuthLog::address($request) : null,
                ]);
            }
        }
        if (!empty($identity['id_token'])) {
            $user->set(self::ID_TOKEN, (string)$identity['id_token']);
            $user->setHidden(array_unique([...$user->getHidden(), self::ID_TOKEN]));
        }
    }
}
