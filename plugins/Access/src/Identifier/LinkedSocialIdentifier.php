<?php
declare(strict_types=1);

namespace Access\Identifier;

use Access\Authentication\AuthLog;
use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Authentication\Identifier\Resolver\ResolverAwareTrait;
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

        return $this->getResolver()->find(['id' => $link['user_id']]);
    }
}
