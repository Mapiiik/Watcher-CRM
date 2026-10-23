<?php
declare(strict_types=1);

namespace Access\Authentication;

use Access\Identifier\LinkedSocialIdentifier;
use Cake\Core\Configure;
use CakeDC\Auth\Authentication\OneTimePasswordAuthenticationCheckerInterface;

/**
 * Whether signing in asks for a one-time code.
 *
 * It does where the settings require one of the account, and where its owner has set one up of
 * their own accord. Somebody required to use one who has not set it up yet does so on signing in.
 * Signing in through an identity provider that asked for a second factor itself asks for none.
 */
class TwoFactorChecker implements OneTimePasswordAuthenticationCheckerInterface
{
    /**
     * Whether one-time codes are on at all.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return Configure::read('OneTimePasswordAuthenticator.login') !== false;
    }

    /**
     * Whether signing in to an account asks for a one-time code.
     *
     * @param array<string, mixed>|null $user The account as it signs in.
     * @return bool
     */
    public function isRequired(?array $user = null): bool
    {
        // the identity provider asked for a second factor already
        if (empty($user) || !$this->isEnabled() || !empty($user[LinkedSocialIdentifier::SECOND_FACTOR])) {
            return false;
        }

        return SecurityPolicy::requiresOneTimePassword($user) || !empty($user['secret_verified']);
    }
}
